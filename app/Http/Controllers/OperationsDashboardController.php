<?php

namespace App\Http\Controllers;

use App\Models\ProductionShift;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OperationsDashboardController extends Controller
{
    public function index(Request $request): View
    {
        // 1. تحديد التواريخ (الافتراضي هو اليوم)
        $fromDate = $request->input('from_date') ?: now()->toDateString();
        $toDate = $request->input('to_date') ?: $fromDate;

        // 2. جلب البيانات مع حسابات الـ KPIs (إنجاز وجودة)
        $productionShifts = ProductionShift::with(['shift', 'productionLine', 'machine'])
            ->whereDate('date', '>=', $fromDate)
            ->whereDate('date', '<=', $toDate)
            ->orderBy('date', 'desc')
            ->get()
            ->map(function ($pShift) {
                $actual = $pShift->actual_quantity ?: 0;
                $planned = $pShift->planned_quantity ?: 1; // حماية من القسمة على صفر
                $scrap = $pShift->scrap_quantity ?: 0;

                // نسبة الإنجاز (Achievement)
                $pShift->achievement_rate = round(($actual / $planned) * 100, 1);

                // نسبة الجودة (Yield) = ((الفعلي - الهالك) / الفعلي) * 100
                $pShift->yield_rate = ($actual > 0) 
                    ? round((($actual - $scrap) / $actual) * 100, 1) 
                    : 100;

                return $pShift;
            });

        // 3. ملخص الإجماليات للكروت العلوية (Summary)
        $summary = [
            'total_planned' => $productionShifts->sum('planned_quantity'),
            'total_actual'  => $productionShifts->sum('actual_quantity'),
            'total_scrap'   => $productionShifts->sum('scrap_quantity'),
            'avg_yield'     => round($productionShifts->avg('yield_rate') ?: 0, 1),
        ];

        return view('operations.dashboard.index', compact(
            'fromDate',
            'toDate',
            'productionShifts',
            'summary'
        ));
    }
}