import fs from 'node:fs';
import { getAll } from './lib/api-client.mjs';
import { ensureDirectory } from './lib/config.mjs';
const products = await getAll('/wp-json/wc/v3/products', { status: 'any' }, { auth: 'wc', requireAuth: true });
ensureDirectory('exports');
const file = `exports/products-${new Date().toISOString().slice(0, 10)}.json`;
fs.writeFileSync(file, `${JSON.stringify(products, null, 2)}\n`, { mode: 0o600 });
console.log(`Exported ${products.length} products to ${file}`);

