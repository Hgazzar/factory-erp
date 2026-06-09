<?php

declare(strict_types=1);

namespace App\Services\Fleet;

use App\Models\Fleet\FleetCustomer;
use App\Models\PosSale;
use App\Models\TenantStoreSetting;
use App\Support\FleetAccess;

final class FleetStoreIntegrationService
{
    public function __construct(
        private readonly FleetAccess $fleetAccess,
    ) {}

    public function fieldDeliveryAvailable(int $tenantUserId): bool
    {
        if (! $this->fleetAccess->operationsEnabled($tenantUserId)) {
            return false;
        }

        $settings = TenantStoreSetting::forTenant($tenantUserId);

        return (bool) $settings->field_delivery_enabled;
    }

    /** @return list<array{key: string, label: string}> */
    public function checkoutFulfillmentOptions(int $tenantUserId): array
    {
        $options = [
            ['key' => PosSale::FULFILLMENT_PICKUP, 'label' => 'استلام / توصيل عادي'],
        ];

        if ($this->fieldDeliveryAvailable($tenantUserId)) {
            $options[] = [
                'key' => PosSale::FULFILLMENT_FIELD_DELIVERY,
                'label' => 'تسليم ميداني (مندوب)',
            ];
        }

        return $options;
    }
}
