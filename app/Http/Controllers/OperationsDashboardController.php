<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ResolvesOperationsTenant;
use App\Models\ProductionShift;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OperationsDashboardController extends Controller
{
    use ResolvesOperationsTenant;

    public function index(Request $request): View
    {
        $this->resolveOperationsTenantUserId();

        $fromDate = $request->input('from_date') ?: now()->toDateString();
        $toDate = $request->input('to_date') ?: $fromDate;

        $productionShifts = ProductionShift::with(['shift', 'productionLine', 'machine'])
            ->whereDate('date', '>=', $fromDate)
            ->whereDate('date', '<=', $toDate)
            ->orderBy('date', 'desc')
            ->get()
            ->map(function ($pShift) {
                $actual = (float) ($pShift->actual_quantity ?: 0);
                $planned = (float) ($pShift->planned_quantity ?: 1);
                $rejected = (float) ($pShift->rejected_quantity ?: 0);

                $pShift->achievement_rate = round(($actual / max($planned, 0.0001)) * 100, 1);

                $pShift->yield_rate = ($actual > 0)
                    ? round((($actual - $rejected) / $actual) * 100, 1)
                    : 100;

                return $pShift;
            });

        $summary = [
            'total_planned' => $productionShifts->sum('planned_quantity'),
            'total_actual' => $productionShifts->sum(fn ($shift) => $shift->actual_quantity),
            'total_rejected' => $productionShifts->sum(fn ($shift) => $shift->rejected_quantity),
            'avg_yield' => round($productionShifts->avg('yield_rate') ?: 0, 1),
        ];

        return view('operations.dashboard.index', compact(
            'fromDate',
            'toDate',
            'productionShifts',
            'summary'
        ));
    }
}
