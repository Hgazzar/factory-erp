<?php

namespace App\Models;

use App\Models\Concerns\ResolvesRouteBindingForTenant;
use App\Models\Scopes\BelongsToAuthenticatedUserScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PurchaseInvoiceItem extends Model
{
    use HasFactory;
    use ResolvesRouteBindingForTenant;

    protected $fillable = [
        'user_id',
        'purchase_invoice_id',
        'item_id',
        'description',
        'quantity',
        'unit_price',
        'discount',
        'vat_percent',
        'line_total',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope(new BelongsToAuthenticatedUserScope);

        static::creating(function (PurchaseInvoiceItem $model): void {
            if (! $model->user_id && $model->purchase_invoice_id) {
                $model->user_id = (int) (PurchaseInvoice::withoutGlobalScopes()
                    ->where('id', $model->purchase_invoice_id)
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
            'line_total' => 'decimal:4',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function purchaseInvoice(): BelongsTo
    {
        return $this->belongsTo(PurchaseInvoice::class);
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }
}

