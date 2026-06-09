<?php

declare(strict_types=1);

namespace App\Models\Fleet;

use App\Models\Concerns\ResolvesRouteBindingForTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FleetCustodyIssue extends Model
{
    use ResolvesRouteBindingForTenant;

    public const STATUS_DRAFT = 'draft';

    public const STATUS_ISSUED = 'issued';

    public const STATUS_VOID = 'void';

    protected $table = 'fleet_custody_issues';

    protected $fillable = [
        'user_id',
        'agent_id',
        'issue_number',
        'issued_on',
        'status',
        'notes',
        'recorded_by',
        'confirmed_at',
    ];

    protected function casts(): array
    {
        return [
            'issued_on' => 'date',
            'confirmed_at' => 'datetime',
        ];
    }

    public function agent(): BelongsTo
    {
        return $this->belongsTo(FleetAgent::class, 'agent_id');
    }

    /** @return HasMany<FleetCustodyIssueLine, $this> */
    public function lines(): HasMany
    {
        return $this->hasMany(FleetCustodyIssueLine::class, 'issue_id');
    }

    public function isIssued(): bool
    {
        return $this->status === self::STATUS_ISSUED;
    }

    public function isDraft(): bool
    {
        return $this->status === self::STATUS_DRAFT;
    }
}
