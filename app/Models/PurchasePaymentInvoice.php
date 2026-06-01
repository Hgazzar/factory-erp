<?php

namespace App\Models;

use App\Models\Concerns\ResolvesRouteBindingForTenant;
use App\Models\Scopes\BelongsToTenantContextScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PurchasePaymentInvoice extends Model
{
    use ResolvesRouteBindingForTenant;

    protected $table = 'purchase_payment_invoices';

    protected $fillable = [
        'user_id',
        'payment_id',
        'purchase_invoice_id',
        'amount',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope(new BelongsToTenantContextScope);

        static::creating(function (PurchasePaymentInvoice $model): void {
            if (! $model->user_id && $model->payment_id) {
                $model->user_id = (int) (Payment::withoutGlobalScopes()
                    ->where('id', $model->payment_id)
                    ->value('user_id') ?? auth()->id() ?? 1);
            }
        });
    }

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:4',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }

    public function purchaseInvoice(): BelongsTo
    {
        return $this->belongsTo(PurchaseInvoice::class);
    }
}
