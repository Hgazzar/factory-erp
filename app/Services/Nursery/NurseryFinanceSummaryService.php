<?php

declare(strict_types=1);

namespace App\Services\Nursery;

use App\Models\Nursery\Guardian;
use App\Models\Nursery\Subscription;
use App\Models\Payment;
use App\Services\Reports\ProfitLossReportService;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * ملخص مالية الحضانة — قراءة من الاشتراكات + مصروفات/أرباح Finance الحالية (بدون دفتر موازٍ).
 */
final class NurseryFinanceSummaryService
{
    public function __construct(
        private readonly NurserySubscriptionService $subscriptions,
        private readonly ProfitLossReportService $profitLoss,
    ) {}

    /**
     * @return array{
     *     period: string,
     *     from: ?Carbon,
     *     to: ?Carbon,
     *     collected_amount: float,
     *     collected_count: int,
     *     outstanding_amount: float,
     *     outstanding_count: int,
     *     overdue_amount: float,
     *     overdue_count: int,
     *     expired_unpaid_amount: float,
     *     expired_unpaid_count: int,
     *     expense_amount: float,
     *     expense_count: int,
     *     net_period: float,
     *     ledger_net_profit: ?float,
     *     by_guardian: list<array{guardian_id: int, guardian_name: string, outstanding_amount: float, unpaid_count: int}>,
     *     recent_unpaid: Collection<int, Subscription>
     * }
     */
    public function summarize(int $tenantUserId, ?string $period = 'month'): array
    {
        $periodKey = strtolower(trim((string) $period));
        if ($periodKey === '') {
            $periodKey = 'month';
        }

        [$from, $to] = $this->subscriptions->resolvePeriod($periodKey === 'all' ? null : $periodKey);

        $collected = $this->sumPaidCollections($tenantUserId, $from, $to);
        $outstanding = $this->outstandingOperational($tenantUserId);
        $overdue = $this->overdueUnpaid($tenantUserId);
        $expired = $this->expiredUnpaid($tenantUserId);
        $expenses = $this->sumExpenses($tenantUserId, $from, $to);

        $collectedAmount = $collected['amount'];
        $expenseAmount = $expenses['amount'];
        $netPeriod = round($collectedAmount - $expenseAmount, 2);

        $ledgerNet = null;
        if ($from !== null && $to !== null) {
            $pl = $this->profitLoss->generate(
                $tenantUserId,
                $from->toDateString(),
                $to->toDateString(),
            );
            $ledgerNet = (float) ($pl['net_profit'] ?? 0);
        }

        return [
            'period' => $periodKey,
            'from' => $from,
            'to' => $to,
            'collected_amount' => $collectedAmount,
            'collected_count' => $collected['count'],
            'outstanding_amount' => $outstanding['amount'],
            'outstanding_count' => $outstanding['count'],
            'overdue_amount' => $overdue['amount'],
            'overdue_count' => $overdue['count'],
            'expired_unpaid_amount' => $expired['amount'],
            'expired_unpaid_count' => $expired['count'],
            'expense_amount' => $expenseAmount,
            'expense_count' => $expenses['count'],
            'net_period' => $netPeriod,
            'ledger_net_profit' => $ledgerNet,
            'by_guardian' => $this->outstandingByGuardian($tenantUserId),
            'recent_unpaid' => $this->recentUnpaid($tenantUserId),
        ];
    }

    /**
     * @return array{amount: float, count: int}
     */
    private function sumPaidCollections(int $tenantUserId, ?Carbon $from, ?Carbon $to): array
    {
        $query = Subscription::withoutGlobalScopes()
            ->where('user_id', $tenantUserId)
            ->where('is_paid', true)
            ->where('status', '!=', Subscription::STATUS_CANCELLED);

        if ($from !== null) {
            $query->where(function ($q) use ($from): void {
                $q->where(function ($inner) use ($from): void {
                    $inner->whereNotNull('paid_at')
                        ->where('paid_at', '>=', $from->copy()->startOfDay());
                })->orWhere(function ($inner) use ($from): void {
                    $inner->whereNull('paid_at')
                        ->where('created_at', '>=', $from->copy()->startOfDay());
                });
            });
        }

        if ($to !== null) {
            $query->where(function ($q) use ($to): void {
                $q->where(function ($inner) use ($to): void {
                    $inner->whereNotNull('paid_at')
                        ->where('paid_at', '<=', $to->copy()->endOfDay());
                })->orWhere(function ($inner) use ($to): void {
                    $inner->whereNull('paid_at')
                        ->where('created_at', '<=', $to->copy()->endOfDay());
                });
            });
        }

        $rows = $query->get(['amount_after_tax', 'discount_amount']);
        $amount = round((float) $rows->sum(fn (Subscription $s): float => $s->finalAmount()), 2);

        return ['amount' => $amount, 'count' => $rows->count()];
    }

