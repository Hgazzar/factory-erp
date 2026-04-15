/**
 * Typed error for ZATCA HTTP failures — never attach OTP, secret, or private key material.
 */
export class ZatcaApiError extends Error {
  /**
   * @param {string} message - Safe, human-readable message
   * @param {object} [ctx]
   * @param {number} [ctx.httpStatus]
   * @param {string} [ctx.requestId] - From ZATCA response if present
   * @param {string} [ctx.code] - Application error code
   */
  constructor(message, ctx = {}) {
    super(message);
    this.name = 'ZatcaApiError';
    this.httpStatus = ctx.httpStatus;
    this.requestId = ctx.requestId;
    this.code = ctx.code ?? 'ZATCA_API_ERROR';
    Error.captureStackTrace?.(this, ZatcaApiError);
  }
}
