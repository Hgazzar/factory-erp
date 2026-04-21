<?php

namespace App\Models;

use App\Models\Concerns\ResolvesRouteBindingForTenant;
use App\Models\Scopes\BelongsToAuthenticatedUserScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ManufacturingRun extends Model
{
    use ResolvesRouteBindingForTenant;

    public const STATUS_DRAFT = 'draft';

    public const STATUS_POSTED = 'posted';

    protected $fillable = [
        'user_id',
        'bom_list_id',
        'reference',
        'status',
        'production_date',
        'start_date',
        'due_date',
        'warehouse_id',
        'machine_id',
        'finished_item_id',
        'quantity_produced',
        'journal_entry_id',
        'total_materials_cost',
        'notes',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope(new BelongsToAuthenticatedUserScope);
    }

    protected function casts(): array
    {
        return [
            'production_date' => 'date',
            'start_date' => 'date',
            'due_date' => 'date',
            'quantity_produced' => 'decimal:4',
            'total_materials_cost' => 'decimal:4',
        ];
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function bomList(): BelongsTo
    {
        return $this->belongsTo(BomList::class);
    }

    public function machine(): BelongsTo
    {
        return $this->belongsTo(Machine::class);
    }

    public function finishedItem(): BelongsTo
    {
        return $this->belongsTo(Item::class, 'finished_item_id');
    }

    public function journalEntry(): BelongsTo
    {
        return $this->belongsTo(JournalEntry::class);
    }

    public function lines(): HasMany
    {
        return $this->hasMany(ManufacturingRunLine::class);
    }

    public function isDraft(): bool
    {
        return $this->status === self::STATUS_DRAFT;
    }

    public function isPosted(): bool
    {
        return $this->status === self::STATUS_POSTED;
    }

    /**
     * نسبة تقدّم بسيطة للواجهة: مسودة = قيد التنفيذ، مرحّل = مكتمل.
     */
    public function progressPercent(): int
    {
        return $this->status === self::STATUS_POSTED ? 100 : 50;
    }
}
