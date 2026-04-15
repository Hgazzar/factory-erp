import { readFile } from 'node:fs/promises';
import { join, isAbsolute } from 'node:path';

/**
 * Normalize PEM: trim, ensure line endings, validate CSR markers.
 * @param {string} pem
 * @param {string} sourceLabel - Non-sensitive label for errors (e.g. "file", "env", "database")
 */
export function assertValidCsrPem(pem, sourceLabel = 'csr') {
  const t = typeof pem === 'string' ? pem.trim() : '';
  if (!t) {
    throw new Error(`CSR is empty (source: ${sourceLabel}).`);
  }
  if (!t.includes('BEGIN CERTIFICATE REQUEST')) {
    throw new Error(`CSR must be PEM PKCS#10 (BEGIN CERTIFICATE REQUEST) — source: ${sourceLabel}.`);
  }
  if (!t.includes('END CERTIFICATE REQUEST')) {
    throw new Error(`CSR PEM appears truncated (missing END CERTIFICATE REQUEST) — source: ${sourceLabel}.`);
  }
  return t.endsWith('\n') ? t : `${t}\n`;
}

/**
 * Read CSR from inline environment PEM (highest priority when set).
 * @param {string} csrPemEnv
 */
export function readCsrFromEnv(csrPemEnv) {
  if (!csrPemEnv) {
    return null;
  }
  const normalized = csrPemEnv.replace(/\\n/g, '\n').trim();
  return assertValidCsrPem(normalized, 'ZATCA_CSR_PEM');
}

/**
 * Read CSR from disk — path may be absolute or relative to Laravel storage/app.
 * @param {string} storageAppRoot - Resolved absolute path to storage/app
 * @param {string} relativeOrAbsolutePath - e.g. zatca/einvoice-settings/1/zatca.csr
 */
export async function readCsrFromFile(storageAppRoot, relativeOrAbsolutePath) {
  if (!relativeOrAbsolutePath) {
    throw new Error('CSR file path is empty.');
  }
  const full = isAbsolute(relativeOrAbsolutePath)
    ? relativeOrAbsolutePath
    : join(storageAppRoot, relativeOrAbsolutePath);
  let raw;
  try {
    raw = await readFile(full, 'utf8');
  } catch (e) {
    const code = /** @type {NodeJS.ErrnoException} */ (e).code;
    throw new Error(`Cannot read CSR file (${code ?? 'unknown'}): path derived from storage root + csr path.`);
  }
  return assertValidCsrPem(raw, 'file');
}

/**
 * Load csr_path from einvoice_settings (PostgreSQL, same schema as Laravel).
 * Does not log connection string or query results containing secrets.
 * @param {string} databaseUrl
 */
export async function fetchCsrRelativePathFromDatabase(databaseUrl) {
  const { default: pg } = await import('pg');
  const client = new pg.Client({ connectionString: databaseUrl });
  await client.connect();
  try {
    const { rows } = await client.query(
      `SELECT id, csr_path
       FROM einvoice_settings
       WHERE csr_path IS NOT NULL AND btrim(csr_path) <> ''
       ORDER BY id ASC
       LIMIT 1`,
    );
    if (!rows.length || !rows[0].csr_path) {
      throw new Error('No einvoice_settings row with non-empty csr_path found.');
    }
    return { id: rows[0].id, csrPath: String(rows[0].csr_path).trim() };
  } finally {
    await client.end().catch(() => {});
  }
}

/**
 * Resolve CSR PEM using config precedence: env PEM > file > database path + file.
 * @param {object} cfg
 * @param {string} cfg.csrPemEnv
 * @param {string} cfg.csrFilePath
 * @param {boolean} cfg.useDatabaseCsr
 * @param {string} cfg.databaseUrl
 * @param {string} cfg.storageAppRoot
 */
export async function resolveCsrPem(cfg) {
  const fromEnv = readCsrFromEnv(cfg.csrPemEnv);
  if (fromEnv) {
    return { pem: fromEnv, source: 'ZATCA_CSR_PEM' };
  }
  if (cfg.useDatabaseCsr) {
    const { csrPath } = await fetchCsrRelativePathFromDatabase(cfg.databaseUrl);
    const pem = await readCsrFromFile(cfg.storageAppRoot, csrPath);
    return { pem, source: 'database_csr_path+file' };
  }
  if (cfg.csrFilePath) {
    const pem = await readCsrFromFile(cfg.storageAppRoot, cfg.csrFilePath);
    return { pem, source: 'ZATCA_CSR_FILE_PATH' };
  }
  throw new Error('No CSR resolution strategy matched (env / file / database).');
}
