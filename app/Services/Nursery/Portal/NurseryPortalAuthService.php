<?php

declare(strict_types=1);

namespace App\Services\Nursery\Portal;

use App\Models\Nursery\Guardian;
use App\Models\Nursery\NurseryOutboundMessage;
use App\Models\Nursery\NurserySetting;
use App\Services\Nursery\NurseryWhatsAppOutboxService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;
use InvalidArgumentException;

/**
 * مصادقة بوابة أولياء الأمور — OTP في الجلسة + جدولة واتساب عبر Outbox.
 */
final class NurseryPortalAuthService
{
    private const SESSION_KEY = 'nursery_portal.session';

    private const OTP_KEY = 'nursery_portal.otp';

    /**
     * يطلب رمز تحقق لرقم جوال ولي أمر مسجّل في المستأجر.
     *
     * @throws InvalidArgumentException
     */
    public function requestOtp(int $tenantUserId, string $phone): void
    {
        $phone = trim($phone);

        if ($phone === '') {
            throw new InvalidArgumentException('رقم الجوال مطلوب.');
        }

        $guardian = $this->findGuardianByPhone($tenantUserId, $phone);

        if ($guardian === null) {
            throw new InvalidArgumentException('رقم الجوال غير مسجّل لدى هذه الحضانة.');
        }

        $code = $this->generateOtpCode();
        $ttlMinutes = max(1, (int) config('nursery.portal.otp_ttl_minutes', 10));

        Session::put(self::OTP_KEY, [
            'tenant_user_id' => $tenantUserId,
            'phone' => $phone,
            'guardian_id' => (int) $guardian->id,
            'code' => $code,
            'expires_at' => now()->addMinutes($ttlMinutes)->timestamp,
        ]);

        if ((bool) config('nursery.portal.otp_log_only', true)) {
            Log::info('Nursery portal OTP (dev/log-only)', [
                'tenant_user_id' => $tenantUserId,
                'guardian_id' => $guardian->id,
                'phone' => $phone,
                'otp' => $code,
            ]);
        }

        $this->enqueueOtpWhatsApp($tenantUserId, $guardian, $phone, $code, $ttlMinutes);
    }

    /**
     * يتحقق من الرمز ويفتح جلسة ولي الأمر.
     *
     * @throws InvalidArgumentException
     */
    public function verifyOtp(int $tenantUserId, string $phone, string $code): Guardian
    {
        $phone = trim($phone);
        $code = trim($code);

        if ($phone === '' || $code === '') {
            throw new InvalidArgumentException('رقم الجوال ورمز التحقق مطلوبان.');
        }

        $pending = Session::get(self::OTP_KEY);

        if (! is_array($pending)) {
            throw new InvalidArgumentException('لم يُطلب رمز تحقق. أعد المحاولة.');
        }

        if ((int) ($pending['tenant_user_id'] ?? 0) !== $tenantUserId) {
            throw new InvalidArgumentException('جلسة التحقق غير صالحة.');
        }

        if ((int) ($pending['expires_at'] ?? 0) < now()->timestamp) {
            Session::forget(self::OTP_KEY);
            throw new InvalidArgumentException('انتهت صلاحية رمز التحقق. اطلب رمزاً جديداً.');
        }

        $storedPhone = (string) ($pending['phone'] ?? '');
        if (! $this->phonesMatch($storedPhone, $phone)) {
            throw new InvalidArgumentException('رقم الجوال لا يطابق طلب التحقق.');
        }

        if (! hash_equals((string) ($pending['code'] ?? ''), $code)) {
            throw new InvalidArgumentException('رمز التحقق غير صحيح.');
        }

        $guardianId = (int) ($pending['guardian_id'] ?? 0);
        $guardian = Guardian::query()
            ->withoutGlobalScopes()
            ->where('user_id', $tenantUserId)
            ->whereKey($guardianId)
            ->first();

        if ($guardian === null) {
            throw new InvalidArgumentException('تعذّر إتمام تسجيل الدخول.');
        }

        Session::forget(self::OTP_KEY);

        return $this->establishSession($tenantUserId, $guardian);
    }

    /**
     * تسجيل دخول عبر رابط دعوة (magic link).
     */
    public function loginViaInviteToken(int $tenantUserId, string $token): Guardian
    {
        $token = trim($token);

        if ($token === '') {
            throw new InvalidArgumentException('رابط الدعوة غير صالح.');
        }

        $guardian = Guardian::findByPortalToken($tenantUserId, $token);

        if ($guardian === null) {
            throw new InvalidArgumentException('رابط الدعوة غير صالح أو منتهٍ.');
        }

        return $this->establishSession($tenantUserId, $guardian);
    }

