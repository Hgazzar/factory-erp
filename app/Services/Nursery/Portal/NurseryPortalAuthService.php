<?php

declare(strict_types=1);

namespace App\Services\Nursery\Portal;

use App\Models\Nursery\Guardian;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;
use InvalidArgumentException;

/**
 * مصادقة بوابة أولياء الأمور — رابط دعوة خاص + رقم الجوال المسجّل لنفس الدعوة.
 */
final class NurseryPortalAuthService
{
    private const SESSION_KEY = 'nursery_portal.session';

    /**
     * دخول عبر رابط دعوة مربوط بولي أمر + تأكيد رقم جواله المسجّل فقط.
     *
     * @throws InvalidArgumentException
     */
    public function loginViaInvitePhone(int $tenantUserId, string $token, string $phone): Guardian
    {
        $token = trim($token);
        $phone = trim($phone);

        if ($token === '') {
            throw new InvalidArgumentException('رابط الدعوة غير صالح.');
        }

        if ($phone === '') {
            throw new InvalidArgumentException('رقم الجوال مطلوب.');
        }

        $guardian = Guardian::findByPortalToken($tenantUserId, $token);

        if ($guardian === null) {
            throw new InvalidArgumentException('رابط الدعوة غير صالح أو منتهٍ.');
        }

        if (! $this->phonesMatch((string) $guardian->phone, $phone)) {
            throw new InvalidArgumentException('رقم الجوال غير مطابق لهذه الدعوة.');
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
        Session::forget([self::SESSION_KEY]);
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

    private function phonesMatch(string $a, string $b): bool
    {
        if ($a === $b) {
            return true;
        }

        $na = preg_replace('/\D+/', '', $a) ?? $a;
        $nb = preg_replace('/\D+/', '', $b) ?? $b;

        return $na !== '' && $na === $nb;
    }
}
