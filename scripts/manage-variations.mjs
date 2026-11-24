import fs from 'node:fs';
import { apiRequest, getAll } from './lib/api-client.mjs';
import { parseArgs, requireWriteApproval } from './lib/config.mjs';
const args = parseArgs();
const apply = args.has('apply');
if (apply) requireWriteApproval(args);
const sku = args.get('sku');
const file = args.get('file');
if (!sku || !file) throw new Error('Usage: --sku NS-001 --file variations.json [--apply --backup-confirmed]');
const variations = JSON.parse(fs.readFileSync(file, 'utf8'));
if (!Array.isArray(variations)) throw new Error('Variation file must contain a JSON array.');
const variationSkus = new Set();
for (const variation of variations) {
  if (!variation || !/^[a-z0-9][a-z0-9._-]{1,99}$/i.test(variation.sku || '') || !/^\d+(\.\d{1,2})?$/.test(variation.regular_price || '') || Number(variation.regular_price) <= 0 || !Array.isArray(variation.attributes) || !variation.attributes.length) {
    throw new Error('Every variation requires a stable unique SKU, positive approved regular_price, and attributes.');
  }
  const key = variation.sku.toLowerCase();
  if (variationSkus.has(key)) throw new Error('Duplicate variation SKU in the input file.');
  variationSkus.add(key);
}
const products = await getAll('/wp-json/wc/v3/products', { sku }, { auth: 'wc', requireAuth: true });
if (products.length !== 1 || products[0].sku !== sku || products[0].type !== 'variable') throw new Error(`Expected one variable parent product for ${sku}`);
const endpoint = `/wp-json/wc/v3/products/${products[0].id}/variations`;
const existing = await getAll(endpoint, { status: 'any' }, { auth: 'wc', requireAuth: true });
const signature = attributes => attributes.map(attribute => `${attribute.id || attribute.name?.trim().toLowerCase()}=${attribute.option?.trim().toLowerCase()}`).sort().join('|');
const planned = new Set();
for (const variation of variations) {
  const choice = signature(variation.attributes);
  if (planned.has(choice)) throw new Error('Duplicate variation combination in the input file.');
  planned.add(choice);
  const matches = existing.filter(item => item.sku?.toLowerCase() === variation.sku.toLowerCase() || signature(item.attributes) === choice);
  if (matches.length > 1 || (matches[0] && (matches[0].sku !== variation.sku || signature(matches[0].attributes) !== choice))) {
    throw new Error(`${variation.sku}: existing variation SKU or options conflict.`);
  }
  if (matches.length) { console.log(`PRESERVE ${variation.sku} (#${matches[0].id})`); continue; }
  if (!apply) { console.log(`CREATE ${variation.sku} for ${sku} (dry run)`); continue; }
  const { sku: variationSku, regular_price, attributes } = variation;
  const result = await apiRequest(endpoint, { method: 'POST', auth: 'wc', requireAuth: true, body: { sku: variationSku, regular_price, attributes } });
  if (result.data?.sku !== variationSku || !Number.isInteger(result.data?.id)) throw new Error(`${variationSku}: unexpected variation response. Inspect WooCommerce before retrying.`);
  console.log(`Created ${variationSku} (#${result.data.id}, HTTP ${result.status})`);
}

