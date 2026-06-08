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
    ];

    public function assignedAgent(): BelongsTo
    {
        return $this->belongsTo(FleetAgent::class, 'assigned_agent_id');
    }
}
