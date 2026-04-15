import axios from 'axios';
import { ZatcaApiError } from '../errors/ZatcaApiError.js';
import { ensureZatcaEnvLoaded } from '../config/loadEnv.js';

/**
 * Enterprise ZATCA HTTP layer — all outbound ZATCA calls go through here.
 * Rules: never log OTP, secret, private keys, or full CSR/base64 bodies.
 */

const DEFAULT_ACCEPT = 'application/json';

const DEFAULT_COMPLIANCE_BASE = 'https://sandbox.zatca.gov.sa/IntegrationSandbox';

/**
 * Derive axios `baseURL` and request path from config (supports legacy full URLs in .env).
 * @param {string} complianceUrlOrBase
 * @returns {{ baseURL: string, path: string }}
 */
export function resolveComplianceBaseUrlAndPath(complianceUrlOrBase) {
  let raw = String(complianceUrlOrBase ?? '').trim().replace(/\/+$/, '');
  if (!raw) {
    raw = DEFAULT_COMPLIANCE_BASE;
  }
  const lower = raw.toLowerCase();
  if (lower.endsWith('compliancecert-api')) {
    const base = raw.replace(/\/complianceCert-api\/?$/i, '');
    return { baseURL: base, path: '/complianceCert-api' };
  }
  if (lower.endsWith('/compliance')) {
    const base = raw.replace(/\/compliance\/?$/i, '');
    return { baseURL: base, path: '/compliance' };
  }
  return { baseURL: raw, path: '/complianceCert-api' };
}

/**
 * Retry helper for transient network / gateway failures.
 * @template T
 * @param {() => Promise<T>} fn
 * @param {number} [retries=2] attempts after first failure (matches 3 total tries when 2)
 * @param {number} [delay=2000] ms before first retry; doubles each retry
 */
export async function withRetry(fn, retries = 2, delay = 2000) {
  try {
    return await fn();
  } catch (err) {
    if (retries === 0) {
      throw err;
    }
    // eslint-disable-next-line no-console
    console.error(`[ZATCA] Retry request... remaining: ${retries}`);
    await new Promise((res) => {
      setTimeout(res, delay);
    });
    return withRetry(fn, retries - 1, delay * 2);
  }
}

/**
 * Build headers for Compliance CSID / compliance certificate issuance (sandbox or gateway).
 * @param {object} opts
 * @param {string} opts.otp - Plain OTP (only sent over HTTPS, never logged)
 * @param {string} [opts.acceptVersion] - default V2
 */
export function buildComplianceCsidHeaders({ otp, acceptVersion = 'V2' }) {
  if (!otp || typeof otp !== 'string') {
    throw new Error('OTP is required for compliance CSID request.');
  }
  return {
    'Content-Type': 'application/json',
    Accept: DEFAULT_ACCEPT,
    'Accept-Version': acceptVersion,
    OTP: otp.trim(),
  };
}

/**
 * Encode CSR PEM to Base64 (UTF-8 bytes) as required by ZATCA JSON body `{ csr: "..." }`.
 * @param {string} csrPem
 */
export function csrPemToBase64(csrPem) {
  return Buffer.from(csrPem, 'utf8').toString('base64');
}

/**
 * Redact sensitive patterns from a string (for safe error messages).
 * @param {string} s
 */
export function redactForLog(s) {
  if (typeof s !== 'string' || !s) {
    return '';
  }
  return s
    .replace(/"secret"\s*:\s*"[^"]*"/gi, '"secret":"[REDACTED]"')
    .replace(/"binarySecurityToken"\s*:\s*"[^"]*"/gi, '"binarySecurityToken":"[REDACTED]"')
    .replace(/"csr"\s*:\s*"[^"]*"/gi, '"csr":"[REDACTED]"')
    .slice(0, 4000);
}

/**
 * Axios client for compliance CSID: baseURL + 120s timeout + default JSON headers.
 * OTP is sent per-request (header) so it is never stored on the instance.
 * @param {string} baseURL
 * @param {number} [timeoutMs=120000]
 */