    /**
     * يولّد أو يجدّد token دعوة لولي أمر.
     */
    public function ensurePortalInviteToken(Guardian $guardian): string
    {
        if (trim((string) $guardian->portal_access_token) !== '') {
            return (string) $guardian->portal_access_token;
        }

        $token = Str::random(48);
        $guardian->forceFill([
            'portal_access_token' => $token,
            'portal_invited_at' => now(),
        ])->save();

        return $token;
    }

    public function logout(): void
    {
        Session::forget([self::SESSION_KEY, self::OTP_KEY]);
    }

    public function isAuthenticatedForTenant(int $tenantUserId): bool
    {
        $session = Session::get(self::SESSION_KEY);

        if (! is_array($session)) {
            return false;
        }

        return (int) ($session['tenant_user_id'] ?? 0) === $tenantUserId
            && (int) ($session['guardian_id'] ?? 0) > 0;
    }

    public function currentGuardianId(int $tenantUserId): ?int
    {
        if (! $this->isAuthenticatedForTenant($tenantUserId)) {
            return null;
        }

        $session = Session::get(self::SESSION_KEY);

        return is_array($session) ? (int) ($session['guardian_id'] ?? 0) : null;
    }

    public function currentGuardian(int $tenantUserId): ?Guardian
    {
        $guardianId = $this->currentGuardianId($tenantUserId);

        if ($guardianId === null || $guardianId < 1) {
            return null;
        }

        return Guardian::query()
            ->withoutGlobalScopes()
            ->where('user_id', $tenantUserId)
            ->whereKey($guardianId)
            ->first();
    }

    private function establishSession(int $tenantUserId, Guardian $guardian): Guardian
    {
        Session::put(self::SESSION_KEY, [
            'tenant_user_id' => $tenantUserId,
            'guardian_id' => (int) $guardian->id,
        ]);

        $guardian->forceFill(['portal_last_login_at' => now()])->save();

        return $guardian->fresh();
    }

    private function findGuardianByPhone(int $tenantUserId, string $phone): ?Guardian
    {
        $normalized = preg_replace('/\D+/', '', $phone) ?? $phone;

        return Guardian::query()
            ->withoutGlobalScopes()
            ->where('user_id', $tenantUserId)
            ->where(function ($q) use ($phone, $normalized): void {
                $q->where('phone', $phone);
                if ($normalized !== '') {
                    $q->orWhereRaw("REPLACE(REPLACE(REPLACE(phone, ' ', ''), '-', ''), '+', '') = ?", [$normalized]);
                }
            })
            ->first();
    }

    private function phonesMatch(string $a, string $b): bool
    {
        if ($a === $b) {
            return true;
        }

        $na = preg_replace('/\D+/', '', $a) ?? $a;
        $nb = preg_replace('/\D+/', '', $b) ?? $b;

        return $na !== '' && $na === $nb;
    }

    private function enqueueOtpWhatsApp(int $tenantUserId, Guardian $guardian, string $phone, string $code, int $ttlMinutes): void
    {
        $nurseryName = NurserySetting::forTenant($tenantUserId)->nursery_name;
        $message = implode("\n", [
            "رمز التحقق لبوابة {$nurseryName}: {$code}",
            "صالح لمدة {$ttlMinutes} دقائق.",
        ]);

        app(NurseryWhatsAppOutboxService::class)->enqueue(
            $tenantUserId,
            NurseryOutboundMessage::TYPE_GUARDIAN_OTP,
            NurseryOutboundMessage::TYPE_GUARDIAN_OTP.':'.$tenantUserId.':'.$guardian->id,
            NurseryOutboundMessage::RELATED_GUARDIAN,
            (int) $guardian->id,
            [
                'phone' => $phone,
                'message' => $message,
                'guardian_id' => (int) $guardian->id,
            ],
            true,
        );
    }

    private function generateOtpCode(): string
    {
        if ((bool) config('nursery.portal.otp_log_only', true)) {
            $dev = trim((string) config('nursery.portal.dev_otp_code', '123456'));

            return $dev !== '' ? $dev : '123456';
        }

        $length = max(4, min(8, (int) config('nursery.portal.otp_length', 6)));
        $max = (10 ** $length) - 1;
        $min = 10 ** ($length - 1);

        return str_pad((string) random_int($min, $max), $length, '0', STR_PAD_LEFT);
    }
}
