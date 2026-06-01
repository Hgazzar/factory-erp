<?php

namespace App\Models;

use App\Models\Concerns\ResolvesRouteBindingForTenant;
use App\Models\Scopes\BelongsToAuthenticatedUserScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Contract extends Model
{
    use ResolvesRouteBindingForTenant;

    protected $fillable = [
        'user_id',
        'contract_number',
        'name',
        'name_ar',
        'type',
        'customer_id',
        'start_date',
        'end_date',
        'billing_cycle',
        'currency',
        'tax_percent',
        'reminder_days',
        'auto_renew',
        'next_invoice_date',
        'subtotal',
        'discount_amount',
        'tax_amount',
        'total',
        'status',
        'warehouse_id',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope(new BelongsToAuthenticatedUserScope);

        static::creating(function (Contract $model): void {
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
            'start_date' => 'date',
            'end_date' => 'date',
            'next_invoice_date' => 'date',
            'auto_renew' => 'boolean',
            'reminder_days' => 'integer',
            'tax_percent' => 'decimal:2',
            'subtotal' => 'decimal:4',
            'discount_amount' => 'decimal:4',
            'tax_amount' => 'decimal:4',
            'total' => 'decimal:4',
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

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(ContractItem::class, 'contract_id')->orderBy('sort_order');
    }

    public function salesInvoices(): HasMany
    {
        return $this->hasMany(SalesInvoice::class, 'contract_id');
    }

    public static function generateContractNumber(): string
    {
        $last = self::query()->orderByDesc('id')->first();
        $seq = $last ? ((int) preg_replace('/\D/', '', $last->contract_number ?? '0')) + 1 : 1;

        return 'CON-'.str_pad((string) $seq, 5, '0', STR_PAD_LEFT);
    }

    /** هل العقد قارب انتهاءه خلال أيام التذكير */
    public function isDueForReminder(): bool
    {
        if (! $this->end_date || $this->status !== 'active') {
            return false;
        }
        $daysLeft = now()->startOfDay()->diffInDays($this->end_date, false);

        return $daysLeft >= 0 && $daysLeft <= (int) $this->reminder_days;
    }

    /**
     * إنشاء فاتورة مبيعات مسودة من العقد وتحديث تاريخ الفاتورة التالية.
     */
    public function createDraftInvoice(): ?SalesInvoice
    {
        if ($this->status !== 'active' || ! $this->next_invoice_date) {
            return null;
        }
        $warehouseId = $this->warehouse_id
            ?? Warehouse::query()->where('user_id', $this->user_id)->where('is_active', true)->value('id');
        if (! $warehouseId) {
            return null;
        }
        $invoice = SalesInvoice::create([
            'user_id' => (int) $this->user_id,
            'customer_id' => $this->customer_id,
            'contract_id' => $this->id,
            'posting_source' => SalesInvoice::POSTING_SOURCE_DIRECT,
            'warehouse_id' => $warehouseId,
            'date' => $this->next_invoice_date,
            'due_date' => $this->next_invoice_date->copy()->addDays(30),
            'reference' => 'عقد-'.($this->contract_number ?? $this->id),
            'invoice_status' => 'draft',
            'payment_method' => 'credit',
            'vat_rate' => $this->tax_percent,
            'vat_amount' => $this->tax_amount,
            'total' => $this->total,
            'paid_amount' => 0,
        ]);
        foreach ($this->items as $line) {
            $invoice->items()->create([
                'item_id' => $line->item_id,
                'quantity' => $line->quantity,
                'unit_price' => $line->unit_price,
                'line_total' => $line->line_total,
            ]);
        }
        $next = $this->next_invoice_date->copy();
        if ($this->billing_cycle === 'monthly') {
            $next->addMonth();
        } elseif ($this->billing_cycle === 'quarterly') {
            $next->addMonths(3);
        } else {
            $next->addYear();
        }
        $this->update(['next_invoice_date' => $next]);

        return $invoice;
    }
}
