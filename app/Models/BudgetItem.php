<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BudgetItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'budget_id',
        'account_id',
        'cost_center_id',
        'planned_amount',
        'monthly_amounts',
    ];

    protected function casts(): array
    {
        return [
            'planned_amount' => 'decimal:4',
            'monthly_amounts' => 'array',
        ];
    }

    public function budget(): BelongsTo
    {
        return $this->belongsTo(Budget::class);
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function costCenter(): BelongsTo
    {
        return $this->belongsTo(CostCenter::class);
    }
}

