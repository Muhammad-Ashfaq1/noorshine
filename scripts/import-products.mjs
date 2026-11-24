import { apiRequest, getAll } from './lib/api-client.mjs';
import { parseArgs, requireWriteApproval } from './lib/config.mjs';
import { readJson, validateCatalog } from './lib/catalog.mjs';

const args = parseArgs();
const apply = args.has('apply');
if (apply) requireWriteApproval(args);
const offline = args.has('offline');
const products = readJson('data/products.json');
const errors = validateCatalog(products);
if (errors.length) throw new Error(`Catalog validation failed:\n${errors.join('\n')}`);

const readOptions = { auth: 'wc', requireAuth: true };
const [categories, remoteProducts, tags] = offline ? [
  readJson('data/categories.json').map((category, index) => ({ ...category, id: index + 1 })),
  [],
  []
] : await Promise.all([
  getAll('/wp-json/wc/v3/products/categories', { hide_empty: 'false' }, readOptions),
  getAll('/wp-json/wc/v3/products', { status: 'any' }, readOptions),
  getAll('/wp-json/wc/v3/products/tags', { hide_empty: 'false' }, readOptions)
]);
const categoryIds = new Map(categories.map(category => [category.slug, category.id]));
const tagsByName = new Map(tags.map(tag => [tag.name.toLowerCase(), tag.id]));
const externalId = product => product.meta_data?.find(meta => meta.key === '_noorshine_external_id')?.value;

const pending = [];
for (const product of products) {
  const matches = remoteProducts.filter(current => current.sku?.toUpperCase() === product.sku.toUpperCase() || externalId(current) === product.external_id || current.slug === product.slug);
  if (matches.length) {
    if (matches.length !== 1 || matches[0].sku !== product.sku || externalId(matches[0]) !== product.external_id) {
      throw new Error(`${product.sku}: existing SKU, external ID or slug conflicts with the catalog. Resolve the match before importing.`);
    }
    console.log(`PRESERVE ${product.sku} (#${matches[0].id}, ${matches[0].status})`);
    continue;
  }
  const missing = product.categories.filter(slug => !categoryIds.has(slug));
  if (apply && missing.length) throw new Error(`${product.sku}: sync missing categories first: ${missing.join(', ')}`);
  pending.push({ product, missing });
}

const batchSize = 5;
for (let index = 0; index < pending.length; index += 1) {
  if (index % batchSize === 0) {
    console.log(`Batch ${Math.floor(index / batchSize) + 1}/${Math.ceil(pending.length / batchSize)}`);
    if (apply && index > 0) await new Promise(resolve => setTimeout(resolve, 500));
  }
  const { product, missing } = pending[index];
  if (!apply) {
    console.log(`CREATE ${product.sku} (draft, ${offline ? 'offline plan' : 'dry run'})${missing.length ? `; needs categories ${missing.join(', ')}` : ''}`);
    continue;
  }
  const productTags = [];
  for (const name of product.tags) {
    const key = name.toLowerCase();
    if (!tagsByName.has(key)) {
      const response = await apiRequest('/wp-json/wc/v3/products/tags', { method: 'POST', body: { name }, ...readOptions });
      if (!Number.isInteger(response.data?.id) || response.data.id <= 0) throw new Error(`${product.sku}: tag creation returned an invalid ID. Inspect WooCommerce before rerunning.`);
      tagsByName.set(key, response.data.id);
      console.log(`Created product tag #${response.data.id} (HTTP ${response.status})`);
    }
    productTags.push({ id: tagsByName.get(key) });
  }
  const payload = {
    name: product.name,
    slug: product.slug,
    sku: product.sku,
    type: product.type,
    status: 'draft',
    catalog_visibility: 'hidden',
    description: product.description,
    short_description: product.short_description,
    categories: product.categories.map(slug => ({ id: categoryIds.get(slug) })),
    stock_status: 'outofstock',
    tags: productTags,
    attributes: [
      { name: 'Material', visible: true, variation: product.type === 'variable', options: product.materials },
      { name: 'Finish', visible: true, variation: product.type === 'variable', options: product.finishes },
      { name: 'Size', visible: true, variation: product.type === 'variable', options: product.sizes }
    ],
    meta_data: [
      { key: '_noorshine_external_id', value: product.external_id },
      { key: '_noorshine_production_status', value: product.production_status },
      { key: '_noorshine_price_status', value: product.price_status },
      { key: '_noorshine_designer_enabled', value: product.customization.eligible ? 'yes' : 'no' },
      { key: '_noorshine_verification', value: JSON.stringify(product.verification) },
      { key: '_noorshine_seo_title', value: product.seo.title },
      { key: '_noorshine_meta_description', value: product.seo.meta_description },
      { key: '_noorshine_image_requirements', value: JSON.stringify(product.images) }
    ]
  };
  const response = await apiRequest('/wp-json/wc/v3/products', { method: 'POST', ...readOptions, body: payload }).catch(error => {
    throw new Error(`${product.sku}: ${error.message}`);
  });
  if (!Number.isInteger(response.data?.id) || response.data.id <= 0 || response.data.sku !== product.sku || response.data.status !== 'draft' || response.data.catalog_visibility !== 'hidden' || externalId(response.data) !== product.external_id) {
    throw new Error(`${product.sku}: WooCommerce returned an unexpected identity, status or visibility. Inspect the record before rerunning.`);
  }
  console.log(`Created ${product.sku} (#${response.data.id}, draft, HTTP ${response.status})`);
}

