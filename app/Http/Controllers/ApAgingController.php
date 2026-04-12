<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ApAgingController extends Controller
{
    public function index(Request $request): View|StreamedResponse
    {
        $validated = $request->validate([
            'as_of_date' => ['nullable', 'date'],
        ]);

        $asOfDate = $validated['as_of_date'] ?? now()->toDateString();

        $rows = DB::table('purchase_invoices as pi')
            ->join('suppliers as s', 's.id', '=', 'pi.supplier_id')
            ->whereDate('pi.date', '<=', $asOfDate)
            ->select(
                's.id as supplier_id',
                DB::raw("COALESCE(s.code, '') as supplier_code"),
                DB::raw("COALESCE(s.name, '—') as supplier_name"),
                DB::raw("COALESCE(SUM(CASE
                    WHEN pi.date >= ? THEN COALESCE(pi.total, 0)
                    ELSE 0
                END), 0) as current_amount"),
                DB::raw("COALESCE(SUM(CASE
                    WHEN pi.date < ?
                        AND (?::date - pi.date) BETWEEN 1 AND 30
                    THEN COALESCE(pi.total, 0)
                    ELSE 0
                END), 0) as bucket_1_30"),
                DB::raw("COALESCE(SUM(CASE
                    WHEN (?::date - pi.date) BETWEEN 31 AND 60
                    THEN COALESCE(pi.total, 0)
                    ELSE 0
                END), 0) as bucket_31_60"),
                DB::raw("COALESCE(SUM(CASE
                    WHEN (?::date - pi.date) BETWEEN 61 AND 90
                    THEN COALESCE(pi.total, 0)
                    ELSE 0
                END), 0) as bucket_61_90"),
                DB::raw("COALESCE(SUM(CASE
                    WHEN (?::date - pi.date) > 90
                    THEN COALESCE(pi.total, 0)
                    ELSE 0
                END), 0) as bucket_over_90"),
                DB::raw('COALESCE(SUM(COALESCE(pi.total, 0)), 0) as total_amount')
            )
            ->groupBy('s.id', 's.code', 's.name')
            ->setBindings([$asOfDate, $asOfDate, $asOfDate, $asOfDate, $asOfDate, $asOfDate], 'select')
            ->orderBy('s.code')
            ->orderBy('s.name')
            ->get();

        $totalPayables = (float) $rows->sum('total_amount');
        $currentAmount = (float) $rows->sum('current_amount');
        $overdueAmount = (float) (
            $rows->sum('bucket_1_30')
            + $rows->sum('bucket_31_60')
            + $rows->sum('bucket_61_90')
            + $rows->sum('bucket_over_90')
        );
        $suppliersCount = (int) $rows->count();

        if ($request->query('export') === 'excel') {
            return $this->exportExcel($rows->all(), $asOfDate);
        }

        return view('finance.reports.ap-aging', [
            'asOfDate' => Carbon::parse($asOfDate)->toDateString(),
            'rows' => $rows,
            'stats' => [
                'total_payables' => $totalPayables,
                'current_amount' => $currentAmount,
                'overdue_amount' => $overdueAmount,
                'suppliers_count' => $suppliersCount,
            ],
        ]);
    }

    private function exportExcel(array $rows, string $asOfDate): StreamedResponse
    {
        $fileName = 'ap-aging-' . now()->format('Ymd-His') . '.xls';

        return response()->streamDownload(function () use ($rows, $asOfDate): void {
            echo "\xEF\xBB\xBF";
            echo "أعمار الذمم الدائنة\n";
            echo "كما في: {$asOfDate}\n\n";
            echo "الرمز\tاسم المورد\tحالي\t1-30\t31-60\t61-90\tأكثر من 90\tالإجمالي\n";

            foreach ($rows as $row) {
                echo $row->supplier_code . "\t"
                    . $row->supplier_name . "\t"
                    . number_format((float) $row->current_amount, 2, '.', '') . "\t"
                    . number_format((float) $row->bucket_1_30, 2, '.', '') . "\t"
                    . number_format((float) $row->bucket_31_60, 2, '.', '') . "\t"
                    . number_format((float) $row->bucket_61_90, 2, '.', '') . "\t"
                    . number_format((float) $row->bucket_over_90, 2, '.', '') . "\t"
                    . number_format((float) $row->total_amount, 2, '.', '') . "\n";
            }
        }, $fileName, [
            'Content-Type' => 'application/vnd.ms-excel; charset=UTF-8',
        ]);
    }
}
