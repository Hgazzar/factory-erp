<?php

namespace App\Models;

use App\Models\Concerns\ResolvesRouteBindingForTenant;
use App\Models\Scopes\BelongsToTenantContextScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PurchaseReturn extends Model
{
    use HasFactory;
    use ResolvesRouteBindingForTenant;

    public const STATUS_PENDING = 'pending';

    public const STATUS_SHIPPED = 'shipped';

    public const STATUS_COMPLETED = 'completed';

    protected $fillable = [
        'user_id',
        'code',
        'date',
        'supplier_id',
        'purchase_invoice_id',
        'warehouse_id',
        'reason_type',
        'reason',
        'reference',
        'notes',
        'internal_notes',
        'subtotal',
        'total',
        'currency',
        'vat_amount',
        'status',
        'debit_note_id',
        'journal_entry_id',
        'posted_at',
        'inventory_posted_at',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope(new BelongsToTenantContextScope);

        static::creating(function (PurchaseReturn $model): void {
            if (! $model->user_id && $model->supplier_id) {
                $model->user_id = (int) (Supplier::withoutGlobalScopes()
                    ->where('id', $model->supplier_id)
                    ->value('user_id') ?? auth()->id() ?? 1);
            }
        });
    }

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'subtotal' => 'decimal:4',
            'total' => 'decimal:4',
            'vat_amount' => 'decimal:4',
            'posted_at' => 'datetime',
            'inventory_posted_at' => 'datetime',
        ];
    }

    public function isPosted(): bool
    {
        return $this->posted_at !== null && $this->journal_entry_id !== null;
    }

    public function isInventoryPosted(): bool
    {
        return $this->inventory_posted_at !== null;
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_PENDING => 'معلق',
            self::STATUS_SHIPPED => 'مشحون',
            self::STATUS_COMPLETED => 'مكتمل',
            default => (string) $this->status,
        };
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function purchaseInvoice(): BelongsTo
    {
        return $this->belongsTo(PurchaseInvoice::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function debitNote(): BelongsTo
    {
        return $this->belongsTo(DebitNote::class);
    }

    public function journalEntry(): BelongsTo
    {
        return $this->belongsTo(JournalEntry::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(PurchaseReturnItem::class, 'purchase_return_id');
    }
}
