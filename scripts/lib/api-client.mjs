import { setTimeout as delay } from 'node:timers/promises';
import { loadEnv } from './config.mjs';
import { createLogger } from './logger.mjs';

const config = loadEnv();
const log = createLogger('api-sync');

function authHeader(kind) {
  if (kind === 'wp') {
    if (!config.wpUsername || !config.wpApplicationPassword) return null;
    return `Basic ${Buffer.from(`${config.wpUsername}:${config.wpApplicationPassword}`).toString('base64')}`;
  }
  if (kind !== 'wc' || !config.wcKey || !config.wcSecret) return null;
  return `Basic ${Buffer.from(`${config.wcKey}:${config.wcSecret}`).toString('base64')}`;
}

export async function apiRequest(pathname, options = {}) {
  const {
    method = 'GET',
    body,
    auth = pathname.startsWith('/wp-json/wc/') ? 'wc' : 'wp',
    requireAuth = method !== 'GET',
    retries = config.maxRetries,
    rawBody,
    contentHeaders = {}
  } = options;
  if (!pathname.startsWith('/wp-json/')) throw new Error('Only WordPress REST paths are supported.');
  let base;
  try { base = new URL(config.baseUrl); } catch { throw new Error('Invalid WP_BASE_URL.'); }
  if (base.protocol !== 'https:' || base.username || base.password || base.search || base.hash) {
    throw new Error('WP_BASE_URL must be HTTPS, with no URL credentials, query or fragment.');
  }
  const authorization = authHeader(auth);
  if (requireAuth && !authorization) {
    throw new Error(`Missing ${auth === 'wc' ? 'WooCommerce REST keys' : 'WordPress Application Password'} in .env`);
  }

  const headers = { Accept: 'application/json', 'User-Agent': 'NoorShineCatalogSync/1.0', ...contentHeaders };
  if (body !== undefined) headers['Content-Type'] = 'application/json';
  if (authorization) headers.Authorization = authorization;
  const url = `${config.baseUrl}${pathname}`;
  const route = pathname.split('?')[0];
  const readOnly = method === 'GET' || method === 'HEAD';
  const retryLimit = readOnly && Number.isInteger(retries) ? Math.max(0, Math.min(retries, 5)) : 0;

  for (let attempt = 0; attempt <= retryLimit; attempt += 1) {
    const controller = new AbortController();
    const timeout = setTimeout(() => controller.abort(), config.timeoutMs);
    try {
      let response;
      try {
        response = await fetch(url, {
          method,
          headers,
          redirect: 'manual',
          cache: 'no-store',
          body: rawBody ?? (body === undefined ? undefined : JSON.stringify(body)),
          signal: controller.signal
        });
      } catch (error) {
        if (attempt === retryLimit) {
          const cause = error.cause?.code;
          const reason = typeof cause === 'string' && /^[A-Z0-9_]{1,40}$/.test(cause) ? cause : error.name === 'AbortError' ? 'timeout' : 'network error';
          throw new Error(`${method} ${route} failed (${reason}).${readOnly ? '' : ' Check server state before retrying this write.'}`);
        }
        log('warn', `Network failure; retrying ${method} ${route}`);
        await delay(Math.min(2 ** attempt * 1000, 10000));
        continue;
      }
      const text = await response.text();
      let data;
      let validJson = true;
      try { data = text ? JSON.parse(text) : null; } catch { validJson = false; }

      if (response.ok) {
        if (!validJson) throw new Error(`${method} ${route} returned HTTP ${response.status} without valid JSON.`);
        return { data, status: response.status, headers: response.headers };
      }
      const retryable = response.status === 429 || response.status >= 500;
      if (!retryable || attempt === retryLimit) {
        const code = typeof data?.code === 'string' && !/^(ck_|cs_)/i.test(data.code) && /^(rest_|woocommerce_|jwt_auth_)[a-z0-9_]{1,70}$/.test(data.code) ? ` (${data.code})` : '';
        const hint = response.status >= 300 && response.status < 400
          ? ' Redirect refused. Verify the canonical HTTPS URL.'
          : !readOnly && retryable ? ' Check server state before retrying this write.' : '';
        throw new Error(`${method} ${route} returned HTTP ${response.status}${code}.${hint}`);
      }
      const retryAfter = Number(response.headers.get('retry-after')) || 2 ** attempt;
      log('warn', `Transient HTTP ${response.status}; retrying ${method} ${route}`);
      await delay(Math.min(retryAfter * 1000, 10000));
    } finally {
      clearTimeout(timeout);
    }
  }
  throw new Error('Request retry loop ended unexpectedly.');
}

export async function getAll(pathname, query = {}, options = {}) {
  const items = [];
  let page = 1;
  while (true) {
    const params = new URLSearchParams({ ...query, per_page: '100', page: String(page) });
    const response = await apiRequest(`${pathname}?${params}`, options);
    if (!Array.isArray(response.data)) throw new Error(`Expected an array from ${pathname}`);
    items.push(...response.data);
    const totalPages = Number(response.headers.get('x-wp-totalpages') || 1);
    if (page >= totalPages) return items;
    page += 1;
    await delay(150);
  }
}

export { config };

