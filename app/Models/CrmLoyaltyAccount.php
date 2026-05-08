<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CrmLoyaltyAccount extends Model
{
    protected $table = 'crm_loyalty_accounts';

    protected $fillable = [
        'user_id',
        'customer_id',
        'loyalty_program_id',
        'total_points',
        'used_points',
    ];

    protected function casts(): array
    {
        return [
            'total_points' => 'decimal:2',
            'used_points' => 'decimal:2',
            'current_balance' => 'decimal:2',
        ];
    }

    public function scopeForTenant(Builder $query, int $userId): Builder
    {
        return $query->where('crm_loyalty_accounts.user_id', $userId);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function loyaltyProgram(): BelongsTo
    {
        return $this->belongsTo(CrmLoyaltyProgram::class, 'loyalty_program_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
