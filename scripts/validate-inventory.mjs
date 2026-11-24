import { getAll } from './lib/api-client.mjs';
const products = await getAll('/wp-json/wc/v3/products', { status: 'any' }, { auth: 'wc', requireAuth: true });
const issues = [];
for (const product of products.filter(item => item.sku?.startsWith('NS-'))) {
  if (product.status !== 'draft') issues.push(`${product.sku}: expected draft, found ${product.status}`);
  if (product.price) issues.push(`${product.sku}: has a price before catalog approval (${product.price})`);
  if (product.manage_stock && product.stock_quantity !== null) issues.push(`${product.sku}: has unverified stock quantity ${product.stock_quantity}`);
}
if (issues.length) { console.error(issues.join('\n')); process.exit(1); }
console.log(`Inventory validation passed for ${products.filter(item => item.sku?.startsWith('NS-')).length} NoorShine products.`);

