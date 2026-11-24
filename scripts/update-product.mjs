import fs from 'node:fs';
import { apiRequest, getAll } from './lib/api-client.mjs';
import { parseArgs, requireWriteApproval } from './lib/config.mjs';
const args = parseArgs();
const apply = args.has('apply');
if (apply) requireWriteApproval(args);
const sku = args.get('sku');
const file = args.get('file');
if (!sku || !file) throw new Error('Usage: --sku NS-001 --file update.json --apply --backup-confirmed');
const matches = await getAll('/wp-json/wc/v3/products', { sku }, { auth: 'wc', requireAuth: true });
if (matches.length !== 1 || matches[0].sku !== sku) throw new Error(`Expected one exact product for SKU ${sku}, found ${matches.length}`);
const body = JSON.parse(fs.readFileSync(file, 'utf8'));
if (!body || typeof body !== 'object' || Array.isArray(body)) throw new Error('Product update file must contain a JSON object.');
delete body.id; delete body.sku; delete body.status;
if (!apply) {
  console.log(`WOULD UPDATE ${sku} (#${matches[0].id}) with the supplied fields.`);
  process.exit(0);
}
const response = await apiRequest(`/wp-json/wc/v3/products/${matches[0].id}`, { method: 'PUT', auth: 'wc', requireAuth: true, body });
if (response.data?.sku !== sku || response.data.status !== matches[0].status) throw new Error(`${sku}: unexpected update response. Inspect WooCommerce before retrying.`);
console.log(`Updated ${response.data.sku} (#${response.data.id}); status remains ${response.data.status}`);

