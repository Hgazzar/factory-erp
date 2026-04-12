<?php

namespace App\Models;

use App\Models\Concerns\ResolvesRouteBindingForTenant;
use App\Models\Scopes\BelongsToAuthenticatedUserScope;
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
        'total',
        'currency',
        'vat_amount',
        'status',
        'debit_note_id',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope(new BelongsToAuthenticatedUserScope);

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
            'total' => 'decimal:4',
            'vat_amount' => 'decimal:4',
        ];
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

    public function items(): HasMany
    {
        return $this->hasMany(PurchaseReturnItem::class, 'purchase_return_id');
    }
}
