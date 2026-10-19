<?php

declare(strict_types=1);

namespace App\Models\Nursery;

use App\Models\Concerns\ResolvesRouteBindingForTenant;
use App\Models\Scopes\BelongsToTenantContextScope;
use App\Models\User;
use Illuminate\Database\Eloquent\Attributes\ScopedBy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[ScopedBy([BelongsToTenantContextScope::class])]
class SubscriptionPlanHourRate extends Model
{
    use ResolvesRouteBindingForTenant;

    protected $table = 'nursery_subscription_plan_hour_rates';

    protected $fillable = [
        'user_id',
        'plan_id',
        'hours',
        'amount',
    ];

    protected function casts(): array
    {
        return [
            'hours' => 'integer',
            'amount' => 'decimal:2',
        ];
    }

    public function tenantUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(SubscriptionPlan::class, 'plan_id');
    }

    public function amountAfterTax(float $taxRate): float
    {
        return round((float) $this->amount * (1 + ($taxRate / 100)), 2);
    }
}
