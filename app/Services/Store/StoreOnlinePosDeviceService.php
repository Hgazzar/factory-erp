<?php

declare(strict_types=1);

namespace App\Services\Store;

use App\Models\PosDevice;
use App\Models\TenantStoreSetting;
use App\Models\Warehouse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * يضمن وجود جهاز POS نشط لمعالجة طلبات المتجر الإلكتروني.
 */
final class StoreOnlinePosDeviceService
{
    public function resolveOrCreate(int $tenantUserId, TenantStoreSetting $settings): PosDevice
    {
        $existing = $this->findActiveDevice($tenantUserId, $settings);
        if ($existing !== null) {
            return $existing;
        }

        return DB::transaction(function () use ($tenantUserId, $settings): PosDevice {
            $lockedSettings = TenantStoreSetting::query()
                ->where('tenant_user_id', $tenantUserId)
                ->lockForUpdate()
                ->firstOrFail();

            $existing = $this->findActiveDevice($tenantUserId, $lockedSettings);
            if ($existing !== null) {
                return $existing;
            }

            $warehouse = Warehouse::withoutGlobalScopes()
                ->where('user_id', $tenantUserId)
                ->where('is_active', true)
                ->orderByDesc('is_default')
                ->orderBy('id')
                ->first();

            if ($warehouse === null) {
                $warehouse = Warehouse::withoutGlobalScopes()->create([
                    'user_id' => $tenantUserId,
                    'code' => 'STORE-'.str_pad((string) $tenantUserId, 4, '0', STR_PAD_LEFT),
                    'name_ar' => 'مستودع المتجر الإلكتروني',
                    'name_en' => 'Online Store Warehouse',
                    'is_active' => true,
                    'is_default' => true,
                ]);
            }

            $device = PosDevice::withoutGlobalScopes()->create([
                'user_id' => $tenantUserId,
                'name' => 'متجر إلكتروني',
                'mac_address' => 'ONLINE-STORE-'.$tenantUserId.'-'.Str::lower(Str::random(8)),
                'status' => PosDevice::STATUS_ACTIVE,
                'warehouse_id' => $warehouse->id,
            ]);

            if (! $lockedSettings->default_pos_device_id) {
                $lockedSettings->default_pos_device_id = $device->id;
                $lockedSettings->save();
            }

            return $device;
        });
    }

    private function findActiveDevice(int $tenantUserId, TenantStoreSetting $settings): ?PosDevice
    {
        if ($settings->default_pos_device_id) {
            $device = PosDevice::withoutGlobalScopes()
                ->where('user_id', $tenantUserId)
                ->whereKey((int) $settings->default_pos_device_id)
                ->where('status', PosDevice::STATUS_ACTIVE)
                ->first();

            if ($device !== null) {
                return $device;
            }
        }

        return PosDevice::withoutGlobalScopes()
            ->where('user_id', $tenantUserId)
            ->where('status', PosDevice::STATUS_ACTIVE)
            ->orderBy('id')
            ->first();
    }
}
