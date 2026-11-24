import { apiRequest } from './lib/api-client.mjs';
import { parseArgs, requireWriteApproval } from './lib/config.mjs';
const args = parseArgs();
const apply = args.has('apply');
if (apply) requireWriteApproval(args);

const settings = [
  ['/wp-json/wc/v3/settings/general/woocommerce_currency', { value: 'PKR' }, 'Currency: PKR'],
  ['/wp-json/wc/v3/settings/general/woocommerce_default_country', { value: 'PK' }, 'Store country: Pakistan (address remains incomplete)'],
  ['/wp-json/wc/v3/settings/products/woocommerce_manage_stock', { value: 'yes' }, 'Inventory management: enabled'],
  ['/wp-json/wc/v3/settings/account/woocommerce_enable_guest_checkout', { value: 'yes' }, 'Guest checkout: enabled'],
  ['/wp-json/wc/v3/settings/account/woocommerce_enable_signup_and_login_from_checkout', { value: 'yes' }, 'Account signup during checkout: enabled'],
  ['/wp-json/wc/v3/payment_gateways/cod', { enabled: false }, 'Cash on delivery: disabled pending approval'],
  ['/wp-json/wc/v3/payment_gateways/bacs', { enabled: false }, 'Bank transfer: disabled pending bank details']
];

const readOptions = { auth: 'wc', requireAuth: true };
if (args.has('offline')) {
  for (const [, , label] of settings) console.log(`OFFLINE PLAN ${label}`);
  process.exit(0);
}
const [zones, gateways, ...current] = await Promise.all([
  apiRequest('/wp-json/wc/v3/shipping/zones', readOptions),
  apiRequest('/wp-json/wc/v3/payment_gateways', readOptions),
  ...settings.map(([endpoint]) => apiRequest(endpoint, readOptions))
]);
if (!Array.isArray(zones.data) || !Array.isArray(gateways.data)) throw new Error('Expected WooCommerce shipping zone and gateway lists.');
const otherEnabled = gateways.data.filter(gateway => gateway.enabled && !['cod', 'bacs'].includes(gateway.id));
console.log(`Shipping zones: ${zones.data.length}. Other enabled gateways: ${otherEnabled.length}.`);
if (apply && otherEnabled.length) throw new Error('Review enabled payment gateways in WooCommerce before applying the launch configuration.');
for (const [index, [endpoint, body, label]] of settings.entries()) {
  const key = Object.keys(body)[0];
  const value = current[index].data?.[key];
  if (value === undefined) throw new Error(`Missing current setting: ${label}`);
}
for (const [index, [endpoint, body, label]] of settings.entries()) {
  const [key, desired] = Object.entries(body)[0];
  const actual = current[index].data[key];
  if (actual === desired || (endpoint.endsWith('_country') && /^PK:[A-Z0-9_-]+$/i.test(actual))) {
    console.log(`KEEP ${label}`);
    continue;
  }
  if (!apply) { console.log(`WOULD CHANGE ${label}`); continue; }
  const response = await apiRequest(endpoint, { method: 'PUT', auth: 'wc', requireAuth: true, body });
  if (response.data?.[key] !== desired) throw new Error(`WooCommerce did not confirm this setting: ${label}`);
  console.log(`SET ${label} (HTTP ${response.status})`);
}

