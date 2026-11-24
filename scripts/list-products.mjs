import { getAll } from './lib/api-client.mjs';
const products = await getAll('/wp-json/wc/v3/products', { status: 'any' }, { auth: 'wc', requireAuth: true });
for (const product of products) console.log(`${product.id}\t${product.sku || '-'}\t${product.status}\t${product.name}`);
console.log(`Total: ${products.length}`);

