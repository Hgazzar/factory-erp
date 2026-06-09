<?php

declare(strict_types=1);

namespace App\Models\Fleet;

use App\Models\Concerns\ResolvesRouteBindingForTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FleetRouteStop extends Model
{
    use ResolvesRouteBindingForTenant;

    public const STATUS_PENDING = 'pending';

    public const STATUS_VISITED = 'visited';

    public const STATUS_SKIPPED = 'skipped';

    protected $table = 'fleet_route_stops';

    protected $fillable = [
        'user_id',
        'route_id',
        'customer_id',
        'pos_sale_id',
        'sort_order',
        'status',
        'visited_at',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
            'visited_at' => 'datetime',
        ];
    }

    public function route(): BelongsTo
    {
        return $this->belongsTo(FleetRoute::class, 'route_id');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(FleetCustomer::class, 'customer_id');
    }

    public function posSale(): BelongsTo
    {
        return $this->belongsTo(\App\Models\PosSale::class, 'pos_sale_id');
    }
}
