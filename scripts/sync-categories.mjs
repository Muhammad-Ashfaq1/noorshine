import { apiRequest, getAll } from './lib/api-client.mjs';
import { parseArgs, requireWriteApproval } from './lib/config.mjs';
import { readJson } from './lib/catalog.mjs';

const args = parseArgs();
const apply = args.has('apply');
if (apply) requireWriteApproval(args);
const desired = readJson('data/categories.json');
let existing = [];
try {
  if (!args.has('offline')) existing = await getAll('/wp-json/wc/v3/products/categories', { hide_empty: 'false' }, { auth: 'wc', requireAuth: true });
}
catch (error) { console.error(`Cannot read WooCommerce categories: ${error.message}`); process.exit(1); }

const bySlug = new Map(existing.map(category => [category.slug, category]));
if (apply) {
  const conflicts = desired.filter(category => {
    const current = bySlug.get(category.slug);
    const parent = bySlug.get(category.parent);
    return current && (category.parent ? !parent || current.parent !== parent.id : current.parent !== 0);
  });
  if (conflicts.length) throw new Error(`Existing category hierarchy differs for ${conflicts.map(category => category.slug).join(', ')}. Resolve it before applying.`);
}
for (const category of desired) {
  const current = bySlug.get(category.slug);
  const parent = category.parent ? bySlug.get(category.parent) : null;
  if (category.parent && !parent) throw new Error(`Parent ${category.parent} must be synced before ${category.slug}`);
  const payload = { name: category.name, slug: category.slug, description: category.description, parent: parent?.id || 0 };
  if (current) {
    console.log(`PRESERVE ${category.slug} (#${current.id})${current.parent !== payload.parent ? ' — parent conflict' : ''}`);
    continue;
  }
  if (!apply) {
    console.log(`CREATE ${category.slug} (${args.has('offline') ? 'offline plan' : 'dry run'})`);
    bySlug.set(category.slug, { ...payload, id: `dry-${category.slug}` });
    continue;
  }
  const response = await apiRequest('/wp-json/wc/v3/products/categories', {
    method: 'POST', auth: 'wc', requireAuth: true, body: payload
  });
  if (!Number.isInteger(response.data?.id) || response.data.id <= 0 || response.data.slug !== category.slug || response.data.parent !== payload.parent) {
    throw new Error(`${category.slug}: unexpected response identity or parent. Inspect WooCommerce before rerunning.`);
  }
  bySlug.set(category.slug, response.data);
  console.log(`Created ${category.slug} (#${response.data.id}, HTTP ${response.status})`);
}

