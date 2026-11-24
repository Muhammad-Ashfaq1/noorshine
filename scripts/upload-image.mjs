import fs from 'node:fs';
import path from 'node:path';
import { apiRequest } from './lib/api-client.mjs';
import { parseArgs, requireWriteApproval } from './lib/config.mjs';
const args = parseArgs();
const apply = args.has('apply');
if (apply) requireWriteApproval(args);
const file = args.get('file');
const alt = args.get('alt');
if (!file || !alt) throw new Error('Usage: --file image.webp --alt "Accurate alt text" --apply --backup-confirmed');
const fileTypes = { '.jpg': 'image/jpeg', '.jpeg': 'image/jpeg', '.png': 'image/png', '.webp': 'image/webp' };
const type = fileTypes[path.extname(file).toLowerCase()];
if (!type || !fs.statSync(file).isFile()) throw new Error('Supply an authorized JPG, PNG or WebP file.');
if (!apply) {
  console.log('WOULD UPLOAD authorized media with alt text.');
} else {
  const form = new FormData();
  form.set('file', new Blob([fs.readFileSync(file)], { type }), path.basename(file));
  form.set('alt_text', alt);
  const result = await apiRequest('/wp-json/wp/v2/media', { method: 'POST', auth: 'wp', rawBody: form });
  if (!Number.isInteger(result.data?.id) || result.data.alt_text !== alt) throw new Error('Verify the new media record and alt text in WordPress before retrying.');
  console.log(`Uploaded authorized media #${result.data.id} (HTTP ${result.status})`);
}

