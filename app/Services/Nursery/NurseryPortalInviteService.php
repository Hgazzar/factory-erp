<?php

declare(strict_types=1);

namespace App\Services\Nursery;

use App\Models\Nursery\Child;
use App\Models\Nursery\Guardian;
use App\Models\Nursery\NurseryOutboundMessage;
use App\Models\Nursery\NurserySetting;
use App\Models\TenantProfile;
use App\Models\User;
use App\Services\Nursery\Portal\NurseryPortalAuthService;
use App\Support\PremiumFeatureKeys;
use Illuminate\Support\Facades\Log;

/**
 * دعوة ولي الأمر لبوابة الحضانة — رابط magic link + جدولة واتساب عبر Outbox.
 */
final class NurseryPortalInviteService
{
    public function __construct(
        private readonly NurseryPortalAuthService $portalAuth,
        private readonly NurseryWhatsAppOutboxService $outbox,
    ) {}

    public function isPortalEnabled(int $tenantUserId): bool
    {
        $tenant = User::query()->find($tenantUserId);

        return $tenant !== null && $tenant->hasFeature(PremiumFeatureKeys::NURSERY_PORTAL);
    }

    public function inviteUrl(int $tenantUserId, Guardian $guardian): ?string
    {
        if (! $this->isPortalEnabled($tenantUserId)) {
            return null;
        }

        $slug = $this->tenantSlug($tenantUserId);
        if ($slug === null) {
            return null;
        }

        $token = $this->portalAuth->ensurePortalInviteToken($guardian);

        return route('nursery.portal.invite', [
            'tenant_slug' => $slug,
            'token' => $token,
        ]);
    }

    /**
     * @return array{sent: bool, url: string|null, message: string}
     */
    public function sendInviteToGuardian(int $tenantUserId, Guardian $guardian): array
    {
        abort_unless((int) $guardian->user_id === $tenantUserId, 404);

        if (trim((string) $guardian->phone) === '') {
            return ['sent' => false, 'url' => null, 'message' => 'رقم جوال ولي الأمر مطلوب لإرسال الدعوة.'];
        }

        $url = $this->inviteUrl($tenantUserId, $guardian);
        if ($url === null) {
            return ['sent' => false, 'url' => null, 'message' => 'بوابة أولياء الأمور غير مفعّلة أو slug غير متاح.'];
        }

        $guardian->loadMissing('children:id,guardian_id,name');
        $childNames = $guardian->children->pluck('name')->filter()->implode('، ');
        $nurseryName = NurserySetting::forTenant($tenantUserId)->portalDisplayName();
        $message = implode("\n", array_filter([
            "مرحباً {$guardian->name}،",
            "دعوة للانضمام لبوابة {$nurseryName}.",
            $childNames !== '' ? "أطفالك: {$childNames}" : null,
            $url,
        ]));

        $this->enqueueInviteWhatsApp($tenantUserId, $guardian, $message);

        return [
            'sent' => true,
            'url' => $url,
            'message' => 'تمت جدولة إرسال رابط الدعوة عبر واتساب.',
        ];
    }

    public function revokePortalAccess(int $tenantUserId, Guardian $guardian): void
    {
        abort_unless((int) $guardian->user_id === $tenantUserId, 404);

        $guardian->forceFill([
            'portal_access_token' => null,
        ])->save();
    }

    /**
     * @return array{sent: bool, url: string|null, message: string}
     */
    public function sendInvite(int $tenantUserId, Child $child): array
    {
        abort_unless((int) $child->user_id === $tenantUserId, 404);

        $child->loadMissing('guardian');
        $guardian = $child->guardian;

        if ($guardian === null) {
            return ['sent' => false, 'url' => null, 'message' => 'لا يوجد ولي أمر مرتبط بهذا الطفل.'];
        }

        if (trim((string) $guardian->phone) === '') {
            return ['sent' => false, 'url' => null, 'message' => 'رقم جوال ولي الأمر مطلوب لإرسال الدعوة.'];
        }

        $url = $this->inviteUrl($tenantUserId, $guardian);
        if ($url === null) {
            return ['sent' => false, 'url' => null, 'message' => 'بوابة أولياء الأمور غير مفعّلة أو slug غير متاح.'];
        }

        $nurseryName = NurserySetting::forTenant($tenantUserId)->portalDisplayName();
        $message = implode("\n", [
            "مرحباً {$guardian->name}،",
            "دعوة للانضمام لبوابة {$nurseryName}.",
            "تابع {$child->name}: {$url}",
        ]);

        $this->enqueueInviteWhatsApp($tenantUserId, $guardian, $message);

        return [
            'sent' => true,
            'url' => $url,
            'message' => 'تمت جدولة إرسال رابط الدعوة عبر واتساب.',
        ];
    }

    public function portalLoginUrl(int $tenantUserId): ?string
    {
        $slug = $this->tenantSlug($tenantUserId);

        return $slug !== null
            ? route('nursery.portal.login', ['tenant_slug' => $slug])
            : null;
    }

    private function enqueueInviteWhatsApp(int $tenantUserId, Guardian $guardian, string $message): void
    {
        Log::info('Nursery portal invite queued', [
            'tenant_user_id' => $tenantUserId,
            'guardian_id' => $guardian->id,
            'phone' => $guardian->phone,
        ]);

        $this->outbox->enqueue(
            $tenantUserId,
            NurseryOutboundMessage::TYPE_GUARDIAN_INVITE,
            NurseryOutboundMessage::TYPE_GUARDIAN_INVITE.':'.$tenantUserId.':'.$guardian->id,
            NurseryOutboundMessage::RELATED_GUARDIAN,
            (int) $guardian->id,
            [
                'phone' => (string) $guardian->phone,
                'message' => $message,
                'guardian_id' => (int) $guardian->id,
            ],
            true,
        );
    }

    private function tenantSlug(int $tenantUserId): ?string
    {
        $profile = TenantProfile::forTenantUser($tenantUserId);
        $slug = $profile?->slug ?? $profile?->domain;

        return $slug !== null && trim($slug) !== '' ? trim($slug) : null;
    }
}
