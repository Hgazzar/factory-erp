<?php

namespace App\Models;

use App\Models\Scopes\BelongsToTenantContextScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductionRecord extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'employee_id',
        'production_shift_id',
        'item_id',
        'quantity',
        'scrap_quantity',
        'scrap_reason',
        'recorded_at',
        'journal_entry_id',
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
            'scrap_quantity' => 'decimal:4',
            'recorded_at' => 'datetime',
            'downtime_lost_hours' => 'decimal:2',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function productionShift(): BelongsTo
    {
        return $this->belongsTo(ProductionShift::class);
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }

    public function journalEntry(): BelongsTo
    {
        return $this->belongsTo(JournalEntry::class);
    }
}
