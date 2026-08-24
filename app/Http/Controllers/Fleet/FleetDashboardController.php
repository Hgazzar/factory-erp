<?php

declare(strict_types=1);

namespace App\Http\Controllers\Fleet;

use App\Http\Controllers\Concerns\ResolvesOperationsTenant;
use App\Http\Controllers\Controller;
use App\Services\Fleet\FleetDashboardService;
use App\Services\Fleet\FleetGeoVerificationService;
use Illuminate\View\View;

final class FleetDashboardController extends Controller
{
    use ResolvesOperationsTenant;

    public function index(FleetDashboardService $dashboard, FleetGeoVerificationService $geo): View
    {
        $tenantUserId = $this->resolveOperationsTenantUserId();
        $stats = $dashboard->overviewStats($tenantUserId);
        $geoExceptions = $geo->recentExceptions($tenantUserId);
        $geoPendingLocations = $geo->pendingLocations($tenantUserId);

        return view('fleet.dashboard', compact('stats', 'geoExceptions', 'geoPendingLocations'));
    }
}