export function createComplianceCsidAxiosClient(baseURL, timeoutMs = 120_000) {
  const base = String(baseURL ?? '').trim();
  if (!base.startsWith('https://')) {
    throw new Error('ZATCA compliance base URL must be HTTPS.');
  }
  return axios.create({
    baseURL: base,
    timeout: timeoutMs,
    maxRedirects: 0,
    headers: {
      'Content-Type': 'application/json',
      'Accept-Version': 'V2',
      Accept: DEFAULT_ACCEPT,
      'User-Agent': 'FactoryERP-ZatcaNodeIntegration/1.0',
    },
    validateStatus: () => true,
  });
}

/**
 * @param {object} cfg
 * @param {number} cfg.timeoutMs
 */
export function createZatcaHttpClient(cfg) {
  const instance = axios.create({
    timeout: cfg.timeoutMs,
    validateStatus: () => true,
    maxRedirects: 0,
    headers: {
      'User-Agent': 'FactoryERP-ZatcaNodeIntegration/1.0',
    },
  });

  instance.interceptors.response.use(
    (res) => res,
    (err) => {
      if (err.response) {
        return Promise.reject(err);
      }
      const safe = err.code || err.message || 'network_error';
      return Promise.reject(new ZatcaApiError(`ZATCA network error: ${safe}`, { code: 'ZATCA_NETWORK' }));
    },
  );

  return instance;
}

/**
 * Single POST to compliance CSID endpoint (no retry — use {@link requestComplianceCsid}).
 * Per-request headers: only `OTP` (instance carries Content-Type + Accept-Version).
 * @param {import('axios').AxiosInstance} client
 * @param {string} path - e.g. /complianceCert-api
 * @param {string} otp
 * @param {string} csrPem
 */
export async function postComplianceCsidRequest(client, path, otp, csrPem) {
  const csrBase64 = csrPemToBase64(csrPem);
  const res = await client.post(
    path,
    { csr: csrBase64 },
    {
      headers: {
        OTP: String(otp).trim(),
      },
    },
  );

  if (res.status < 200 || res.status >= 300) {
    const raw =
      typeof res.data === 'object' ? JSON.stringify(res.data) : String(res.data ?? '');
    const safeBody = redactForLog(raw);
    const reqId =
      res.data && typeof res.data === 'object'
        ? pickRequestId(/** @type {object} */ (res.data))
        : null;
    throw new ZatcaApiError(`ZATCA compliance request failed (HTTP ${res.status}). ${safeBody}`, {
      httpStatus: res.status,
      requestId: reqId,
      code: 'ZATCA_COMPLIANCE_HTTP',
    });
  }

  if (!res.data || typeof res.data !== 'object') {
    throw new ZatcaApiError('ZATCA compliance: response body is not JSON object.', {
      httpStatus: res.status,
      code: 'ZATCA_COMPLIANCE_PARSE',
    });
  }

  return res.data;
}

function pickRequestId(data) {
  const v =
    data.requestID ?? data.requestId ?? data.RequestID ?? data.compliance_request_id ?? null;
  return typeof v === 'string' ? v : null;
}

/**
 * CSID / compliance certificate request with retries (Axios timeout 120s per attempt by default).
 * @param {object} input
 * @param {string} input.complianceUrl - Base URL or legacy full URL (see resolveComplianceBaseUrlAndPath)
 * @param {string} input.otp
 * @param {string} input.csrPem
 * @param {number} [input.timeoutMs=120000]
 */
export async function requestComplianceCsid({ complianceUrl, otp, csrPem, timeoutMs = 120_000 }) {
  ensureZatcaEnvLoaded();

  const baseFromEnv = process.env.ZATCA_COMPLIANCE_URL?.trim() || '';
  const effectiveBaseInput = baseFromEnv || complianceUrl || DEFAULT_COMPLIANCE_BASE;
  const { baseURL, path } = resolveComplianceBaseUrlAndPath(effectiveBaseInput);

  if (!baseURL.startsWith('https://')) {
    throw new Error('ZATCA compliance URL must be HTTPS.');
  }

  if (!String(otp ?? '').trim()) {
    throw new Error('OTP is required for compliance CSID request.');
  }

  const client = createComplianceCsidAxiosClient(baseURL, timeoutMs);

  return withRetry(async () => postComplianceCsidRequest(client, path, otp, csrPem), 2, 2000);
}
