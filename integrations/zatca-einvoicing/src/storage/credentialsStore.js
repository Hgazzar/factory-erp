import { mkdir, writeFile, rename } from 'node:fs/promises';
import { dirname } from 'node:path';

/**
 * Normalized compliance onboarding payload for downstream signing & reporting.
 * binarySecurityToken is typically Base64-encoded X.509 (DER) or PEM from ZATCA.
 *
 * @typedef {object} ComplianceCredentialsRecord
 * @property {string} schemaVersion
 * @property {string} environment
 * @property {string} savedAt - ISO-8601
 * @property {string|null} requestId
 * @property {string|null} csid - Cryptographic Stamp ID when returned explicitly
 * @property {string} secret - API shared secret (store encrypted at rest in production KMS)
 * @property {string} binarySecurityToken - Compliance certificate material (Base64 or PEM)
 * @property {object} [metadata] - Non-sensitive subset of raw response keys for audit
 */

/**
 * Strip high-risk fields from a copy of API body for audit logging only.
 * @param {object} raw
 */
export function sanitizeResponseForMetadata(raw) {
  if (!raw || typeof raw !== 'object') {
    return {};
  }
  const keys = Object.keys(raw).filter(
    (k) => !['secret', 'binarySecurityToken', 'BinarySecurityToken'].includes(k),
  );
  /** @type {Record<string, unknown>} */
  const out = {};
  for (const k of keys.slice(0, 40)) {
    const v = raw[k];
    if (typeof v === 'string') {
      out[k] = v.length > 200 ? `${v.slice(0, 200)}…` : v;
    } else if (typeof v === 'number' || typeof v === 'boolean' || v === null) {
      out[k] = v;
    }
  }
  return out;
}

/**
 * Map ZATCA (or gateway) JSON to our persisted shape. Accepts common casings.
 * @param {object} data
 * @param {string} environment
 * @returns {ComplianceCredentialsRecord}
 */
export function normalizeComplianceResponse(data, environment = 'sandbox') {
  const requestId =
    pickString(data, ['requestID', 'requestId', 'RequestID', 'compliance_request_id']) ?? null;
  const secret = pickString(data, ['secret', 'Secret']);
  const binarySecurityToken = pickString(data, ['binarySecurityToken', 'BinarySecurityToken']);
  const csid = pickString(data, ['csid', 'CSID', 'cryptographicStampId']) ?? null;

  if (!binarySecurityToken) {
    throw new Error('Compliance response missing binarySecurityToken.');
  }
  if (!secret) {
    throw new Error('Compliance response missing secret.');
  }

  return {
    schemaVersion: '1.0',
    environment,
    savedAt: new Date().toISOString(),
    requestId,
    csid,
    secret,
    binarySecurityToken,
    metadata: sanitizeResponseForMetadata(data),
  };
}

/**
 * @param {object} obj
 * @param {string[]} keys
 */
function pickString(obj, keys) {
  for (const k of keys) {
    const v = obj[k];
    if (typeof v === 'string' && v.trim() !== '') {
      return v.trim();
    }
  }
  return null;
}

/**
 * Atomic write: temp file then rename.
 * @param {string} absolutePath
 * @param {ComplianceCredentialsRecord} record
 */
export async function saveComplianceCredentials(absolutePath, record) {
  await mkdir(dirname(absolutePath), { recursive: true });
  const tmp = `${absolutePath}.${process.pid}.tmp`;
  const json = `${JSON.stringify(record, null, 2)}\n`;
  await writeFile(tmp, json, { encoding: 'utf8', mode: 0o600 });
  await rename(tmp, absolutePath);
}
