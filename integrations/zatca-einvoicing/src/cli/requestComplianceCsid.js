#!/usr/bin/env node
/**
 * CLI: request ZATCA Compliance CSID / certificate and persist credentials.
 * Env is always loaded from integrations/zatca-einvoicing/.env (see loadEnv.js) before config/build.
 */

import { loadZatcaEnv, buildZatcaConfig, assertComplianceRequestConfig, getExpectedZatcaEnvPath } from '../config/loadEnv.js';
import { resolveCsrPem } from '../storage/csrReader.js';
import { normalizeComplianceResponse, saveComplianceCredentials } from '../storage/credentialsStore.js';
import { requestComplianceCsid } from '../services/zatcaService.js';
import { ZatcaApiError } from '../errors/ZatcaApiError.js';

function log(msg) {
  console.error(`[ZATCA] ${msg}`);
}

async function main() {
  const envInfo = loadZatcaEnv();
  const expected = getExpectedZatcaEnvPath();

  if (envInfo.loadedAny) {
    log(`ZATCA env loaded successfully (${envInfo.envExists ? '.env' : ''}${envInfo.envExists && envInfo.localExists ? ' + ' : ''}${envInfo.localExists ? '.env.local' : ''})`);
  } else {
    log(`ZATCA env: no .env / .env.local at ${expected} — using process.env only`);
  }

  const cfg = buildZatcaConfig();
  assertComplianceRequestConfig(cfg);

  log(`ZATCA_OTP: loaded from environment (${cfg.envFilePresent ? `file: ${expected}` : 'process environment / CI'})`);

  const { pem, source } = await resolveCsrPem(cfg);
  log(`CSR resolved (source: ${source})`);

  log('Starting CSID request...');

  let raw;
  try {
    raw = await requestComplianceCsid({
      complianceUrl: cfg.complianceUrl,
      otp: cfg.otp,
      csrPem: pem,
      timeoutMs: cfg.timeoutMs,
    });
  } catch (e) {
    if (e instanceof ZatcaApiError) {
      console.error(`[ZATCA] ${e.message}`);
      process.exit(1);
    }
    console.error(`[ZATCA] Unexpected error: ${e instanceof Error ? e.message : String(e)}`);
    process.exit(1);
  }

  const record = normalizeComplianceResponse(raw, 'sandbox');
  await saveComplianceCredentials(cfg.credentialsStorePath, record);

  console.log(
    JSON.stringify(
      {
        ok: true,
        csrSource: source,
        credentialsPath: cfg.credentialsStorePath,
        requestId: record.requestId,
        csid: record.csid,
        savedAt: record.savedAt,
      },
      null,
      2,
    ),
  );
}

main().catch((e) => {
  console.error(e instanceof Error ? e.message : String(e));
  process.exit(1);
});
