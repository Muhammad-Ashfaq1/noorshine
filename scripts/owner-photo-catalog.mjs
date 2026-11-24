import fs from 'node:fs/promises';
import path from 'node:path';
import { execFileSync } from 'node:child_process';
import { apiRequest, config, getAll } from './lib/api-client.mjs';

const mode = process.argv[2] ?? 'check';
if (!['prepare', 'check', 'apply'].includes(mode)) throw new Error('Use prepare, check, or apply.');
const enableCatalogPreview = process.argv.includes('--enable-catalog-preview');
const directory = process.env.NOORSHINE_OWNER_EXPORT_DIR ?? (mode === 'prepare' ? 'dist/owner-catalog' : 'assets/storefront');
const hero = {
  slug: 'noorshine-owner-showroom-banner',
  name: 'NoorShine wall art showroom banner',
  alt: 'Black and gold wall clock, framed Islamic calligraphy and green wall art on a warm interior wall'
};
const products = (await fs.readFile('data/owner-photo-catalog.tsv', 'utf8')).trimEnd().split(/\r?\n/).slice(1).map(line => {
  const [sku, category, source, crop, price, name, summary, description, alt, reference] = line.split('\t');
  if (!/^NS-\d{3}$/.test(sku) || !/^[1-9]\d+$/.test(price) || !name || !alt || !reference) {
    throw new Error('Invalid owner-photo catalog row.');
  }
  const externalId = sku === 'NS-101' ? 'noorshine-owner-photo-pilot-ns-101' : `noorshine-owner-photo-${sku.toLowerCase()}`;
  return { sku, category, source, crop, price, name, summary, description, alt, reference, externalId, slug: `noorshine-${sku.toLowerCase()}-owner-photo` };
});
const assets = [hero, ...products];
const fileFor = asset => path.join(directory, asset.slug + '.webp');

if (mode === 'prepare') {
  const bannerFile = process.argv[3];
  if (!bannerFile) throw new Error('Supply the clean owner banner image as the third argument.');
  await fs.mkdir(directory, { recursive: true });
  execFileSync('cwebp', ['-quiet', '-q', '88', bannerFile, '-o', fileFor(hero)]);
  const sourceDirectory = process.env.NOORSHINE_OWNER_SOURCE_DIR ?? 'assets/captured-images';
  const photographed = products.filter(item => item.source);
  for (const row of photographed) {
    const input = path.join(sourceDirectory, `WhatsApp Image 2026-11-20 at ${row.source}.jpeg`);
    const dimensions = execFileSync('sips', ['-g', 'pixelWidth', '-g', 'pixelHeight', input], { encoding: 'utf8' });
    const [, sourceWidth, sourceHeight] = dimensions.match(/pixelWidth: (\d+)\s+pixelHeight: (\d+)/);
    const [x, y, right, bottom] = row.crop.split(',').map((value, index) =>
      Math.round(Number(value) * Number(index % 2 === 0 ? sourceWidth : sourceHeight)));
    const width = right - x;
    const height = bottom - y;
    if (width < 400 || height < 400 || right > Number(sourceWidth) || bottom > Number(sourceHeight)) {
      throw new Error(`Invalid photo crop for ${row.sku}.`);
    }
    const resize = width > height ? ['1350', '0'] : ['0', '1350'];
    execFileSync('cwebp', [
      '-quiet', '-crop', String(x), String(y), String(width), String(height),
      '-resize', ...resize, '-q', '89', input, '-o', fileFor(row)
    ]);
  }
  console.log(`Prepared ${photographed.length + 1} WebP files in ${directory}.`);
  process.exit(0);
}

