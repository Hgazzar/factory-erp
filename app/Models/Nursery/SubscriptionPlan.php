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

    public function hourRates(): HasMany
    {
        return $this->hasMany(SubscriptionPlanHourRate::class, 'plan_id')->orderBy('hours');
    }

    public function amountAfterTax(): float
    {
        $base = (float) $this->amount;
        $tax = (float) $this->tax_rate;

        return round($base * (1 + ($tax / 100)), 2);
    }

    public function amountAfterTaxForHours(?int $hours): float
    {
        if ($hours === null || $hours < 1) {
            return $this->amountAfterTax();
        }

        $rate = $this->relationLoaded('hourRates')
            ? $this->hourRates->firstWhere('hours', $hours)
            : $this->hourRates()->where('hours', $hours)->first();

        if ($rate instanceof SubscriptionPlanHourRate) {
            return $rate->amountAfterTax((float) $this->tax_rate);
        }

        return $this->amountAfterTax();
    }

    public function hasHourRates(): bool
    {
        if ($this->relationLoaded('hourRates')) {
            return $this->hourRates->isNotEmpty();
        }

        return $this->hourRates()->exists();
    }

    /**
     * ملخص للعرض في قائمة الخطط عندما تعتمد الأسعار على باقات الساعات.
     *
     * @return array{count: int, min_before: float, max_before: float, min_after: float, max_after: float}|null
     */
    public function hourRatesSummary(): ?array
    {
        $rates = $this->relationLoaded('hourRates')
            ? $this->hourRates
            : $this->hourRates()->orderBy('hours')->get();

        if ($rates->isEmpty()) {
            return null;
        }

        $amounts = $rates->map(fn (SubscriptionPlanHourRate $r) => (float) $r->amount)->filter(fn ($a) => $a >= 0);
        if ($amounts->isEmpty()) {
            return null;
        }

        $minBefore = (float) $amounts->min();
        $maxBefore = (float) $amounts->max();
        $tax = (float) $this->tax_rate;

        return [
            'count' => $rates->count(),
            'min_before' => $minBefore,
            'max_before' => $maxBefore,
            'min_after' => round($minBefore * (1 + ($tax / 100)), 2),
            'max_after' => round($maxBefore * (1 + ($tax / 100)), 2),
        ];
    }
}
