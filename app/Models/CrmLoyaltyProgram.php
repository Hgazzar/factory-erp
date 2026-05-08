<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CrmLoyaltyProgram extends Model
{
    protected $table = 'crm_loyalty_programs';

    protected $fillable = [
        'user_id',
        'code',
        'name',
        'name_ar',
        'description',
        'points_name',
        'earning_rate',
        'redemption_rate',
        'min_transaction_amount',
        'min_redemption_points',
        'max_redemption_percentage',
        'earn_on_discounts',
        'earn_on_tax',
        'has_expiration',
        'start_date',
        'end_date',
        'tiers_count',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'earning_rate' => 'decimal:2',
            'redemption_rate' => 'decimal:4',
            'min_transaction_amount' => 'decimal:2',
            'min_redemption_points' => 'decimal:2',
            'max_redemption_percentage' => 'decimal:2',
            'earn_on_discounts' => 'boolean',
            'earn_on_tax' => 'boolean',
            'has_expiration' => 'boolean',
            'start_date' => 'date',
            'end_date' => 'date',
            'tiers_count' => 'integer',
        ];
    }

    public function scopeForTenant(Builder $query, int $userId): Builder
    {
        return $query->where('crm_loyalty_programs.user_id', $userId);
    }

    /**
     * @return array<string, string>
     */
    public static function statusLabels(): array
    {
        return [
            'active' => 'نشط',
            'inactive' => 'متوقف',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function accounts(): HasMany
    {
        return $this->hasMany(CrmLoyaltyAccount::class, 'loyalty_program_id');
    }
}
