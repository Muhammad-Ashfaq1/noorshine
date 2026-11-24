import { readJson, validateCatalog } from './lib/catalog.mjs';

const categories = readJson('data/categories.json');
const products = readJson('data/products.json');
const errors = validateCatalog(products);
const slugs = new Set(categories.map(category => category.slug));
for (const category of categories) {
  if (category.parent && !slugs.has(category.parent)) errors.push(`Category ${category.slug}: unknown parent ${category.parent}`);
}
for (const product of products) {
  for (const category of product.categories) if (!slugs.has(category)) errors.push(`${product.sku}: unknown category ${category}`);
}
if (errors.length) {
  console.error(errors.map(error => `- ${error}`).join('\n'));
  process.exit(1);
}
const counts = products.reduce((result, product) => ({ ...result, [product.group]: (result[product.group] || 0) + 1 }), {});
console.log(`Catalog valid: ${products.length} products, ${categories.length} categories`);
console.log(counts);

