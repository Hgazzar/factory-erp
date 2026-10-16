<?php

declare(strict_types=1);

namespace App\Services\Nursery;

use App\Models\AuditTrail;
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
        private readonly NurseryWhatsAppNotifier $notifier,
        private readonly NurserySubscriptionPlanService $plans,
    ) {}

    /**
     * Canonical nursery plans: ساعة / يومي / أسبوعي / فصلي (no yearly, no monthly default).
     * Syncs existing tenants — creates missing types, renames defaults, deactivates yearly.
     * Safe against unique(user_id, name) conflicts on live DBs with legacy plan rows.
     */
    public function ensureDefaultPlans(int $tenantUserId): void
    {
        $defaults = [
            ['name' => 'ساعة', 'plan_type' => NurserySubscriptionPlanService::TYPE_HOURLY, 'amount' => 50],
            ['name' => 'يومي', 'plan_type' => NurserySubscriptionPlanService::TYPE_DAILY, 'amount' => 150],
            ['name' => 'أسبوعي', 'plan_type' => NurserySubscriptionPlanService::TYPE_WEEKLY, 'amount' => 700],
            ['name' => 'فصلي', 'plan_type' => NurserySubscriptionPlanService::TYPE_TERM, 'amount' => 5500],
        ];

        $base = fn () => SubscriptionPlan::query()->withoutGlobalScopes()->where('user_id', $tenantUserId);

        foreach ($defaults as $plan) {
            $byType = $base()
                ->where('plan_type', $plan['plan_type'])
                ->orderByDesc('is_active')
                ->orderBy('id')
                ->first();

            $byName = $base()
                ->where('name', $plan['name'])
                ->orderByDesc('is_active')
                ->orderBy('id')
                ->first();

            if ($byType !== null) {
                // Free the canonical name if another row holds it (avoids unique user_id+name 500).
                if ($byName !== null && (int) $byName->id !== (int) $byType->id) {
                    $byName->update([
                        'name' => $plan['name'].' (قديم-'.$byName->id.')',
                        'is_active' => false,
                    ]);
                }

                $byType->update([
                    'name' => $plan['name'],
                    'is_active' => true,
                ]);

                continue;
            }

            if ($byName !== null) {
                $byName->update([
                    'plan_type' => $plan['plan_type'],
                    'is_active' => true,
                ]);

                continue;
            }

            SubscriptionPlan::query()->withoutGlobalScopes()->create([
                'user_id' => $tenantUserId,
                'name' => $plan['name'],
                'plan_type' => $plan['plan_type'],
                'amount' => $plan['amount'],
                'tax_rate' => 15,
                'currency_code' => 'SAR',
                'is_active' => true,
            ]);
        }

        // Hide legacy yearly (and leftover monthly defaults) from new-subscription pickers.
        $base()
            ->whereIn('plan_type', [
                NurserySubscriptionPlanService::TYPE_YEARLY,
                NurserySubscriptionPlanService::TYPE_MONTHLY,
            ])
            ->where('is_active', true)
            ->update(['is_active' => false]);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array{
     *     subscription: Subscription,
     *     finance_posted: bool,
     *     finance_status: string,
     *     whatsapp_dispatched: bool,
     *     whatsapp_sent: bool
     * }
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

        if ($startsOn === '') {
            throw new InvalidArgumentException('تاريخ البداية مطلوب.');
        }

        if ($endsOn === '') {
            $autoEnd = $this->plans->endsOnForPlanType($startsOn, (string) $plan->plan_type);
            if ($autoEnd === null) {
                throw new InvalidArgumentException('تاريخ الانتهاء مطلوب لهذه الخطة.');
            }
            $endsOn = $autoEnd->toDateString();
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
        $status = $isPaid
            ? Subscription::STATUS_PAID
            : ($endsOn < now()->toDateString() ? Subscription::STATUS_EXPIRED : Subscription::STATUS_UNPAID);
        $paymentMethod = $isPaid ? $this->normalizePaymentMethod($data['payment_method'] ?? 'cash') : null;

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
            'status' => $status,
            'paid_at' => $isPaid ? now() : null,
            'payment_method' => $paymentMethod,
            'created_by' => $createdBy,
        ]);

        $financePosted = false;
        $financeStatus = 'not_enabled';
        $whatsappDispatched = false;

        if ($isPaid) {
            [$financePosted, $financeStatus] = $this->attemptFinance($subscription, $tenantUserId, $paymentMethod ?? 'cash');
            $this->notifier->dispatchSubscriptionPaidConfirmation($tenantUserId, (int) $subscription->id);
            $whatsappDispatched = true;
        }

        return [
            'subscription' => $subscription->fresh(),
            'finance_posted' => $financePosted,
            'finance_status' => $financeStatus,
            'whatsapp_dispatched' => $whatsappDispatched,
            'whatsapp_sent' => false,
        ];
    }

    /**
     * @return array{
     *     subscription: Subscription,
     *     finance_posted: bool,
     *     finance_status: string,
     *     whatsapp_dispatched: bool,
     *     already_paid: bool
     * }
     */
    public function markPaid(Subscription $subscription, int $tenantUserId, string $paymentMethod = 'cash'): array
    {
        abort_unless((int) $subscription->user_id === $tenantUserId, 404);

        if ($subscription->status === Subscription::STATUS_CANCELLED) {
            throw new InvalidArgumentException('لا يمكن تسجيل دفع لاشتراك ملغى.');
        }

        $method = $this->normalizePaymentMethod($paymentMethod);

        if ($subscription->isAlreadyPaid()) {
            if ($subscription->status !== Subscription::STATUS_PAID) {
                $subscription->forceFill([
                    'status' => Subscription::STATUS_PAID,
                    'is_paid' => true,
                ])->save();
            }

            return [
                'subscription' => $subscription->fresh(),
                'finance_posted' => false,
                'finance_status' => $subscription->journal_entry_id ? 'recorded' : 'skipped',
                'whatsapp_dispatched' => false,
                'already_paid' => true,
            ];
        }

        $subscription->forceFill([
            'is_paid' => true,
            'status' => Subscription::STATUS_PAID,
            'paid_at' => now(),
            'payment_method' => $method,
        ])->save();

        [$financePosted, $financeStatus] = $this->attemptFinance($subscription, $tenantUserId, $method);

        $this->notifier->dispatchSubscriptionPaidConfirmation($tenantUserId, (int) $subscription->id);

        return [
            'subscription' => $subscription->fresh(),
            'finance_posted' => $financePosted,
            'finance_status' => $financeStatus,
            'whatsapp_dispatched' => true,
            'already_paid' => false,
        ];
    }

    public function cancel(Subscription $subscription, int $tenantUserId): Subscription
    {
        abort_unless((int) $subscription->user_id === $tenantUserId, 404);

        $this->accounting->reversePaidSubscriptionIfNeeded($subscription, $tenantUserId);

        $previousStatus = (string) $subscription->status;
        if ($previousStatus !== Subscription::STATUS_CANCELLED) {
            $subscription->update(['status' => Subscription::STATUS_CANCELLED]);
            AuditTrail::log(
                'update',
                'nursery_subscriptions',
                $subscription->id,
                ['status' => $previousStatus],
                ['status' => Subscription::STATUS_CANCELLED],
            );
        }

        return $subscription->fresh();
    }

    /**
     * Creates a new unpaid subscription period. Does not mutate the source row.
     */
    public function renew(Subscription $subscription, int $tenantUserId, ?int $createdBy = null): Subscription
    {
        abort_unless((int) $subscription->user_id === $tenantUserId, 404);

        if ($subscription->isCancelled()) {
            throw new InvalidArgumentException('لا يمكن تجديد اشتراك ملغى.');
        }

        $existing = Subscription::query()
            ->where('user_id', $tenantUserId)
            ->where('renewed_from_id', $subscription->id)
            ->where('status', '!=', Subscription::STATUS_CANCELLED)
            ->first();

        if ($existing !== null) {
            return $existing;
        }

        $subscription->loadMissing('plan');
        $startsOn = ($subscription->ends_on?->copy() ?? now())->addDay()->startOfDay();
        if ($startsOn->lt(now()->startOfDay())) {
            $startsOn = now()->startOfDay();
        }

        $planType = (string) ($subscription->plan?->plan_type ?? NurserySubscriptionPlanService::TYPE_CUSTOM);
        $autoEnd = $this->plans->endsOnForPlanType($startsOn, $planType);
        if ($autoEnd !== null) {
            $endsOn = $autoEnd;
        } else {
            $durationDays = max(1, (int) $subscription->starts_on?->diffInDays($subscription->ends_on));
            $endsOn = $startsOn->copy()->addDays($durationDays);
        }

        $result = $this->create($tenantUserId, [
            'child_id' => (int) $subscription->child_id,
            'plan_id' => (int) $subscription->plan_id,
            'starts_on' => $startsOn->toDateString(),
            'ends_on' => $endsOn->toDateString(),
            'amount_after_tax' => (float) $subscription->amount_after_tax,
            'discount_amount' => (float) $subscription->discount_amount,
            'notes' => $subscription->notes,
            'is_paid' => false,
        ], $createdBy);

        $renewed = $result['subscription'];
        $renewed->forceFill(['renewed_from_id' => (int) $subscription->id])->save();

        AuditTrail::log(
            'create',
            'nursery_subscriptions',
            $renewed->id,
            null,
            [
                'renewed_from_id' => (int) $subscription->id,
                'status' => $renewed->status,
                'is_paid' => false,
            ],
        );

        return $renewed->fresh();
    }

    public function expireOverdueUnpaid(int $tenantUserId): int
    {
        return Subscription::query()
            ->where('user_id', $tenantUserId)
            ->whereNotIn('status', [Subscription::STATUS_CANCELLED, Subscription::STATUS_EXPIRED])
            ->where('is_paid', false)
            ->whereDate('ends_on', '<', now()->toDateString())
            ->update(['status' => Subscription::STATUS_EXPIRED]);
    }

    /**
     * @return array{queued: int, skipped: int, sent: int}
     */
    public function sendPaymentReminders(int $tenantUserId): array
    {
        $items = Subscription::query()
            ->where('user_id', $tenantUserId)
            ->whereIn('status', Subscription::operationalStatuses())
            ->where('is_paid', false)
            ->where('ends_on', '>=', now()->toDateString())
            ->whereNull('payment_reminder_sent_at')
            ->get();

        $queued = 0;
        $skipped = 0;

        foreach ($items as $subscription) {
            $this->notifier->dispatchPaymentReminder($tenantUserId, (int) $subscription->id);
            $queued++;

            if ($subscription->fresh()->payment_reminder_sent_at === null) {
                $skipped++;
            }
        }

        return ['queued' => $queued, 'skipped' => $skipped, 'sent' => 0];
    }

    /**
     * @return array{queued: int, skipped: int, sent: int}
     */
    public function sendRenewalReminders(int $tenantUserId, ?int $withinDays = null): array
    {
        $withinDays ??= (int) config('nursery.subscriptions.renewal_reminder_days', 30);
        $until = now()->addDays($withinDays)->toDateString();

        $items = Subscription::query()
            ->where('user_id', $tenantUserId)
            ->whereIn('status', Subscription::operationalStatuses())
            ->whereBetween('ends_on', [now()->toDateString(), $until])
            ->whereNull('renewal_reminder_sent_at')
            ->get();

        $queued = 0;
        $skipped = 0;

        foreach ($items as $subscription) {
            $this->notifier->dispatchRenewalReminder($tenantUserId, (int) $subscription->id);
            $queued++;

            if ($subscription->fresh()->renewal_reminder_sent_at === null) {
                $skipped++;
            }
        }

        return ['queued' => $queued, 'skipped' => $skipped, 'sent' => 0];
    }

    /**
     * @return array{total: int, paid: int, unpaid: int, cancelled: int, expired: int}
     */
    public function stats(int $tenantUserId, ?Carbon $from = null, ?Carbon $to = null): array
    {
        $base = $this->periodScope(Subscription::query()->where('user_id', $tenantUserId), $from, $to);

        return [
            'total' => (clone $base)->count(),
            'paid' => (clone $base)->whereIn('status', Subscription::operationalStatuses())->where('is_paid', true)->count(),
            'unpaid' => (clone $base)->whereIn('status', Subscription::operationalStatuses())->where('is_paid', false)->count(),
            'cancelled' => (clone $base)->where('status', Subscription::STATUS_CANCELLED)->count(),
            'expired' => (clone $base)->where('status', Subscription::STATUS_EXPIRED)->count(),
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
            ->whereIn('status', Subscription::operationalStatuses())
            ->where('is_paid', false)
            ->count();

        $expiring = Subscription::query()
            ->where('user_id', $tenantUserId)
            ->whereIn('status', Subscription::operationalStatuses())
            ->whereBetween('ends_on', [now()->toDateString(), $until])
            ->count();

        $operationalTotal = Subscription::query()
            ->where('user_id', $tenantUserId)
            ->whereIn('status', Subscription::operationalStatuses())
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
            'operational_total' => $operationalTotal,
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
            ->whereIn('status', Subscription::operationalStatuses())
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
            ->whereIn('status', Subscription::operationalStatuses())
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
            $query->where('created_at', '>=', $from);
        }
        if ($to !== null) {
            $query->where('created_at', '<=', $to);
        }

        return $query;
    }

    /**
     * List filter windows in canonical duration order: ساعة → يومي → أسبوعي → فصلي.
     *
     * @return array{0: ?Carbon, 1: ?Carbon}
     */
    public function resolvePeriod(?string $period): array
    {
        $period = strtolower(trim((string) $period));
        $to = now();

        return match ($period) {
            'hour' => [$to->copy()->subHour(), $to->copy()],
            'day' => [$to->copy()->startOfDay(), $to->copy()->endOfDay()],
            'week' => [$to->copy()->startOfWeek(), $to->copy()->endOfWeek()],
            'term' => [$to->copy()->subMonthsNoOverflow(3)->startOfDay(), $to->copy()->endOfDay()],
            // Legacy bookmarks — map onto the new set (no yearly).
            'month' => [$to->copy()->subMonthsNoOverflow(3)->startOfDay(), $to->copy()->endOfDay()],
            'year' => [$to->copy()->subMonthsNoOverflow(3)->startOfDay(), $to->copy()->endOfDay()],
            default => [null, null],
        };
    }

    /**
     * @return array{0: bool, 1: string}
     */
    private function attemptFinance(Subscription $subscription, int $tenantUserId, string $paymentMethod): array
    {
        if ($subscription->journal_entry_id) {
            return [false, 'recorded'];
        }

        if (! $this->accounting->canRecord($tenantUserId)) {
            return [false, 'not_enabled'];
        }

        try {
            $this->accounting->recordPaidSubscription($subscription, $tenantUserId, $paymentMethod);

            return [true, 'recorded'];
        } catch (RuntimeException|InvalidArgumentException $e) {
            Log::warning('Nursery subscription finance posting failed', [
                'subscription_id' => $subscription->id,
                'message' => $e->getMessage(),
            ]);

            return [false, 'failed'];
        }
    }

    private function normalizePaymentMethod(mixed $method): string
    {
        $value = strtolower(trim((string) $method));
        if ($value === '') {
            $value = 'cash';
        }

        if (! in_array($value, Subscription::PAYMENT_METHODS, true)) {
            throw new InvalidArgumentException('طريقة الدفع غير مدعومة.');
        }

        return $value;
    }
}
