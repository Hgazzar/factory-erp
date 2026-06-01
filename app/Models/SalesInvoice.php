<?php

namespace App\Models;

use App\Models\Concerns\ResolvesRouteBindingForTenant;
use App\Models\Scopes\BelongsToTenantContextScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SalesInvoice extends Model
{
    use HasFactory;
    use ResolvesRouteBindingForTenant;

    public const STATUS_DRAFT = 'draft';

    public const STATUS_UNPAID = 'unpaid';

    public const STATUS_PARTIAL = 'partial';

    public const STATUS_PAID = 'paid';

    public const STATUS_OVERDUE = 'overdue';

    public const PAYMENT_CASH = 'cash';

    public const PAYMENT_CREDIT = 'credit';

    public const POSTING_SOURCE_ORDER = 'order';

    public const POSTING_SOURCE_DIRECT = 'direct';

    protected $fillable = [
        'user_id',
        'customer_id',
        'quotation_id',
        'sales_order_id',
        'posting_source',
        'contract_id',
        'service_order_id',
        'warehouse_id',
        'invoice_status',
        'status',
        'date',
        'due_date',
        'reference',
        'notes',
        'internal_notes',
        'terms',
        'payment_method',
        'subtotal',
        'vat_rate',
        'vat_amount',
        'total',
        'paid_amount',
        'journal_entry_id',
        'cogs_journal_entry_id',
        'posted_at',
        'inventory_posted_at',
        'zatca_invoice_uuid',
        'zatca_icv',
        'zatca_hash',
        'zatca_pih',
        'zatca_signed_xml',
        'zatca_status',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope(new BelongsToTenantContextScope);
    }

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'due_date' => 'date',
            'subtotal' => 'decimal:4',
            'vat_rate' => 'decimal:2',
            'vat_amount' => 'decimal:4',
            'total' => 'decimal:4',
            'paid_amount' => 'decimal:4',
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

    public function isCashSale(): bool
    {
        return $this->payment_method === self::PAYMENT_CASH;
    }

    public function isCreditSale(): bool
    {
        return $this->payment_method === self::PAYMENT_CREDIT;
    }

    public function getBalanceAttribute(): float
    {
        return max(0, (float) $this->total - (float) ($this->paid_amount ?? 0));
    }

    public function getStatusLabelAttribute(): string
    {
        $balance = $this->balance;
        if ($balance <= 0) {
            return 'مدفوعة';
        }
        if ($this->due_date && $this->due_date->isPast()) {
            return 'متأخرة';
        }

        return match ($this->status) {
            self::STATUS_PAID => 'مدفوعة',
            self::STATUS_PARTIAL => 'مدفوعة جزئياً',
            self::STATUS_OVERDUE => 'متأخرة',
            self::STATUS_DRAFT => 'مسودة',
            self::STATUS_UNPAID => 'غير مدفوعة',
            default => $this->total > 0 && $balance < $this->total ? 'مدفوعة جزئياً' : 'غير مدفوعة',
        };
    }

    public function getPaymentStatusAttribute(): string
    {
        return $this->status_label;
    }

    /**
     * تحديث حالة الدفع من paid_amount و total وتاريخ الاستحقاق.
     */
    public function refreshPaymentStatus(): void
    {
        if ($this->status === self::STATUS_DRAFT && ! $this->isPosted()) {
            return;
        }

        $total = (float) $this->total;
        $paid = (float) ($this->paid_amount ?? 0);
        $balance = max(0, $total - $paid);

        if ($balance <= 0.0001) {
            $this->status = self::STATUS_PAID;
        } elseif ($this->due_date && $this->due_date->isPast() && $balance > 0.0001) {
            $this->status = self::STATUS_OVERDUE;
        } elseif ($paid > 0.0001) {
            $this->status = self::STATUS_PARTIAL;
        } else {
            $this->status = self::STATUS_UNPAID;
        }
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

    public function salesOrder(): BelongsTo
    {
        return $this->belongsTo(SalesOrder::class);
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

    public function journalEntry(): BelongsTo
    {
        return $this->belongsTo(JournalEntry::class);
    }

    public function cogsJournalEntry(): BelongsTo
    {
        return $this->belongsTo(JournalEntry::class, 'cogs_journal_entry_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(SalesInvoiceItem::class);
    }

    public function crmActivities(): HasMany
    {
        return $this->hasMany(CrmActivity::class, 'sales_invoice_id');
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
}
