<?php

declare(strict_types=1);

namespace App\Services\Fleet;

use App\Models\Fleet\FleetProduct;
use App\Models\PosProduct;
use App\Services\Tenant\TenantModuleRegistry;
use InvalidArgumentException;

final class FleetProductPublishService
{
    public function __construct(
        private readonly TenantModuleRegistry $modules,
    ) {}

    public function publishToStore(FleetProduct $product, int $tenantUserId): FleetProduct
    {
        if ((int) $product->user_id !== $tenantUserId) {
            throw new InvalidArgumentException('الصنف غير تابع لهذا الحساب.');
        }

        if (! $this->modules->isEnabled('pos', $tenantUserId)) {
            throw new InvalidArgumentException('موديول نقاط البيع غير مفعّل — لا يمكن نشر الصنف للمتجر.');
        }

        $posProduct = null;
        if ($product->pos_product_id !== null) {
            $posProduct = PosProduct::query()
                ->where('user_id', $tenantUserId)
                ->where('id', $product->pos_product_id)
                ->first();
        }

        if ($posProduct === null) {
            $posProduct = PosProduct::query()->create([
                'user_id' => $tenantUserId,
                'name' => $product->name,
                'sku' => $product->sku,
                'description' => $product->description,
                'sale_price' => round((float) $product->sale_price, 4),
                'cost_price' => 0,
                'vat_percent' => 0,
                'opening_quantity' => 0,
                'current_quantity' => 9999,
                'is_active' => (bool) $product->is_active,
                'is_published_online' => true,
            ]);
        } else {
            $posProduct->update([
                'name' => $product->name,
                'sku' => $product->sku,
                'description' => $product->description,
                'sale_price' => round((float) $product->sale_price, 4),
                'is_active' => (bool) $product->is_active,
                'is_published_online' => true,
            ]);
        }

        $product->update(['pos_product_id' => $posProduct->id]);

        return $product->fresh();
    }
}
