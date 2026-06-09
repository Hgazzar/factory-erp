<?php

declare(strict_types=1);

namespace App\Models\Fleet;

use App\Models\Concerns\ResolvesRouteBindingForTenant;
use Illuminate\Database\Eloquent\Model;

class FleetProduct extends Model
{
    use ResolvesRouteBindingForTenant;

    protected $table = 'fleet_products';

    protected $fillable = [
        'user_id',
        'pos_product_id',
        'name',
        'sku',
        'sale_price',
        'image_url',
        'is_active',
        'description',
    ];

    protected function casts(): array
    {
        return [
            'sale_price' => 'float',
            'is_active' => 'boolean',
        ];
    }
}
