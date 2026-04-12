<?php

namespace App\Models;

use App\Models\Concerns\ResolvesRouteBindingForTenant;
use App\Models\Scopes\BelongsToAuthenticatedUserScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PurchaseInvoice extends Model
{
    use HasFactory;
    use ResolvesRouteBindingForTenant;

    public const STATUS_DRAFT = 'draft';

    public const STATUS_UNPAID = 'unpaid';

    public const STATUS_PARTIAL = 'partial';

    public const STATUS_PAID = 'paid';

    public const STATUS_OVERDUE = 'overdue';

    protected $fillable = [
        'user_id',
        'supplier_id',
        'warehouse_id',
        'date',
        'due_date',
        'reference',
        'supplier_invoice_number',
        'currency',
        'vat_rate',
        'vat_amount',
        'total',
        'paid_amount',
        'status',
        'notes',
        'internal_notes',
        'journal_entry_id',
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

    public function getBalanceAttribute(): float
    {
        return max(0, (float) $this->total - (float) $this->paid_amount);
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

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(PurchaseInvoiceItem::class);
    }

    /**
     * سندات الصرف المرتبطة بهذه الفاتورة.
     */
    public function payments(): BelongsToMany
    {
        return $this->belongsToMany(Payment::class, 'purchase_payment_invoices')
            ->withPivot('amount')
            ->withTimestamps();
    }

    /**
     * تحديث حالة الدفع من paid_amount و total وتاريخ الاستحقاق.
     */
    public function refreshPaymentStatus(): void
    {
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
}
