<?php

declare(strict_types=1);

namespace App\Services\Nursery;

use App\Models\Nursery\NurserySetting;
use App\Models\User;
use App\Services\Tenant\TenantBrandingService;
use InvalidArgumentException;

final class NurserySettingsService
{
    public function __construct(
        private readonly TenantBrandingService $tenantBranding,
    ) {}
    /**
     * @param  array<string, mixed>  $data
     */
    public function updateAccount(int $tenantUserId, array $data): NurserySetting
    {
        $settings = NurserySetting::forTenant($tenantUserId);

        $name = trim((string) ($data['nursery_name'] ?? ''));
        if ($name === '') {
            throw new InvalidArgumentException('اسم الحضانة مطلوب.');
        }

        $managerEmail = $this->nullableString($data['manager_email'] ?? null);
        if ($managerEmail !== null && ! filter_var($managerEmail, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException('بريد مدير الحضانة غير صالح.');
        }

        $contactEmail = $this->nullableString($data['contact_email'] ?? null);
        if ($contactEmail !== null && ! filter_var($contactEmail, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException('بريد الحضانة غير صالح.');
        }

        $settings->update([
            'nursery_name' => $name,
            'display_name' => $this->nullableString($data['display_name'] ?? null),
            'contact_phone' => $this->nullableString($data['contact_phone'] ?? null),
            'contact_email' => $contactEmail,
            'address' => $this->nullableString($data['address'] ?? null),
            'city' => $this->nullableString($data['city'] ?? null),
            'region' => $this->nullableString($data['region'] ?? null),
            'manager_name' => $this->nullableString($data['manager_name'] ?? null),
            'manager_mobile' => $this->nullableString($data['manager_mobile'] ?? null),
            'manager_email' => $managerEmail,
        ]);

        return $settings->fresh();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function updateBranding(int $tenantUserId, array $data): NurserySetting
    {
        $this->tenantBranding->updateBranding($tenantUserId, $data, TenantBrandingService::MODULE_NURSERY);

        return NurserySetting::forTenant($tenantUserId)->fresh();
    }

    public function updateLogo(int $tenantUserId, ?UploadedFile $file, bool $remove = false): NurserySetting
    {
        $this->tenantBranding->updateLogo($tenantUserId, $file, $remove);

        return NurserySetting::forTenant($tenantUserId)->fresh();
    }

    public function joinedAt(int $tenantUserId): ?\Carbon\Carbon
    {
        $created = User::query()->whereKey($tenantUserId)->value('created_at');

        return $created ? \Carbon\Carbon::parse($created) : null;
    }

    private function nullableString(mixed $value): ?string
    {
        $value = trim((string) $value);

        return $value !== '' ? $value : null;
    }
}
