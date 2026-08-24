<?php

declare(strict_types=1);

namespace App\Models\Fleet;

use App\Models\Concerns\ResolvesRouteBindingForTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FleetCustomer extends Model
{
    use ResolvesRouteBindingForTenant;

    public const STATUS_ACTIVE = 'active';

    public const STATUS_INACTIVE = 'inactive';

    public const LOCATION_NONE = 'none';

    public const LOCATION_PENDING = 'pending';

    public const LOCATION_APPROVED = 'approved';

    public const LOCATION_SOURCE_AGENT = 'agent';

    public const LOCATION_SOURCE_MANAGER = 'manager';

    public const LOCATION_SOURCE_MAP = 'map';

    protected $table = 'fleet_customers';

    protected $fillable = [
        'user_id',
        'name',
        'phone',
        'email',
        'address',
        'city',
        'region',
        'assigned_agent_id',
        'crm_customer_id',
        'status',
        'notes',
        'latitude',
        'longitude',
        'location_status',
        'location_source',
        'location_updated_at',
        'geofence_radius',
    ];

    protected function casts(): array
    {
        return [
            'latitude' => 'float',
            'longitude' => 'float',
            'location_updated_at' => 'datetime',
            'geofence_radius' => 'integer',
        ];
    }

    public function assignedAgent(): BelongsTo
    {
        return $this->belongsTo(FleetAgent::class, 'assigned_agent_id');
    }

    public function hasApprovedLocation(): bool
    {
        return $this->location_status === self::LOCATION_APPROVED
            && $this->latitude !== null
            && $this->longitude !== null;
    }
}
