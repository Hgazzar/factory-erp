<?php

declare(strict_types=1);

namespace App\Services\Fleet;

use App\Models\Fleet\FleetAgent;
use App\Models\TenantProfile;
use App\Services\Tenant\TenantModuleRegistry;
use App\Support\FleetAccess;
use Illuminate\Support\Carbon;
use InvalidArgumentException;
use Laravel\Sanctum\NewAccessToken;

final class FleetAgentAuthService
{
    public function __construct(
        private readonly TenantModuleRegistry $modules,
        private readonly FleetAccess $fleetAccess,
    ) {}

    public function login(string $tenantSlug, string $phone, string $pin, string $deviceName): NewAccessToken
    {
        $tenantUserId = $this->resolveTenantUserId($tenantSlug);
        $this->assertFleetEnabled($tenantUserId);

        $phone = trim($phone);
        $pin = trim($pin);
        $deviceName = trim($deviceName);

        if ($phone === '' || $pin === '') {
            throw new InvalidArgumentException('رقم الجوال ورمز الدخول مطلوبان.');
        }

        if ($deviceName === '') {
            $deviceName = 'mobile';
        }

        $agent = FleetAgent::findActiveByPhone($tenantUserId, $phone);

        if ($agent === null || ! $agent->hasApiAccess() || ! $agent->verifyApiPin($pin)) {
            throw new InvalidArgumentException('بيانات الدخول غير صحيحة.');
        }

        $expiryDays = max(1, (int) config('fleet.agent_api.token_expiry_days', 90));
        $abilities = config('fleet.agent_api.token_abilities', ['fleet:agent']);

        $token = $agent->createToken(
            $deviceName,
            is_array($abilities) ? $abilities : ['fleet:agent'],
            now()->addDays($expiryDays),
        );

        $agent->forceFill(['api_last_login_at' => now()])->save();

        return $token;
    }

    public function logout(FleetAgent $agent): void
    {
        $token = $agent->currentAccessToken();
        if ($token !== null) {
            $token->delete();
        }
    }

    public function resolveTenantUserId(string $tenantSlug): int
    {
        $profile = TenantProfile::resolveBySlug($tenantSlug);

        if ($profile === null || $profile->status !== TenantProfile::STATUS_ACTIVE) {
            throw new InvalidArgumentException('المنشأة غير موجودة أو غير نشطة.');
        }

        return (int) $profile->tenant_user_id;
    }

    public function assertFleetEnabled(int $tenantUserId): void
    {
        if (! $this->modules->isEnabled('fleet', $tenantUserId)) {
            throw new InvalidArgumentException('موديول المناديب غير مفعّل.');
        }

        if (! $this->fleetAccess->operationsEnabled($tenantUserId)) {
            throw new InvalidArgumentException('العمليات الميدانية غير مفعّلة لهذه المنشأة.');
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function agentPayload(FleetAgent $agent): array
    {
        return [
            'id' => (int) $agent->id,
            'name' => $agent->name,
            'phone' => $agent->phone,
            'email' => $agent->email,
            'status' => $agent->status,
            'api_last_login_at' => $agent->api_last_login_at instanceof Carbon
                ? $agent->api_last_login_at->toIso8601String()
                : null,
        ];
    }
}
