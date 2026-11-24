import fs from 'node:fs';

export function readJson(file) {
  return JSON.parse(fs.readFileSync(file, 'utf8'));
}

export function validateCatalog(products) {
  if (!Array.isArray(products)) return ['Catalog must be a JSON array'];
  const errors = [];
  const skus = new Set();
  const identities = new Set();
  const slugs = new Set();
  const required = ['external_id', 'sku', 'name', 'slug', 'status', 'categories', 'short_description', 'description', 'materials', 'finishes', 'sizes', 'seo'];
  products.forEach((product, index) => {
    if (!product || typeof product !== 'object') {
      errors.push(`Product ${index + 1}: invalid record`);
      return;
    }
    for (const field of required) if (!product[field] || (Array.isArray(product[field]) && product[field].length === 0)) errors.push(`Product ${index + 1}: missing ${field}`);
    if (product.status !== 'draft') errors.push(`${product.sku}: status must be draft`);
    if (skus.has(product.sku)) errors.push(`${product.sku}: duplicate SKU`);
    skus.add(product.sku);
    for (const [key, seen] of [['external_id', identities], ['slug', slugs]]) {
      if (seen.has(product[key])) errors.push(`${product.sku}: duplicate ${key}`);
      seen.add(product[key]);
    }
    if (!['simple', 'variable'].includes(product.type)) errors.push(`${product.sku}: invalid product type`);
    if (product.catalog_visibility !== 'hidden') errors.push(`${product.sku}: expected hidden visibility`);
    if (product.dimensions !== null || product.stock_quantity != null) errors.push(`${product.sku}: dimensions and stock must be unset before approval`);
    for (const key of ['categories', 'tags', 'materials', 'finishes', 'sizes']) {
      if (!Array.isArray(product[key]) || !product[key].length || product[key].some(value => typeof value !== 'string' || !value.trim())) {
        errors.push(`${product.sku}: invalid ${key}`);
      }
    }
    if (typeof product.customization?.eligible !== 'boolean' || !product.seo?.title || !product.seo?.meta_description) errors.push(`${product.sku}: customization and SEO fields are required`);
    if (product.selling_price !== null || product.manufacturing_cost !== null) errors.push(`${product.sku}: price/cost must remain null before approval`);
    if (product.weight !== null) errors.push(`${product.sku}: unverified weight must be null`);
    if (!product.verification || product.verification.production !== 'pending') errors.push(`${product.sku}: production verification must be pending`);
  });
  if (products.length !== 40) errors.push(`Expected 40 products, found ${products.length}`);
  return errors;
}

export function csvEscape(value) {
  const string = value === null || value === undefined ? '' : Array.isArray(value) || typeof value === 'object' ? JSON.stringify(value) : String(value);
  return `"${string.replaceAll('"', '""')}"`;
}

