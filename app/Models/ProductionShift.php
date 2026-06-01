<?php

namespace App\Models;

use App\Models\Concerns\ResolvesRouteBindingForTenant;
use App\Models\Scopes\BelongsToTenantContextScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductionShift extends Model
{
    use HasFactory;
    use ResolvesRouteBindingForTenant;

    public const STATUS_PLANNED = 'planned';
    public const STATUS_IN_PROGRESS = 'in_progress';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'user_id',
        'shift_id',
        'production_line_id',
        'machine_id',
        'date',
        'planned_start_at',
        'planned_end_at',
        'actual_start_at',
        'actual_end_at',
        'planned_quantity',
        'status',
        'notes',
        'is_active',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope(new BelongsToTenantContextScope);
    }

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'planned_start_at' => 'datetime',
            'planned_end_at' => 'datetime',
            'actual_start_at' => 'datetime',
            'actual_end_at' => 'datetime',
            'planned_quantity' => 'decimal:4',
            'is_active' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function shift(): BelongsTo
    {
        return $this->belongsTo(Shift::class);
    }

    public function productionLine(): BelongsTo
    {
        return $this->belongsTo(ProductionLine::class);
    }

    public function machine(): BelongsTo
    {
        return $this->belongsTo(Machine::class);
    }

    public function productionLogs(): HasMany
    {
        return $this->hasMany(ProductionLog::class);
    }

    public function getActualQuantityAttribute(): float
    {
        return (float) $this->productionLogs()->sum('quantity');
    }

    public function getRejectedQuantityAttribute(): float
    {
        return (float) $this->productionLogs()->sum('rejected_quantity');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
