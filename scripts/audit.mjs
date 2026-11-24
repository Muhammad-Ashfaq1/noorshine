import { apiRequest, config } from './lib/api-client.mjs';
import { parseArgs } from './lib/config.mjs';
const publicOnly = parseArgs().has('public');

const checks = [
  ['WordPress REST index', '/wp-json/', 'wp', false],
  ['WordPress content types', '/wp-json/wp/v2/types', 'wp', false],
  ['WordPress authenticated user', '/wp-json/wp/v2/users/me?context=edit', 'wp', true],
  ['WordPress themes', '/wp-json/wp/v2/themes?status=active', 'wp', true],
  ['WordPress plugins', '/wp-json/wp/v2/plugins?per_page=100', 'wp', true],
  ['WooCommerce system status', '/wp-json/wc/v3/system_status', 'wc', true],
  ['WooCommerce products', '/wp-json/wc/v3/products?per_page=1', 'wc', true],
  ['WooCommerce categories', '/wp-json/wc/v3/products/categories?per_page=1', 'wc', true]
];

let failures = 0;
for (const [label, path, auth, requiresCredentials] of checks) {
  const configured = auth === 'wp'
    ? Boolean(config.wpUsername && config.wpApplicationPassword)
    : Boolean(config.wcKey && config.wcSecret);
  if (requiresCredentials && (publicOnly || !configured)) {
    console.log(`SKIP ${label}: ${publicOnly ? 'public mode' : 'credentials missing'}`);
    if (!publicOnly) failures += 1;
    continue;
  }
  try {
    const response = await apiRequest(path, { auth: requiresCredentials ? auth : 'none', requireAuth: requiresCredentials, retries: 1 });
    const identifier = value => typeof value === 'string' && /^[a-z][a-z0-9_/-]{0,100}$/i.test(value) ? value : 'unknown';
    const version = value => typeof value === 'string' && /^\d+(?:\.\d+){1,3}$/.test(value) ? value : 'unknown';
    let detail = `HTTP ${response.status}`;
    if (path === '/wp-json/') detail += `, wc/v3=${response.data?.namespaces?.includes('wc/v3') ? 'present' : 'absent'}, homepage=${identifier(response.data?.show_on_front)}`;
    if (path.includes('/users/me')) detail += `, roles=${(response.data?.roles || []).map(identifier).join(', ') || 'unavailable'}`;
    if (path.includes('/themes?')) detail += `, active=${(response.data || []).map(theme => identifier(theme.stylesheet)).join(', ') || 'unavailable'}`;
    if (path.includes('/plugins?')) detail += `, plugins=${(response.data || []).map(plugin => `${identifier(plugin.plugin)} (${identifier(plugin.status)})`).join(', ') || 'none returned'}`;
    if (path.endsWith('/system_status')) {
      const env = response.data?.environment;
      detail += `, WooCommerce ${version(env?.version)}, WordPress ${version(env?.wp_version)}, PHP ${version(env?.php_version)}`;
    }
    if (path.includes('per_page=1') && Array.isArray(response.data)) {
      const total = response.headers.get('x-wp-total') || '';
      if (/^\d+$/.test(total)) detail += `, total=${total}`;
    }
    console.log(`PASS ${label}: ${detail}`);
  } catch (error) {
    console.log(`FAIL ${label}: ${error.message}`);
    failures += 1;
  }
}
if (failures) process.exitCode = 1;

