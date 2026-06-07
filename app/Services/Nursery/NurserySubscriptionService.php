<?php

declare(strict_types=1);

namespace App\Services\Nursery;

use App\Models\Nursery\Child;
use App\Models\Nursery\Subscription;
use App\Models\Nursery\SubscriptionPlan;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use RuntimeException;

final class NurserySubscriptionService
{
    public function __construct(
        private readonly NurserySubscriptionAccountingService $accounting,
        private readonly NurseryWhatsAppNotificationService $whatsapp,
    ) {}

    public function ensureDefaultPlans(int $tenantUserId): void
    {
        if (SubscriptionPlan::query()->where('user_id', $tenantUserId)->exists()) {
            return;
        }

        foreach (
            [
                ['name' => 'شهري', 'plan_type' => 'monthly', 'amount' => 2000],
                ['name' => 'فصلي', 'plan_type' => 'term', 'amount' => 5500],
                ['name' => 'سنوي', 'plan_type' => 'yearly', 'amount' => 18000],
            ] as $plan
        ) {
            SubscriptionPlan::query()->create([
                'user_id' => $tenantUserId,
                'name' => $plan['name'],
                'plan_type' => $plan['plan_type'],
                'amount' => $plan['amount'],
                'tax_rate' => 15,
                'currency_code' => 'SAR',
                'is_active' => true,
            ]);
        }
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array{subscription: Subscription, finance_posted: bool, whatsapp_sent: bool}
     */
    public function create(int $tenantUserId, array $data, ?int $createdBy = null): array
    {
        $childId = (int) ($data['child_id'] ?? 0);
        $planId = (int) ($data['plan_id'] ?? 0);

        Child::query()
            ->where('user_id', $tenantUserId)
            ->whereKey($childId)
            ->where('status', Child::STATUS_ACTIVE)
            ->firstOrFail();

        $plan = SubscriptionPlan::query()
            ->where('user_id', $tenantUserId)
            ->whereKey($planId)
            ->where('is_active', true)
            ->firstOrFail();

        $startsOn = (string) ($data['starts_on'] ?? '');
        $endsOn = (string) ($data['ends_on'] ?? '');

        if ($startsOn === '' || $endsOn === '') {
            throw new InvalidArgumentException('تاريخ البداية والانتهاء مطلوبان.');
        }

        if ($endsOn < $startsOn) {
            throw new InvalidArgumentException('تاريخ الانتهاء يجب أن يكون بعد البداية.');
        }

        $discount = max(0, (float) ($data['discount_amount'] ?? 0));
        $amountAfterTax = isset($data['amount_after_tax']) && $data['amount_after_tax'] !== ''
            ? (float) $data['amount_after_tax']
            : $plan->amountAfterTax();

        if ($discount > $amountAfterTax) {
            throw new InvalidArgumentException('الخصم لا يمكن أن يتجاوز قيمة الاشتراك.');
        }

        $notes = trim((string) ($data['notes'] ?? ''));
        if (strlen($notes) > 500) {
            throw new InvalidArgumentException('الملاحظة طويلة جداً (500 حرف).');
        }

        $isPaid = filter_var($data['is_paid'] ?? false, FILTER_VALIDATE_BOOL);

        $subscription = Subscription::query()->create([
            'user_id' => $tenantUserId,
            'child_id' => $childId,
            'plan_id' => $planId,
            'starts_on' => $startsOn,
            'ends_on' => $endsOn,
            'amount_after_tax' => $amountAfterTax,
            'discount_amount' => $discount,
            'notes' => $notes !== '' ? $notes : null,
            'is_paid' => $isPaid,
            'status' => Subscription::STATUS_ACTIVE,
            'created_by' => $createdBy,
        ]);

        $financePosted = false;
        $whatsappSent = false;

        if ($isPaid) {
            $financePosted = $this->tryRecordFinance($subscription, $tenantUserId);
            $whatsappSent = $this->whatsapp->sendSubscriptionPaidConfirmation($tenantUserId, $subscription->fresh());
        }

        return [
            'subscription' => $subscription->fresh(),
            'finance_posted' => $financePosted,
            'whatsapp_sent' => $whatsappSent,
        ];
    }

    public function cancel(Subscription $subscription, int $tenantUserId): Subscription
    {
        abort_unless((int) $subscription->user_id === $tenantUserId, 404);

        $subscription->update(['status' => Subscription::STATUS_CANCELLED]);

        return $subscription->fresh();
    }

    /**
     * @return array{sent: int, skipped: int}
     */
    public function sendPaymentReminders(int $tenantUserId): array
    {
        $items = Subscription::query()
            ->where('user_id', $tenantUserId)
            ->where('status', Subscription::STATUS_ACTIVE)
            ->where('is_paid', false)
            ->where('ends_on', '>=', now()->toDateString())
            ->whereNull('payment_reminder_sent_at')
            ->get();

        $sent = 0;
        $skipped = 0;

        foreach ($items as $subscription) {
            if ($this->whatsapp->sendPaymentReminder($tenantUserId, $subscription)) {
                $subscription->update(['payment_reminder_sent_at' => now()]);
                $sent++;
            } else {
                $skipped++;
            }
        }

        return ['sent' => $sent, 'skipped' => $skipped];
    }

    /**
     * @return array{sent: int, skipped: int}
     */
    public function sendRenewalReminders(int $tenantUserId, ?int $withinDays = null): array
    {
        $withinDays ??= (int) config('nursery.subscriptions.renewal_reminder_days', 30);
        $until = now()->addDays($withinDays)->toDateString();

        $items = Subscription::query()
            ->where('user_id', $tenantUserId)
            ->where('status', Subscription::STATUS_ACTIVE)
            ->whereBetween('ends_on', [now()->toDateString(), $until])
            ->whereNull('renewal_reminder_sent_at')
            ->get();

        $sent = 0;
        $skipped = 0;

        foreach ($items as $subscription) {
            if ($this->whatsapp->sendRenewalReminder($tenantUserId, $subscription)) {
                $subscription->update(['renewal_reminder_sent_at' => now()]);
                $sent++;
            } else {
                $skipped++;
            }
        }

        return ['sent' => $sent, 'skipped' => $skipped];
    }

    /**
     * @return array{total: int, paid: int, unpaid: int, cancelled: int}
     */
    public function stats(int $tenantUserId, ?Carbon $from = null, ?Carbon $to = null): array
    {
        $base = $this->periodScope(Subscription::query()->where('user_id', $tenantUserId), $from, $to);

        return [
            'total' => (clone $base)->count(),
            'paid' => (clone $base)->where('status', Subscription::STATUS_ACTIVE)->where('is_paid', true)->count(),
            'unpaid' => (clone $base)->where('status', Subscription::STATUS_ACTIVE)->where('is_paid', false)->count(),
            'cancelled' => (clone $base)->where('status', Subscription::STATUS_CANCELLED)->count(),
        ];
    }

    /**
     * @return array{unpaid_active: int, expiring_soon: int, calendar_this_week: int}
     */
    public function dashboardKpis(int $tenantUserId): array
    {
        $renewalDays = (int) config('nursery.subscriptions.renewal_reminder_days', 30);
        $until = now()->addDays($renewalDays)->toDateString();

        $unpaid = Subscription::query()
            ->where('user_id', $tenantUserId)
            ->where('status', Subscription::STATUS_ACTIVE)
            ->where('is_paid', false)
            ->count();

        $expiring = Subscription::query()
            ->where('user_id', $tenantUserId)
            ->where('status', Subscription::STATUS_ACTIVE)
            ->whereBetween('ends_on', [now()->toDateString(), $until])
            ->count();

        $weekStart = now()->startOfWeek(Carbon::SUNDAY)->toDateString();
        $weekEnd = now()->endOfWeek(Carbon::SATURDAY)->toDateString();

        $calendarCount = \App\Models\Nursery\CalendarEntry::query()
            ->where('user_id', $tenantUserId)
            ->whereBetween('starts_at', [$weekStart.' 00:00:00', $weekEnd.' 23:59:59'])
            ->count();

        return [
            'unpaid_active' => $unpaid,
            'expiring_soon' => $expiring,
            'calendar_this_week' => $calendarCount,
        ];
    }

    /**
     * @return Collection<int, Subscription>
     */
    public function paymentReminders(int $tenantUserId): Collection
    {
        return Subscription::query()
            ->with(['child:id,name', 'plan:id,name'])
            ->where('user_id', $tenantUserId)
            ->where('status', Subscription::STATUS_ACTIVE)
            ->where('is_paid', false)
            ->where('ends_on', '>=', now()->toDateString())
            ->orderBy('ends_on')
            ->limit(20)
            ->get();
    }

    /**
     * @return Collection<int, Subscription>
     */
    public function renewalReminders(int $tenantUserId, int $withinDays = 30): Collection
    {
        $until = now()->addDays($withinDays)->toDateString();

        return Subscription::query()
            ->with(['child:id,name', 'plan:id,name'])
            ->where('user_id', $tenantUserId)
            ->where('status', Subscription::STATUS_ACTIVE)
            ->whereBetween('ends_on', [now()->toDateString(), $until])
            ->orderBy('ends_on')
            ->limit(20)
            ->get();
    }

    /**
     * @param  Builder<Subscription>  $query
     * @return Builder<Subscription>
     */
    private function periodScope(Builder $query, ?Carbon $from, ?Carbon $to): Builder
    {
        if ($from !== null) {
            $query->where('created_at', '>=', $from->copy()->startOfDay());
        }
        if ($to !== null) {
            $query->where('created_at', '<=', $to->copy()->endOfDay());
        }

        return $query;
    }

    public function resolvePeriod(?string $period): array
    {
        $period = strtolower(trim((string) $period));
        $to = now();

        return match ($period) {
            'day' => [$to->copy()->startOfDay(), $to->copy()->endOfDay()],
            'week' => [$to->copy()->startOfWeek(), $to->copy()->endOfWeek()],
            'month' => [$to->copy()->startOfMonth(), $to->copy()->endOfMonth()],
            'year' => [$to->copy()->startOfYear(), $to->copy()->endOfYear()],
            default => [null, null],
        };
    }

    private function tryRecordFinance(Subscription $subscription, int $tenantUserId): bool
    {
        if (! $this->accounting->canRecord($tenantUserId)) {
            return false;
        }

        try {
            $this->accounting->recordPaidSubscription($subscription, $tenantUserId);

            return true;
        } catch (RuntimeException|InvalidArgumentException $e) {
            Log::warning('Nursery subscription finance posting failed', [
                'subscription_id' => $subscription->id,
                'message' => $e->getMessage(),
            ]);

            return false;
        }
    }
}
