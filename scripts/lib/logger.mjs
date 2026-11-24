import fs from 'node:fs';
import path from 'node:path';
import { ensureDirectory } from './config.mjs';

const secretPattern = /(ck_[a-z0-9]+|cs_[a-z0-9]+|authorization:\s*basic\s+\S+|application[_ -]?password\s*[=:]\s*\S+)/gi;

export function redact(value) {
  return String(value).replace(secretPattern, '[REDACTED]');
}

export function createLogger(name) {
  ensureDirectory('logs');
  const file = path.resolve('logs', `${name}.log`);
  return (level, message, details = undefined) => {
    const entry = {
      timestamp: new Date().toISOString(),
      level,
      message: redact(message),
      ...(details === undefined ? {} : { details: JSON.parse(redact(JSON.stringify(details))) })
    };
    const line = JSON.stringify(entry);
    fs.appendFileSync(file, `${line}\n`, { mode: 0o600 });
    const stream = level === 'error' ? process.stderr : process.stdout;
    stream.write(`${entry.timestamp} ${level.toUpperCase()} ${entry.message}\n`);
  };
}

