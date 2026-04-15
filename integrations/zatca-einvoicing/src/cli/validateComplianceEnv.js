#!/usr/bin/env node
/**
 * Dry validation: loads integration .env (auto-created if missing), validates ZATCA_OTP + CSR resolution.
 * Does not call ZATCA HTTP. Exit 0 when ready for `npm run zatca:compliance`.
 */

import {
  loadZatcaEnv,
  getExpectedZatcaEnvPath,
  runComplianceDryValidation,
  ZATCA_OTP_PLACEHOLDER,
} from '../config/loadEnv.js';
import { resolveCsrPem } from '../storage/csrReader.js';

function log(msg) {
  console.error(`[ZATCA] ${msg}`);
}

async function main() {
  const envInfo = loadZatcaEnv();
  const expected = getExpectedZatcaEnvPath();

  if (envInfo.autoCreated) {
    log('ZATCA env loaded successfully (.env was auto-created in this package)');
    const otp = process.env.ZATCA_OTP?.trim() ?? '';
    const kind =
      otp === ZATCA_OTP_PLACEHOLDER || otp === ''
        ? 'placeholder — set a real Fatoora OTP before CSID request'
        : 'from process.env at file creation or from generated file';
    log(`Dry check: ZATCA_OTP detected after auto-create (${kind})`);
  } else if (envInfo.loadedAny) {
    log(`ZATCA env loaded successfully (${envInfo.envExists ? '.env' : ''}${envInfo.envExists && envInfo.localExists ? ' + ' : ''}${envInfo.localExists ? '.env.local' : ''})`);
  } else {
    log(`ZATCA env: unexpected state — expected: ${expected}`);
  }

  const dry = await runComplianceDryValidation(resolveCsrPem);
  log(`Package root: ${dry.packageRoot}`);
  log(`ZATCA_OTP: ${dry.otpSet ? 'set (value hidden)' : 'NOT SET'}`);
  if (dry.otpPlaceholder) {
    log('ZATCA_OTP is still the placeholder — replace with a real Fatoora OTP before CSID request.');
  }
  log(`CSR resolved (source: ${dry.csrSource})`);
  log('Configuration OK — ready for CSID request execution (run: npm run zatca:compliance).');
}

main().catch((e) => {
  log(e instanceof Error ? e.message : String(e));
  process.exit(1);
});
