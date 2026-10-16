<?php

declare(strict_types=1);

namespace App\Services\Nursery;

use App\Models\Nursery\SubscriptionPlan;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;

final class NurserySubscriptionPlanService
{
    public const TYPE_HOURLY = 'hourly';

    public const TYPE_DAILY = 'daily';

    public const TYPE_WEEKLY = 'weekly';

    /** @deprecated Legacy rows only — not offered as a nursery plan type. */
    public const TYPE_MONTHLY = 'monthly';

    public const TYPE_TERM = 'term';

    public const TYPE_CUSTOM = 'custom';

    /** @deprecated Legacy DB rows only — not offered in the UI. */
    public const TYPE_YEARLY = 'yearly';

    /**
     * Canonical duration order everywhere: ساعة → يومي → أسبوعي → فصلي.
     *
     * @return array<string, string>
     */
    public function planTypeOptions(): array
    {
        return [
            self::TYPE_HOURLY => 'ساعة',
            self::TYPE_DAILY => 'يومي',
            self::TYPE_WEEKLY => 'أسبوعي',
            self::TYPE_TERM => 'فصلي',
            self::TYPE_CUSTOM => 'مخصص',
        ];
    }

    public function planTypeSortRank(string $planType): int
    {
        return match ($planType) {
            self::TYPE_HOURLY => 1,
            self::TYPE_DAILY => 2,
            self::TYPE_WEEKLY => 3,
            self::TYPE_TERM => 4,
            self::TYPE_CUSTOM => 90,
            default => 99,
        };
    }

    /**
     * @param  Collection<int, SubscriptionPlan>  $plans
     * @return Collection<int, SubscriptionPlan>
     */
    public function sortByDuration(Collection $plans): Collection
    {
        return $plans
            ->sortBy(fn (SubscriptionPlan $plan) => sprintf(
                '%03d-%s',
                $this->planTypeSortRank((string) ($plan->plan_type ?? '')),
                mb_strtolower((string) $plan->name)
            ))
            ->values();
    }

    /**
     * End date for a period starting on $startsOn (inclusive start).
     * Example weekly: 2025-09-19 → 2025-09-26.
     * Returns null for custom (no automatic duration).
     */
    public function endsOnForPlanType(CarbonInterface|string $startsOn, string $planType): ?Carbon
    {
        $start = Carbon::parse($startsOn)->startOfDay();

        return match ($planType) {
            self::TYPE_HOURLY => $start->copy(),
            self::TYPE_DAILY => $start->copy()->addDay(),
            self::TYPE_WEEKLY => $start->copy()->addWeek(),
            self::TYPE_MONTHLY => $start->copy()->addMonth(),
            self::TYPE_TERM => $start->copy()->addMonths(3),
            self::TYPE_YEARLY => $start->copy()->addYear(),
            default => null,
        };
    }

    /**
     * @return array<string, string>
     */
    public function currencyOptions(): array
    {
        return [
            'SAR' => 'الريال السعودي',
        ];
    }

    /**
     * @return Collection<int, SubscriptionPlan>
     */
    public function listForTenant(int $tenantUserId): Collection
    {
        return $this->sortByDuration(
            SubscriptionPlan::query()
                ->where('user_id', $tenantUserId)
                ->get()
        );
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(int $tenantUserId, array $data): SubscriptionPlan
    {
        $payload = $this->normalizePayload($data);

        if (SubscriptionPlan::query()->where('user_id', $tenantUserId)->where('name', $payload['name'])->exists()) {
            throw ValidationException::withMessages(['name' => 'يوجد خطة بنفس الاسم.']);
        }

        return SubscriptionPlan::query()->create([
            'user_id' => $tenantUserId,
            ...$payload,
            'is_active' => true,
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(SubscriptionPlan $plan, int $tenantUserId, array $data): SubscriptionPlan
    {
        abort_unless((int) $plan->user_id === $tenantUserId, 404);

        $payload = $this->normalizePayload($data);

        $duplicate = SubscriptionPlan::query()
            ->where('user_id', $tenantUserId)
            ->where('name', $payload['name'])
            ->whereKeyNot($plan->id)
            ->exists();

        if ($duplicate) {
            throw ValidationException::withMessages(['name' => 'يوجد خطة بنفس الاسم.']);
        }

        $plan->update($payload);

        return $plan->fresh();
    }

    public function deactivate(SubscriptionPlan $plan, int $tenantUserId): void
    {
        abort_unless((int) $plan->user_id === $tenantUserId, 404);

        if ($plan->subscriptions()->where('status', 'active')->exists()) {
            throw new InvalidArgumentException('لا يمكن حذف خطة مرتبطة باشتراكات نشطة.');
        }

        $plan->update(['is_active' => false]);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array{name: string, plan_type: string, amount: float, tax_rate: float, currency_code: string}
     */
    private function normalizePayload(array $data): array
    {
        $name = trim((string) ($data['name'] ?? ''));
        if ($name === '') {
            throw new InvalidArgumentException('اسم الخطة مطلوب.');
        }

        $planType = (string) ($data['plan_type'] ?? self::TYPE_CUSTOM);
        if (! array_key_exists($planType, $this->planTypeOptions())) {
            throw new InvalidArgumentException('نوع الخطة غير صالح.');
        }

        $amount = (float) ($data['amount'] ?? 0);
        if ($amount < 0) {
            throw new InvalidArgumentException('قيمة الاشتراك يجب أن تكون موجبة.');
        }

        $taxRate = (float) ($data['tax_rate'] ?? 15);
        if ($taxRate < 0 || $taxRate > 100) {
            throw new InvalidArgumentException('نسبة الضريبة غير صالحة.');
        }

        $currency = strtoupper(trim((string) ($data['currency_code'] ?? 'SAR')));
        if (! array_key_exists($currency, $this->currencyOptions())) {
            throw new InvalidArgumentException('العملة غير مدعومة.');
        }

        return [
            'name' => $name,
            'plan_type' => $planType,
            'amount' => round($amount, 2),
            'tax_rate' => round($taxRate, 2),
            'currency_code' => $currency,
        ];
    }
}
