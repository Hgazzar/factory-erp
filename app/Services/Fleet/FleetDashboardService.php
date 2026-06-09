<?php

declare(strict_types=1);

namespace App\Services\Fleet;

use App\Models\Fleet\FleetAgent;
use App\Models\Fleet\FleetCollection;
use App\Models\Fleet\FleetCustomer;
use App\Models\Fleet\FleetProduct;
use App\Models\Fleet\FleetRoute;

final class FleetDashboardService
{
    public function __construct(
        private readonly FleetCustodyBalanceService $custodyBalances,
    ) {}

    /**
     * @return array{
     *   agents_active: int,
     *   agents_total: int,
     *   customers_active: int,
     *   products_active: int,
     *   routes_today: int,
     *   custody_agents: int,
     *   custody_issues_issued: int,
     *   collections_today: int,
     *   cod_collected_today: float
     * }
     */
    public function overviewStats(int $tenantUserId): array
    {
        $today = now()->toDateString();

        $codToday = (float) FleetCollection::query()
            ->where('user_id', $tenantUserId)
            ->where('status', FleetCollection::STATUS_CONFIRMED)
            ->where('payment_method', FleetCollection::PAYMENT_COD)
            ->whereDate('collected_on', $today)
            ->sum('subtotal');

        return [
            'agents_active' => (int) FleetAgent::query()
                ->where('user_id', $tenantUserId)
                ->where('status', FleetAgent::STATUS_ACTIVE)
                ->count(),
            'agents_total' => (int) FleetAgent::query()->where('user_id', $tenantUserId)->count(),
            'customers_active' => (int) FleetCustomer::query()
                ->where('user_id', $tenantUserId)
                ->where('status', FleetCustomer::STATUS_ACTIVE)
                ->count(),
            'products_active' => (int) FleetProduct::query()
                ->where('user_id', $tenantUserId)
                ->where('is_active', true)
                ->count(),
            'routes_today' => (int) FleetRoute::query()
                ->where('user_id', $tenantUserId)
                ->whereDate('route_date', $today)
                ->whereIn('status', [FleetRoute::STATUS_PLANNED, FleetRoute::STATUS_IN_PROGRESS])
                ->count(),
            'custody_agents' => $this->custodyBalances->agentsWithCustodyCount($tenantUserId),
            'custody_issues_issued' => $this->custodyBalances->issuedIssuesCount($tenantUserId),
            'collections_today' => (int) FleetCollection::query()
                ->where('user_id', $tenantUserId)
                ->where('status', FleetCollection::STATUS_CONFIRMED)
                ->whereDate('collected_on', $today)
                ->count(),
            'cod_collected_today' => round($codToday, 4),
        ];
    }
}
