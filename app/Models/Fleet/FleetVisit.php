<?php

declare(strict_types=1);

namespace App\Models\Fleet;

use App\Models\Concerns\ResolvesRouteBindingForTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FleetVisit extends Model
{
    use ResolvesRouteBindingForTenant;

    public const GEOFENCE_INSIDE = 'inside';

    public const GEOFENCE_OUTSIDE = 'outside';

    public const GEOFENCE_UNVERIFIED = 'unverified';

    public const OUTCOME_SALE = 'sale';

    public const OUTCOME_NO_SALE = 'no_sale';

    public const OUTCOME_SKIPPED = 'skipped';

    protected $table = 'fleet_visits';

    protected $fillable = [
        'user_id',
        'agent_id',
        'customer_id',
        'route_stop_id',
        'collection_id',
        'captured_lat',
        'captured_lng',
        'accuracy_meters',
        'is_mocked',
        'geofence_status',
        'distance_meters',
        'outcome',
        'visit_reason',
        'visited_at',
    ];

    protected function casts(): array
    {
        return [
            'captured_lat' => 'float',
            'captured_lng' => 'float',
            'accuracy_meters' => 'integer',
            'is_mocked' => 'boolean',
            'distance_meters' => 'integer',
            'visited_at' => 'datetime',
        ];
    }

    public function agent(): BelongsTo
    {
        return $this->belongsTo(FleetAgent::class, 'agent_id');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(FleetCustomer::class, 'customer_id');
    }

    public function routeStop(): BelongsTo
    {
        return $this->belongsTo(FleetRouteStop::class, 'route_stop_id');
    }

    public function collection(): BelongsTo
    {
        return $this->belongsTo(FleetCollection::class, 'collection_id');
    }

    public function isException(): bool
    {
        return $this->is_mocked
            || $this->geofence_status === self::GEOFENCE_OUTSIDE
            || $this->geofence_status === self::GEOFENCE_UNVERIFIED;
    }
}