if (config.baseUrl !== 'https://noorshine.pk') throw new Error('Unexpected deploy destination.');
const wp = { auth: 'wp', requireAuth: true };
const wc = { auth: 'wc', requireAuth: true };
const mediaPath = '/wp-json/wp/v2/media';
const productPath = '/wp-json/wc/v3/products';
const categoryPath = productPath + '/categories';
const pagePath = '/wp-json/wp/v2/pages';
const expectedPlaceholderSlugs = new Set([
  'noorshine-phase4-hero', 'noorshine-ns-025-arch-clock', 'noorshine-ns-027-meridian',
  'noorshine-ns-027-detail', 'noorshine-ns-028-arc-clock', 'noorshine-ns-029-dune',
  'noorshine-ns-030-grid', 'noorshine-ns-031-botanical', 'noorshine-ns-032-star',
  'noorshine-ns-033-rosette', 'noorshine-ns-034-triptych', 'noorshine-ns-035-landscape',
  'noorshine-ns-036-faceted', 'noorshine-ns-039-moon'
]);
const thumbnails = new Map([
  ['islamic-wall-art', 'NS-103'], ['ayatul-kursi-wall-art', 'NS-103'],
  ['quranic-surah-wall-art', 'NS-102'], ['allah-muhammad-calligraphy', 'NS-110'],
  ['bismillah-alhamdulillah-art', 'NS-107'], ['arabic-calligraphy', 'NS-105'],
  ['islamic-wall-clocks', 'NS-113'], ['modern-wall-clocks', 'NS-104'],
  ['cnc-geometric-wall-panels', 'NS-119']
]);

const [inventory, current, categories, pages, settings] = await Promise.all([
  getAll(mediaPath, { context: 'edit', _fields: 'id,slug,alt_text,mime_type,source_url' }, wp),
  getAll(productPath, { status: 'any', _fields: 'id,sku,status,type,stock_status,catalog_visibility,regular_price,images,meta_data' }, wc),
  getAll(categoryPath, { hide_empty: 'false', _fields: 'id,slug,image' }, wc),
  getAll(pagePath, { slug: 'home', status: 'publish', _fields: 'id,featured_media' }, wp),
  apiRequest('/wp-json/wp/v2/settings?_fields=page_on_front,show_on_front', wp)
]);
const listedProducts = new Map(current.map(product => [product.sku.toUpperCase(), product]));
const listedCategories = new Map(categories.map(category => [category.slug, category]));
const mediaBySlug = new Map(inventory.map(item => [item.slug, item]));
const placeholders = new Set(inventory.filter(item => expectedPlaceholderSlugs.has(item.slug)).map(item => item.id));
const home = pages.find(page => page.id === settings.data.page_on_front);
if (!home || settings.data.show_on_front !== 'page') throw new Error('The expected Home page is not assigned.');
const currentHero = mediaBySlug.get(hero.slug);
if (home.featured_media && home.featured_media !== currentHero?.id && !placeholders.has(home.featured_media)) {
  throw new Error('The homepage already has a different owner image. Review before replacing it.');
}
for (const row of products) {
  if (!listedCategories.has(row.category)) throw new Error(`Unknown category ${row.category}.`);
  const existing = listedProducts.get(row.sku);
  if (!existing) {
    if (row.sku === 'NS-101') throw new Error('The approved pilot product is missing.');
    continue;
  }
  const identity = existing.meta_data.find(item => item.key === '_noorshine_external_id');
  if (identity?.value !== row.externalId || existing.type !== 'simple' ||
      existing.status !== 'draft' || existing.catalog_visibility !== 'hidden' || existing.stock_status !== 'outofstock') {
    throw new Error(`Unexpected existing product for ${row.sku}. The catalog was not changed.`);
  }
}
const cleanup = current.filter(product => product.images.some(image => placeholders.has(image.id)));
if (cleanup.some(product => product.status !== 'draft' || product.catalog_visibility !== 'hidden')) {
  throw new Error('A placeholder is used on a non-draft product. Review that product before removing it.');
}
const needUpload = assets.filter(asset => !mediaBySlug.has(asset.slug));
for (const asset of needUpload) await fs.access(fileFor(asset));
console.log(`${mode}: ${products.length} owner-photo products, ${needUpload.length} new images, ${cleanup.length} draft galleries to clean.`);
if (mode === 'check') process.exit(0);

async function inGroups(items, task) {
  for (let start = 0; start < items.length; start += 3) {
    const results = await Promise.allSettled(items.slice(start, start + 3).map(task));
    const errors = results.filter(result => result.status === 'rejected');
    if (errors.length) throw new Error(errors.map(error => error.reason.message).join('\n'));
  }
}

