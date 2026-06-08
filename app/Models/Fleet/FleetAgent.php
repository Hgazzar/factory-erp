<?php

declare(strict_types=1);

namespace App\Models\Fleet;

use App\Models\Concerns\ResolvesRouteBindingForTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FleetAgent extends Model
{
    use ResolvesRouteBindingForTenant;

    public const STATUS_ACTIVE = 'active';

    public const STATUS_INACTIVE = 'inactive';

    protected $table = 'fleet_agents';

    protected $fillable = [
        'user_id',
        'name',
        'phone',
        'email',
        'employee_id',
        'pos_device_id',
        'status',
        'notes',
    ];

    public function assignedCustomers(): HasMany
    {
        return $this->hasMany(FleetCustomer::class, 'assigned_agent_id');
    }

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }
}
