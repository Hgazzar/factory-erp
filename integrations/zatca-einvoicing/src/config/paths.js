import { dirname, join, resolve } from 'node:path';
import { fileURLToPath } from 'node:url';

/**
 * Absolute path to `integrations/zatca-einvoicing/` — stable regardless of `process.cwd()`
 * (repo root, package dir, or CI workspace).
 */
const __dirname = dirname(fileURLToPath(import.meta.url));

export const ZATCA_PACKAGE_ROOT = resolve(join(__dirname, '..', '..'));
