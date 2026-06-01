<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ResolvesOperationsTenant;
use App\Services\Reports\ProfitLossReportService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProfitLossReportWebController extends Controller
{
    use ResolvesOperationsTenant;

    public function __construct(
        private readonly ProfitLossReportService $reportService,
    ) {}

    /**
     * قائمة أرباح وخسائر (استحقاق): مبيعات − COGS − رواتب − مصاريف تشغيل = صافي الربح.
     */
    public function index(Request $request): View
    {
        $fromDate = $request->input('from_date');
        $toDate = $request->input('to_date');

        if ($fromDate === null && $toDate === null) {
            $fromDate = Carbon::today()->startOfMonth()->toDateString();
            $toDate = Carbon::today()->toDateString();
        }

        $tenantUserId = $this->resolveOperationsTenantUserId();
        $report = $this->reportService->generate(
            $tenantUserId,
            (string) ($fromDate ?? Carbon::today()->startOfMonth()->toDateString()),
            (string) ($toDate ?? Carbon::today()->toDateString()),
        );

        return view('finance.reports.profit-loss', [
            'fromDate' => $report['from_date'],
            'toDate' => $report['to_date'],
            'report' => $report,
        ]);
    }
}
