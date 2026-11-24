import { apiRequest, getAll } from './lib/api-client.mjs';
import { parseArgs, requireWriteApproval } from './lib/config.mjs';
import { readJson } from './lib/catalog.mjs';
const args = parseArgs();
const apply = args.has('apply');
if (apply) requireWriteApproval(args);
const pages = readJson('data/pages.json');
const existing = args.has('offline') ? [] : await getAll('/wp-json/wp/v2/pages', { status: 'any', context: 'edit' }, { auth: 'wp', requireAuth: true });
const bySlug = new Map(existing.map(page => [page.slug, page]));
for (const page of pages) {
  const current = bySlug.get(page.slug);
  if (current) { console.log(`PRESERVE ${page.slug} (#${current.id})`); continue; }
  const payload = { title: page.title, slug: page.slug, content: page.content, status: 'draft' };
  if (!apply) { console.log(`CREATE ${page.slug} (draft, ${args.has('offline') ? 'offline plan' : 'dry run'})`); continue; }
  const response = await apiRequest('/wp-json/wp/v2/pages', { method: 'POST', auth: 'wp', requireAuth: true, body: payload });
  if (response.data?.status !== 'draft') throw new Error(`${page.slug}: expected a draft page. Inspect WordPress before retrying.`);
  console.log(`Created ${page.slug} (#${response.data.id}, draft, HTTP ${response.status})`);
}

