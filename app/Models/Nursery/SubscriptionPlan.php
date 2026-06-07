<?php

declare(strict_types=1);

namespace App\Models\Nursery;

use App\Models\Concerns\ResolvesRouteBindingForTenant;
use App\Models\Scopes\BelongsToTenantContextScope;
use App\Models\User;
use Illuminate\Database\Eloquent\Attributes\ScopedBy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[ScopedBy([BelongsToTenantContextScope::class])]
class SubscriptionPlan extends Model
{
    use ResolvesRouteBindingForTenant;

    protected $table = 'nursery_subscription_plans';

    protected $fillable = [
        'user_id',
        'name',
        'plan_type',
        'amount',
        'tax_rate',
        'currency_code',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'tax_rate' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    public function tenantUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class, 'plan_id');
    }

    public function amountAfterTax(): float
    {
        $base = (float) $this->amount;
        $tax = (float) $this->tax_rate;

        return round($base * (1 + ($tax / 100)), 2);
    }
}
