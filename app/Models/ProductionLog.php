<?php

namespace App\Models;

use App\Models\Scopes\BelongsToTenantContextScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductionLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'production_shift_id',
        'item_id',
        'warehouse_id',
        'quantity',
        'rejected_quantity',
        'scrap_reason',
        'logged_at',
        'notes',
        'downtime_reason',
        'downtime_lost_hours',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope(new BelongsToTenantContextScope);
    }

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:4',
            'rejected_quantity' => 'decimal:4',
            'logged_at' => 'datetime',
            'downtime_lost_hours' => 'decimal:2',
            'inventory_synced_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function productionShift(): BelongsTo
    {
        return $this->belongsTo(ProductionShift::class);
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }
}
