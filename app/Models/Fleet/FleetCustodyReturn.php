<?php

declare(strict_types=1);

namespace App\Models\Fleet;

use App\Models\Concerns\ResolvesRouteBindingForTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FleetCustodyReturn extends Model
{
    use ResolvesRouteBindingForTenant;

    public const STATUS_DRAFT = 'draft';

    public const STATUS_CONFIRMED = 'confirmed';

    public const STATUS_VOID = 'void';

    protected $table = 'fleet_custody_returns';

    protected $fillable = [
        'user_id',
        'agent_id',
        'return_number',
        'returned_on',
        'status',
        'notes',
        'recorded_by',
        'confirmed_at',
    ];

    protected function casts(): array
    {
        return [
            'returned_on' => 'date',
            'confirmed_at' => 'datetime',
        ];
    }

    public function agent(): BelongsTo
    {
        return $this->belongsTo(FleetAgent::class, 'agent_id');
    }

    /** @return HasMany<FleetCustodyReturnLine, $this> */
    public function lines(): HasMany
    {
        return $this->hasMany(FleetCustodyReturnLine::class, 'return_id');
    }

    public function isDraft(): bool
    {
        return $this->status === self::STATUS_DRAFT;
    }

    public function isConfirmed(): bool
    {
        return $this->status === self::STATUS_CONFIRMED;
    }
}
