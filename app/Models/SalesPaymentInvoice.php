<?php

namespace App\Models;

use App\Models\Concerns\ResolvesRouteBindingForTenant;
use App\Models\Scopes\BelongsToAuthenticatedUserScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SalesPaymentInvoice extends Model
{
    use ResolvesRouteBindingForTenant;

    protected $fillable = [
        'user_id',
        'sales_payment_id',
        'sales_invoice_id',
        'amount_allocated',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope(new BelongsToAuthenticatedUserScope);

        static::creating(function (SalesPaymentInvoice $model): void {
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
            'amount_allocated' => 'decimal:4',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function salesPayment(): BelongsTo
    {
        return $this->belongsTo(SalesPayment::class);
    }

    public function salesInvoice(): BelongsTo
    {
        return $this->belongsTo(SalesInvoice::class);
    }
}