    /**
     * @return array{amount: float, count: int}
     */
    private function outstandingOperational(int $tenantUserId): array
    {
        $rows = Subscription::withoutGlobalScopes()
            ->where('user_id', $tenantUserId)
            ->whereIn('status', Subscription::operationalStatuses())
            ->where('is_paid', false)
            ->get(['amount_after_tax', 'discount_amount']);

        return [
            'amount' => round((float) $rows->sum(fn (Subscription $s): float => $s->finalAmount()), 2),
            'count' => $rows->count(),
        ];
    }

    /**
     * Unpaid operational past ends_on (still not expired status — or already overdue dates).
     *
     * @return array{amount: float, count: int}
     */
    private function overdueUnpaid(int $tenantUserId): array
    {
        $today = now()->toDateString();

        $rows = Subscription::withoutGlobalScopes()
            ->where('user_id', $tenantUserId)
            ->where('is_paid', false)
            ->where(function ($q) use ($today): void {
                $q->where('status', Subscription::STATUS_EXPIRED)
                    ->orWhere(function ($inner) use ($today): void {
                        $inner->whereIn('status', Subscription::operationalStatuses())
                            ->whereDate('ends_on', '<', $today);
                    });
            })
            ->get(['amount_after_tax', 'discount_amount']);

        return [
            'amount' => round((float) $rows->sum(fn (Subscription $s): float => $s->finalAmount()), 2),
            'count' => $rows->count(),
        ];
    }

    /**
     * @return array{amount: float, count: int}
     */
    private function expiredUnpaid(int $tenantUserId): array
    {
        $rows = Subscription::withoutGlobalScopes()
            ->where('user_id', $tenantUserId)
            ->where('status', Subscription::STATUS_EXPIRED)
            ->where('is_paid', false)
            ->get(['amount_after_tax', 'discount_amount']);

        return [
            'amount' => round((float) $rows->sum(fn (Subscription $s): float => $s->finalAmount()), 2),
            'count' => $rows->count(),
        ];
    }

    /**
     * @return array{amount: float, count: int}
     */
    private function sumExpenses(int $tenantUserId, ?Carbon $from, ?Carbon $to): array
    {
        $query = Payment::withoutGlobalScopes()
            ->where('user_id', $tenantUserId)
            ->where('type', 'expense');

        if ($from !== null) {
            $query->whereDate('date', '>=', $from->toDateString());
        }
        if ($to !== null) {
            $query->whereDate('date', '<=', $to->toDateString());
        }

        $rows = $query->get(['amount']);
        $amount = round((float) $rows->sum(
            fn (Payment $p): float => (float) $p->amount + (float) ($p->tax_amount ?? 0)
        ), 2);

        return ['amount' => $amount, 'count' => $rows->count()];
    }

    /**
     * @return list<array{guardian_id: int, guardian_name: string, outstanding_amount: float, unpaid_count: int}>
     */
    private function outstandingByGuardian(int $tenantUserId): array
    {
        $subs = Subscription::withoutGlobalScopes()
            ->with(['child' => fn ($q) => $q->withoutGlobalScopes()->select('id', 'name', 'guardian_id')])
            ->where('user_id', $tenantUserId)
            ->whereIn('status', Subscription::operationalStatuses())
            ->where('is_paid', false)
            ->get();

        $guardianIds = $subs->map(fn (Subscription $s): ?int => $s->child?->guardian_id)
            ->filter()
            ->unique()
            ->values()
            ->all();

        $names = Guardian::withoutGlobalScopes()
            ->where('user_id', $tenantUserId)
            ->whereIn('id', $guardianIds)
            ->pluck('name', 'id');

        $grouped = [];
        foreach ($subs as $sub) {
            $gid = (int) ($sub->child?->guardian_id ?? 0);
            if ($gid <= 0) {
                continue;
            }
            if (! isset($grouped[$gid])) {
                $grouped[$gid] = [
                    'guardian_id' => $gid,
                    'guardian_name' => (string) ($names[$gid] ?? '—'),
                    'outstanding_amount' => 0.0,
                    'unpaid_count' => 0,
                ];
            }
            $grouped[$gid]['outstanding_amount'] += $sub->finalAmount();
            $grouped[$gid]['unpaid_count']++;
        }

        foreach ($grouped as &$row) {
            $row['outstanding_amount'] = round((float) $row['outstanding_amount'], 2);
        }
        unset($row);

        usort($grouped, static fn (array $a, array $b): int => $b['outstanding_amount'] <=> $a['outstanding_amount']);

        return array_values($grouped);
    }

    /**
     * @return Collection<int, Subscription>
     */
    private function recentUnpaid(int $tenantUserId): Collection
    {
        return Subscription::withoutGlobalScopes()
            ->with([
                'child' => fn ($q) => $q->withoutGlobalScopes()->select('id', 'name'),
                'plan' => fn ($q) => $q->withoutGlobalScopes()->select('id', 'name'),
            ])
            ->where('user_id', $tenantUserId)
            ->whereIn('status', Subscription::operationalStatuses())
            ->where('is_paid', false)
            ->orderBy('ends_on')
            ->limit(15)
            ->get();
    }
}
