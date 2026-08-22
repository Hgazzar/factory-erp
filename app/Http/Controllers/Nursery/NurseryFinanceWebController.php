<?php

declare(strict_types=1);

namespace App\Http\Controllers\Nursery;

use App\Http\Controllers\Concerns\ResolvesOperationsTenant;
use App\Http\Controllers\Controller;
use App\Services\Nursery\NurseryFinanceSummaryService;
use App\Services\Tenant\TenantModuleRegistry;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class NurseryFinanceWebController extends Controller
{
    use ResolvesOperationsTenant;

    public function index(
        Request $request,
        NurseryFinanceSummaryService $finance,
        TenantModuleRegistry $modules,
    ): View {
        $tenantUserId = $this->resolveOperationsTenantUserId();
        $period = (string) $request->query('period', 'month');
        if (! in_array($period, ['day', 'week', 'month', 'year', 'all'], true)) {
            $period = 'month';
        }

        $summary = $finance->summarize($tenantUserId, $period);
        $financeModuleOn = $modules->isEnabled('finance', $tenantUserId);

        return view('nursery.finance.index', [
            'summary' => $summary,
            'period' => $period,
            'financeModuleOn' => $financeModuleOn,
            'canOpenExpenses' => $financeModuleOn && \Illuminate\Support\Facades\Route::has('finance.expenses.index'),
            'canOpenProfitLoss' => $financeModuleOn && \Illuminate\Support\Facades\Route::has('finance.reports.profit-loss'),
        ]);
    }
}
