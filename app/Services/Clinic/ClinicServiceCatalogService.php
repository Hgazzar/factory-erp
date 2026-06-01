<?php

declare(strict_types=1);

namespace App\Services\Clinic;

use App\Models\Clinic\ClinicService;
use Illuminate\Support\Collection;

final class ClinicServiceCatalogService
{
    /**
     * @return Collection<int, ClinicService>
     */
    public function activeForTenant(int $tenantUserId): Collection
    {
        return ClinicService::query()
            ->where('user_id', $tenantUserId)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(int $tenantUserId, array $data): ClinicService
    {
        return ClinicService::query()->create([
            'user_id' => $tenantUserId,
            'code' => strtoupper(trim((string) ($data['code'] ?? ''))),
            'name' => trim((string) ($data['name'] ?? '')),
            'price' => round((float) ($data['price'] ?? 0), 4),
            'vat_inclusive' => (bool) ($data['vat_inclusive'] ?? true),
            'is_active' => (bool) ($data['is_active'] ?? true),
            'sort_order' => (int) ($data['sort_order'] ?? 0),
        ]);
    }

    public function seedDefaults(int $tenantUserId): void
    {
        if (ClinicService::query()->where('user_id', $tenantUserId)->exists()) {
            return;
        }

        $defaults = [
            ['code' => 'CONSULT', 'name' => 'كشف', 'price' => 300, 'sort_order' => 10],
            ['code' => 'FOLLOWUP', 'name' => 'استشارة / متابعة', 'price' => 200, 'sort_order' => 20],
            ['code' => 'ECG', 'name' => 'رسم قلب', 'price' => 150, 'sort_order' => 30],
            ['code' => 'DRESSING', 'name' => 'تغيير على جرح', 'price' => 100, 'sort_order' => 40],
            ['code' => 'LASER', 'name' => 'جلسة ليزر', 'price' => 500, 'sort_order' => 50],
        ];

        foreach ($defaults as $row) {
            $this->create($tenantUserId, $row);
        }
    }
}