await inGroups(needUpload, async asset => {
  const bytes = await fs.readFile(fileFor(asset));
  const form = new FormData();
  form.set('file', new Blob([bytes], { type: 'image/webp' }), asset.slug + '.webp');
  form.set('slug', asset.slug);
  form.set('title', asset.name);
  form.set('alt_text', asset.alt);
  const { data } = await apiRequest(mediaPath, { ...wp, method: 'POST', rawBody: form });
  if (!data.id || data.slug !== asset.slug || data.mime_type !== 'image/webp') {
    throw new Error(`Unexpected result uploading ${asset.slug}.`);
  }
  mediaBySlug.set(asset.slug, data);
});
await inGroups([hero, ...products], async asset => {
  const image = mediaBySlug.get(asset.slug);
  const url = image && new URL(image.source_url);
  if (!image?.id || url.origin !== config.baseUrl || image.mime_type !== 'image/webp') {
    throw new Error(`Invalid media for ${asset.slug}.`);
  }
  const response = await fetch(url, { method: 'HEAD', signal: AbortSignal.timeout(15000) });
  if (!response.ok || !response.headers.get('content-type')?.startsWith('image/webp')) {
    throw new Error(`The photo URL did not load for ${asset.slug}.`);
  }
});
await inGroups(products, async row => {
  const existing = listedProducts.get(row.sku);
  const category = listedCategories.get(row.category);
  const image = mediaBySlug.get(row.slug);
  const images = [{ id: image.id }, ...(existing?.images ?? [])
    .filter(item => item.id !== image.id && !placeholders.has(item.id)).map(item => ({ id: item.id }))];
  const payload = {
    name: row.name, short_description: row.summary, description: row.description,
    regular_price: row.price, images,
    meta_data: [
      { key: '_noorshine_external_id', value: row.externalId },
      { key: '_noorshine_meta_description', value: row.summary },
      { key: '_noorshine_price_reference', value: row.reference }
    ]
  };
  if (enableCatalogPreview) {
    payload.meta_data.push({ key: '_noorshine_catalog_preview', value: 'yes' });
  }
  if (!existing) {
    Object.assign(payload, {
      sku: row.sku, type: 'simple', status: 'draft', catalog_visibility: 'hidden',
      stock_status: 'outofstock', categories: [{ id: category.id }]
    });
  }
  const { data } = await apiRequest(existing ? `${productPath}/${existing.id}` : productPath, {
    ...wc, method: existing ? 'PUT' : 'POST', body: payload
  });
  if (data.sku !== row.sku || data.regular_price !== row.price || data.status !== 'draft' ||
      data.catalog_visibility !== 'hidden' || data.images[0]?.id !== image.id ||
      data.stock_status !== 'outofstock' || !data.description.includes(row.description) ||
      (enableCatalogPreview && !data.meta_data.some(item => item.key === '_noorshine_catalog_preview' && item.value === 'yes'))) {
    throw new Error(`Unexpected draft readback for ${row.sku}.`);
  }
  console.log(`${row.sku} (#${data.id}) • PKR ${row.price}`);
});
const heroId = mediaBySlug.get(hero.slug).id;
if (home.featured_media !== heroId) {
  const { data } = await apiRequest(`${pagePath}/${home.id}`, { ...wp, method: 'POST', body: { featured_media: heroId } });
  if (data.featured_media !== heroId) throw new Error('Homepage banner did not save.');
}
await inGroups(categories, async category => {
  if (category.image?.id && !placeholders.has(category.image.id)) return;
  const sku = thumbnails.get(category.slug);
  const id = sku ? mediaBySlug.get(`noorshine-${sku.toLowerCase()}-owner-photo`).id : 0;
  if (!id && !category.image?.id) return;
  const { data } = await apiRequest(`${categoryPath}/${category.id}`, { ...wc, method: 'PUT', body: { image: { id } } });
  if ((data.image?.id ?? 0) !== id) throw new Error(`Category image did not save for ${category.slug}.`);
});
await inGroups(cleanup.filter(product => !products.some(row => row.sku === product.sku)), async product => {
  const images = product.images.filter(image => !placeholders.has(image.id)).map(image => ({ id: image.id }));
  const { data } = await apiRequest(`${productPath}/${product.id}`, { ...wc, method: 'PUT', body: { images } });
  if (data.images.some(image => placeholders.has(image.id))) throw new Error(`Placeholder still attached to ${product.sku}.`);
});
console.log(`Homepage banner #${heroId} assigned, owner thumbnails set, and old placeholders detached.`);
