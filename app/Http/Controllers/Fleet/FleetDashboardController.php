<?php

declare(strict_types=1);

namespace App\Http\Controllers\Fleet;

use App\Http\Controllers\Concerns\ResolvesOperationsTenant;
use App\Http\Controllers\Controller;
use App\Services\Fleet\FleetDashboardService;
use Illuminate\View\View;

final class FleetDashboardController extends Controller
{
    use ResolvesOperationsTenant;

    public function index(FleetDashboardService $dashboard): View
    {
        $tenantUserId = $this->resolveOperationsTenantUserId();
        $stats = $dashboard->overviewStats($tenantUserId);

        return view('fleet.dashboard', compact('stats'));
    }
}
