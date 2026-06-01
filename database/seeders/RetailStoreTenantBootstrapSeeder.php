<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\TenantFeature;
use App\Models\TenantProfile;
use App\Models\User;
use App\Services\Tenant\TenantFeatureRegistry;
use App\Support\StoreFeatureKeys;
use Illuminate\Database\Seeder;

/**
 * يجهّز slug المتجر وميزة online_store للمستأجرين بدون ملف نيش.
 * تشغيل: php artisan db:seed --class=RetailStoreTenantBootstrapSeeder
 */
final class RetailStoreTenantBootstrapSeeder extends Seeder
{
    public function run(): void
    {
        $registry = app(TenantFeatureRegistry::class);

        $holder = TenantProfile::query()->where('slug', 'retail')->first();
        if ($holder !== null && (int) $holder->tenant_user_id !== 1) {
            $holder->slug = $holder->domain ?: 'tenant-'.$holder->tenant_user_id;
            $holder->save();
            $this->command?->info("Slug «retail» أُعيد تخصيصه للمستأجر {$holder->tenant_user_id} → {$holder->slug}");
        }

        $admins = User::query()
            ->whereIn('role', ['admin', 'super_admin'])
            ->orderBy('id')
            ->get(['id', 'email']);

        foreach ($admins as $admin) {
            $tenantUserId = (int) $admin->id;

            $profile = TenantProfile::query()->firstOrNew(['tenant_user_id' => $tenantUserId]);

            if (! $profile->exists) {
                $this->command?->warn("تخطّي {$admin->email}: لا يوجد ملف نيش — أنشئ المستأجر من Super Admin مع slug يدوي.");

                continue;
            }

            if ($profile->slug === null || $profile->slug === '') {
                $this->command?->warn("تخطّي {$admin->email}: slug فارغ — عيّنه من /super-admin/tenants أو: php artisan tenant:rename-slug \"{$admin->email}\" your-slug");

                continue;
            }

            $profile->niche_key = $profile->niche_key ?: 'retail';
            $profile->status = TenantProfile::STATUS_ACTIVE;
            $profile->save();

            TenantFeature::query()->firstOrCreate([
                'tenant_id' => $tenantUserId,
                'feature_key' => StoreFeatureKeys::ONLINE_STORE,
            ]);

            $registry->forgetCache($tenantUserId);

            $url = route('store.portal.home', ['tenant_slug' => $profile->slug ?? $profile->domain]);
            $this->command?->info("{$admin->email} (id {$tenantUserId}): {$url}");
        }
    }
}
