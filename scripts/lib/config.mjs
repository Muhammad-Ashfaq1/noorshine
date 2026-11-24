import fs from 'node:fs';
import path from 'node:path';

export function loadEnv(file = '.env') {
  if (fs.existsSync(file)) {
    for (const line of fs.readFileSync(file, 'utf8').split(/\r?\n/)) {
      const match = line.match(/^\s*([A-Z][A-Z0-9_]*)\s*=\s*(.*)\s*$/);
      if (!match || process.env[match[1]]) continue;
      let value = match[2];
      if ((value.startsWith('"') && value.endsWith('"')) || (value.startsWith("'") && value.endsWith("'"))) {
        value = value.slice(1, -1);
      }
      process.env[match[1]] = value;
    }
  }

  return {
    baseUrl: (process.env.WP_BASE_URL || 'https://noorshine.pk').replace(/\/$/, ''),
    wpUsername: process.env.WP_API_USERNAME || '',
    wpApplicationPassword: process.env.WP_APPLICATION_PASSWORD || '',
    wcKey: process.env.WC_CONSUMER_KEY || '',
    wcSecret: process.env.WC_CONSUMER_SECRET || '',
    timeoutMs: Number(process.env.API_TIMEOUT_MS || 20000),
    maxRetries: Number(process.env.API_MAX_RETRIES || 3)
  };
}

export function parseArgs(argv = process.argv.slice(2)) {
  const args = new Map();
  for (let index = 0; index < argv.length; index += 1) {
    const token = argv[index];
    if (!token.startsWith('--')) continue;
    const key = token.slice(2);
    const next = argv[index + 1];
    if (next && !next.startsWith('--')) {
      args.set(key, next);
      index += 1;
    } else {
      args.set(key, true);
    }
  }
  return args;
}

export function requireWriteApproval(args) {
  if (args.get('apply') !== true || args.get('backup-confirmed') !== true || args.has('dry-run') || args.has('offline')) {
    throw new Error('Live writes require both --apply and --backup-confirmed. Run without them for a dry run.');
  }
}

export function ensureDirectory(directory) {
  fs.mkdirSync(path.resolve(directory), { recursive: true });
}

