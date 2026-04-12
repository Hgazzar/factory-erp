<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Installment extends Model
{
    use HasFactory;

    protected $fillable = [
        'sales_invoice_id',
        'installment_number',
        'due_date',
        'amount',
        'paid_amount',
    ];

    protected function casts(): array
    {
        return [
            'due_date' => 'date',
            'amount' => 'decimal:4',
            'paid_amount' => 'decimal:4',
        ];
    }

    public function salesInvoice(): BelongsTo
    {
        return $this->belongsTo(SalesInvoice::class);
    }

    /** الرصيد المستحق للقسط */
    public function getBalanceAttribute(): float
    {
        return max(0, (float) $this->amount - (float) $this->paid_amount);
    }

    /** أيام التأخير (موجب = متأخر) */
    public function getDaysOverdueAttribute(): int
    {
        if ($this->balance <= 0 || ! $this->due_date->isPast()) {
            return 0;
        }
        return (int) $this->due_date->startOfDay()->diffInDays(now()->startOfDay());
    }

    /** الحالة المحسوبة */
    public function getStatusAttribute(): string
    {
        $paid = (float) $this->paid_amount;
        $total = (float) $this->amount;
        if ($paid >= $total) {
            return 'مدفوع';
        }
        if ($this->days_overdue > 0) {
            return 'متأخر';
        }
        $due = $this->due_date->startOfDay();
        $startOfWeek = now()->startOfWeek();
        $endOfWeek = now()->endOfWeek();
        if ($due->between($startOfWeek, $endOfWeek)) {
            return 'مستحق هذا الأسبوع';
        }
        return 'قادم';
    }

    /**
     * توزيع مبلغ مخصص للفاتورة على أقساطها (الأقدم استحقاقاً أولاً).
     */
    public static function distributePaymentToInvoice(int $salesInvoiceId, float $amount): void
    {
        $remaining = $amount;
        $installments = Installment::where('sales_invoice_id', $salesInvoiceId)
            ->orderBy('due_date')
            ->get();

        foreach ($installments as $inst) {
            if ($remaining <= 0) {
                break;
            }
            $balance = (float) $inst->amount - (float) $inst->paid_amount;
            if ($balance <= 0) {
                continue;
            }
            $toAdd = min($remaining, $balance);
            $inst->increment('paid_amount', $toAdd);
            $remaining -= $toAdd;
        }
    }
}
