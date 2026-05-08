<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CrmCustomerMembership extends Model
{
    protected $table = 'crm_customer_memberships';

    protected $fillable = [
        'user_id',
        'customer_id',
        'loyalty_program_id',
        'start_date',
        'auto_renew',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'user_id' => 'integer',
            'customer_id' => 'integer',
            'loyalty_program_id' => 'integer',
            'start_date' => 'date',
            'auto_renew' => 'boolean',
        ];
    }

    public function scopeForTenant(Builder $query, int $tenantId): Builder
    {
        return $query->where('crm_customer_memberships.user_id', $tenantId);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    public function loyaltyProgram(): BelongsTo
    {
        return $this->belongsTo(CrmLoyaltyProgram::class, 'loyalty_program_id');
    }
}

