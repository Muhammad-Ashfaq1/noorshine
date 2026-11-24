import assert from 'node:assert/strict';
import { test } from 'node:test';
import { readJson } from '../scripts/lib/catalog.mjs';
import { parseArgs, requireWriteApproval } from '../scripts/lib/config.mjs';

process.env.WP_BASE_URL = 'https://noorshine-test.invalid';
process.env.WP_API_USERNAME = 'fixture-admin';
process.env.WP_APPLICATION_PASSWORD = 'fixture-password';
process.env.WC_CONSUMER_KEY = 'fixture-key';
process.env.WC_CONSUMER_SECRET = 'fixture-secret';
const { apiRequest, config } = await import('../scripts/lib/api-client.mjs');

test('contradictory write and dry-run flags are rejected', () => {
  for (const flag of ['--offline', '--dry-run']) {
    assert.throws(() => requireWriteApproval(parseArgs(['--apply', '--backup-confirmed', flag])));
  }
});

test('a redirected credentialed write is never followed or replayed', async app => {
  app.mock.method(globalThis, 'fetch', async (url, request) => {
    assert.equal(request.redirect, 'manual');
    assert.equal(new URL(url).origin, 'https://noorshine-test.invalid');
    assert.match(request.headers.Authorization, /^Basic /);
    return new Response(null, { status: 307, headers: { location: 'http://different-host.invalid' } });
  });
  await assert.rejects(apiRequest('/wp-json/wc/v3/products', { method: 'POST', body: {} }), /Redirect refused/);
  assert.equal(fetch.mock.callCount(), 1);
});

test('non-HTTPS configuration is blocked before a request', async app => {
  app.mock.method(globalThis, 'fetch');
  const saved = config.baseUrl;
  config.baseUrl = 'http://noorshine-test.invalid';
  try {
    await assert.rejects(apiRequest('/wp-json/'), /must be HTTPS/);
    assert.equal(fetch.mock.callCount(), 0);
  } finally {
    config.baseUrl = saved;
  }
});

test('draft import uses term IDs, resumes cleanly and refuses unrelated SKU matches', async app => {
  const products = readJson('data/products.json');
  const categories = readJson('data/categories.json').map((category, index) => ({ ...category, id: index + 1 }));
  const remote = [];
  const tags = [];
  app.mock.method(console, 'log', () => {});
  app.mock.method(globalThis, 'fetch', async (url, request) => {
    const route = new URL(url).pathname;
    const endpoint = route.replace('/wp-json/wc/v3/products', '');
    if (request.method === 'GET') {
      if (endpoint === '/categories') return Response.json(categories);
      if (endpoint === '/tags') return Response.json(tags);
      if (endpoint === '') return Response.json(remote);
    }
    const record = JSON.parse(request.body);
    if (request.method === 'POST' && endpoint === '/tags') {
      const tag = { ...record, id: tags.length + 1 };
      tags.push(tag);
      return Response.json(tag, { status: 201 });
    }
    assert.equal(request.method, 'POST');
    assert.equal(endpoint, '');
    assert.equal(record.status, 'draft');
    assert.equal(record.catalog_visibility, 'hidden');
    assert.equal(record.stock_status, 'outofstock');
    assert.equal(record.regular_price, undefined);
    assert.ok(record.tags.every(tag => Number.isInteger(tag.id)));
    assert.ok(record.categories.every(category => Number.isInteger(category.id)));
    const created = { ...record, id: remote.length + 1 };
    remote.push(created);
    return Response.json(created, { status: 201 });
  });
  const originalArgv = process.argv;
  process.argv = [...process.argv.slice(0, 2), '--apply', '--backup-confirmed'];
  try {
    await import('../scripts/import-products.mjs?test=initial');
    assert.deepEqual(remote.map(product => product.sku), products.map(product => product.sku));
    remote[0].status = 'publish';
    await import('../scripts/import-products.mjs?test=rerun');
    assert.equal(remote.length, 40);
    assert.equal(remote[0].status, 'publish');
    remote[39].meta_data = [];
    const priorWrites = fetch.mock.calls.filter(call => call.arguments[1]?.method === 'POST').length;
    await assert.rejects(import('../scripts/import-products.mjs?test=conflict'), /existing SKU, external ID or slug conflicts/);
    assert.equal(fetch.mock.calls.filter(call => call.arguments[1]?.method === 'POST').length, priorWrites);
  } finally {
    process.argv = originalArgv;
  }
});
