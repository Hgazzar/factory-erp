<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductionLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'production_shift_id',
        'item_id',
        'quantity',
        'rejected_quantity',
        'scrap_reason',
        'logged_at',
        'notes',
        'downtime_reason',
        'downtime_lost_hours',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:4',
            'rejected_quantity' => 'decimal:4',
            'logged_at' => 'datetime',
            'downtime_lost_hours' => 'decimal:2',
        ];
    }

    public function productionShift(): BelongsTo
    {
        return $this->belongsTo(ProductionShift::class);
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }
}

