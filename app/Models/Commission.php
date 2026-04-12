<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Commission extends Model
{
    protected $fillable = [
        'user_id',
        'sales_invoice_id',
        'commission_rule_id',
        'base_amount',
        'rate_percent',
        'commission_amount',
        'calculated_at',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'base_amount' => 'decimal:4',
            'rate_percent' => 'decimal:2',
            'commission_amount' => 'decimal:4',
            'calculated_at' => 'date',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function salesInvoice(): BelongsTo
    {
        return $this->belongsTo(SalesInvoice::class);
    }

    public function commissionRule(): BelongsTo
    {
        return $this->belongsTo(CommissionRule::class);
    }
}
