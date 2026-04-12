<?php

namespace App\Models;

use App\Models\Concerns\ResolvesRouteBindingForTenant;
use App\Models\Scopes\BelongsToAuthenticatedUserScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SalesReturn extends Model
{
    use HasFactory;
    use ResolvesRouteBindingForTenant;

    protected $fillable = [
        'user_id',
        'reference',
        'date',
        'customer_id',
        'sales_invoice_id',
        'warehouse_id',
        'reason_type',
        'reason',
        'notes',
        'total',
        'vat_amount',
        'status',
        'refunded_amount',
        'journal_entry_id',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope(new BelongsToAuthenticatedUserScope);

        static::creating(function (SalesReturn $model): void {
            if (! $model->user_id && $model->sales_invoice_id) {
                $model->user_id = (int) (SalesInvoice::withoutGlobalScopes()
                    ->where('id', $model->sales_invoice_id)
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
            'refunded_amount' => 'decimal:4',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function salesInvoice(): BelongsTo
    {
        return $this->belongsTo(SalesInvoice::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(SalesReturnItem::class, 'sales_return_id');
    }

    public function journalEntry(): BelongsTo
    {
        return $this->belongsTo(JournalEntry::class);
    }
}
