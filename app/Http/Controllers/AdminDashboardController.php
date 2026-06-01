<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ResolvesOperationsTenant;
use App\Services\Reports\AdminDashboardMetricsService;
use Illuminate\View\View;

class AdminDashboardController extends Controller
{
    use ResolvesOperationsTenant;

    public function __construct(
        private readonly AdminDashboardMetricsService $metrics,
    ) {}

    public function index(): View
    {
        $tenantUserId = $this->resolveOperationsTenantUserId();
        $kpis = $this->metrics->kpis($tenantUserId);
        $charts = $this->metrics->chartSeries($tenantUserId);

        return view('admin.dashboard.index', compact('kpis', 'charts'));
    }
}
