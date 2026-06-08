<?php

declare(strict_types=1);

namespace App\Models\Fleet;

use App\Models\Concerns\ResolvesRouteBindingForTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FleetRoute extends Model
{
    use ResolvesRouteBindingForTenant;

    public const STATUS_DRAFT = 'draft';

    public const STATUS_PLANNED = 'planned';

    public const STATUS_IN_PROGRESS = 'in_progress';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_CANCELLED = 'cancelled';

    protected $table = 'fleet_routes';

    protected $fillable = [
        'user_id',
        'agent_id',
        'route_date',
        'status',
        'notes',
        'started_at',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'route_date' => 'date',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function agent(): BelongsTo
    {
        return $this->belongsTo(FleetAgent::class, 'agent_id');
    }

    /** @return HasMany<FleetRouteStop, $this> */
    public function stops(): HasMany
    {
        return $this->hasMany(FleetRouteStop::class, 'route_id')->orderBy('sort_order');
    }

    public function isActiveToday(): bool
    {
        return in_array($this->status, [self::STATUS_PLANNED, self::STATUS_IN_PROGRESS], true);
    }
}
