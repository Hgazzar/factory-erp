<?php

namespace App\Models;

use App\Models\Concerns\ResolvesRouteBindingForTenant;
use App\Models\Scopes\BelongsToAuthenticatedUserScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SalesInvoice extends Model
{
    use HasFactory;
    use ResolvesRouteBindingForTenant;

    protected $fillable = [
        'user_id',
        'customer_id',
        'quotation_id',
        'contract_id',
        'service_order_id',
        'warehouse_id',
        'invoice_status',
        'date',
        'due_date',
        'reference',
        'notes',
        'internal_notes',
        'terms',
        'payment_method',
        'vat_rate',
        'vat_amount',
        'total',
        'paid_amount',
        'journal_entry_id',
        'zatca_invoice_uuid',
        'zatca_icv',
        'zatca_hash',
        'zatca_pih',
        'zatca_signed_xml',
        'zatca_status',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope(new BelongsToAuthenticatedUserScope);
    }

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'due_date' => 'date',
            'vat_rate' => 'decimal:2',
            'vat_amount' => 'decimal:4',
            'total' => 'decimal:4',
            'paid_amount' => 'decimal:4',
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

    public function quotation(): BelongsTo
    {
        return $this->belongsTo(Quotation::class);
    }

    public function contract(): BelongsTo
    {
        return $this->belongsTo(Contract::class);
    }

    public function serviceOrder(): BelongsTo
    {
        return $this->belongsTo(ServiceOrder::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(SalesInvoiceItem::class);
    }

    public function payments(): BelongsToMany
    {
        return $this->belongsToMany(SalesPayment::class, 'sales_payment_invoices')
            ->withPivot('amount_allocated')
            ->withTimestamps();
    }

    public function paymentAllocations(): HasMany
    {
        return $this->hasMany(SalesPaymentInvoice::class);
    }

    public function salesReturns(): HasMany
    {
        return $this->hasMany(SalesReturn::class, 'sales_invoice_id');
    }

    public function installments(): HasMany
    {
        return $this->hasMany(Installment::class, 'sales_invoice_id');
    }

    public function commissions(): HasMany
    {
        return $this->hasMany(Commission::class);
    }

    public function getBalanceAttribute(): float
    {
        return max(0, (float) $this->total - (float) $this->paid_amount);
    }

    public function getPaymentStatusAttribute(): string
    {
        $total = (float) $this->total;
        $paid = (float) $this->paid_amount;
        if ($paid >= $total) {
            return 'مدفوعة';
        }
        if ($paid > 0) {
            return 'مدفوعة جزئياً';
        }

        return 'غير مدفوعة';
    }
}
