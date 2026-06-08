<?php

declare(strict_types=1);

namespace App\Services\Fleet;

use App\Models\Fleet\FleetAgent;
use App\Models\Fleet\FleetCustomer;
use App\Models\Fleet\FleetProduct;
use App\Models\Fleet\FleetRoute;

final class FleetDashboardService
{
    /**
     * @return array{
     *   agents_active: int,
     *   agents_total: int,
     *   customers_active: int,
     *   products_active: int,
     *   routes_today: int
     * }
     */
    public function overviewStats(int $tenantUserId): array
    {
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
                ->whereDate('route_date', now()->toDateString())
                ->whereIn('status', [FleetRoute::STATUS_PLANNED, FleetRoute::STATUS_IN_PROGRESS])
                ->count(),
        ];
    }
}
