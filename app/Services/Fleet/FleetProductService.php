<?php

declare(strict_types=1);

namespace App\Services\Fleet;

use App\Models\Fleet\FleetProduct;
use InvalidArgumentException;

final class FleetProductService
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function create(int $tenantUserId, array $data): FleetProduct
    {
        $name = trim((string) ($data['name'] ?? ''));
        if ($name === '') {
            throw new InvalidArgumentException('اسم الصنف مطلوب.');
        }

        return FleetProduct::query()->create([
            'user_id' => $tenantUserId,
            'name' => $name,
            'sku' => $this->nullable($data['sku'] ?? null),
            'sale_price' => round((float) ($data['sale_price'] ?? 0), 4),
            'image_url' => $this->nullable($data['image_url'] ?? null),
            'is_active' => (bool) ($data['is_active'] ?? true),
            'description' => $this->nullable($data['description'] ?? null),
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(FleetProduct $product, int $tenantUserId, array $data): FleetProduct
    {
        if ((int) $product->user_id !== $tenantUserId) {
            throw new InvalidArgumentException('الصنف غير تابع لهذا الحساب.');
        }

        $name = trim((string) ($data['name'] ?? $product->name));
        if ($name === '') {
            throw new InvalidArgumentException('اسم الصنف مطلوب.');
        }

        $product->update([
            'name' => $name,
            'sku' => $this->nullable($data['sku'] ?? null),
            'sale_price' => round((float) ($data['sale_price'] ?? $product->sale_price), 4),
            'image_url' => $this->nullable($data['image_url'] ?? null),
            'is_active' => (bool) ($data['is_active'] ?? $product->is_active),
            'description' => $this->nullable($data['description'] ?? null),
        ]);

        return $product->fresh();
    }

    private function nullable(mixed $value): ?string
    {
        $value = trim((string) $value);

        return $value !== '' ? $value : null;
    }
}
