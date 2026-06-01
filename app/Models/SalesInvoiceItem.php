<?php

namespace App\Models;

use App\Models\Concerns\ResolvesRouteBindingForTenant;
use App\Models\Scopes\BelongsToTenantContextScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SalesInvoiceItem extends Model
{
    use HasFactory;
    use ResolvesRouteBindingForTenant;

    protected $fillable = [
        'user_id',
        'sales_invoice_id',
        'item_id',
        'description',
        'quantity',
        'unit_price',
        'discount',
        'vat_percent',
        'unit_cost',
        'line_total',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope(new BelongsToTenantContextScope);

        static::creating(function (SalesInvoiceItem $model): void {
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
            'quantity' => 'decimal:4',
            'unit_price' => 'decimal:4',
            'discount' => 'decimal:4',
            'vat_percent' => 'decimal:2',
            'unit_cost' => 'decimal:4',
            'line_total' => 'decimal:4',
        ];
    }

    /**
     * نسبة الضريبة الفعلية للبند (من إعدادات المنشأة إذا لم تُحدَّد).
     */
    public function resolvedVatPercent(?int $tenantUserId = null): float
    {
        if ($this->vat_percent !== null) {
            return (float) $this->vat_percent;
        }

        $uid = $tenantUserId ?? (int) ($this->user_id ?? $this->salesInvoice?->user_id);

        return CompanySetting::resolvedDefaultVatPercent($uid > 0 ? $uid : null);
    }

    public function netLineAmount(): float
    {
        return max(0, (float) $this->quantity * (float) $this->unit_price - (float) ($this->discount ?? 0));
    }

    public function vatLineAmount(?int $tenantUserId = null): float
    {
        return round($this->netLineAmount() * $this->resolvedVatPercent($tenantUserId) / 100, 4);
    }

    public function cogsLineAmount(): float
    {
        $cost = (float) ($this->unit_cost ?? $this->item?->cost ?? 0);

        return round((float) $this->quantity * $cost, 4);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function salesInvoice(): BelongsTo
    {
        return $this->belongsTo(SalesInvoice::class);
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }
}
