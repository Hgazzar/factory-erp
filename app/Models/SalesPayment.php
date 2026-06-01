<?php

namespace App\Models;

use App\Models\Concerns\ResolvesRouteBindingForTenant;
use App\Models\Scopes\BelongsToTenantContextScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SalesPayment extends Model
{
    use HasFactory;
    use ResolvesRouteBindingForTenant;

    public const METHOD_CASH = 'cash';

    public const METHOD_TRANSFER = 'transfer';

    public const METHOD_CARD = 'card';

    protected $fillable = [
        'user_id',
        'customer_id',
        'date',
        'payment_method',
        'amount',
        'reference',
        'notes',
        'journal_entry_id',
        'created_by',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope(new BelongsToTenantContextScope);

        static::creating(function (SalesPayment $model): void {
            if (! $model->user_id && $model->customer_id) {
                $model->user_id = (int) (Customer::withoutGlobalScopes()
                    ->where('id', $model->customer_id)
                    ->value('user_id') ?? auth()->id() ?? 1);
            }
        });
    }

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'amount' => 'decimal:4',
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

    public function journalEntry(): BelongsTo
    {
        return $this->belongsTo(JournalEntry::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function allocations(): HasMany
    {
        return $this->hasMany(SalesPaymentInvoice::class);
    }

    public function invoices(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(SalesInvoice::class, 'sales_payment_invoices')
            ->withPivot('amount_allocated')
            ->withTimestamps();
    }

    public function getAllocatedAmountAttribute(): float
    {
        return (float) $this->allocations()->sum('amount_allocated');
    }

    public function getUnallocatedAmountAttribute(): float
    {
        return max(0, (float) $this->amount - $this->allocated_amount);
    }

    public static function paymentMethodLabels(): array
    {
        return [
            self::METHOD_CASH => 'نقدي',
            self::METHOD_TRANSFER => 'تحويل بنكي',
            self::METHOD_CARD => 'مدى/بطاقة',
        ];
    }
}
