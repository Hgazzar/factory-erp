<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ArAgingController extends Controller
{
    public function index(Request $request): View|StreamedResponse
    {
        $validated = $request->validate([
            'as_of_date' => ['nullable', 'date'],
        ]);

        $asOfDate = $validated['as_of_date'] ?? now()->toDateString();

        $rows = DB::table('sales_invoices as si')
            ->join('customers as c', 'c.id', '=', 'si.customer_id')
            ->whereDate('si.date', '<=', $asOfDate)
            ->whereRaw('COALESCE(si.total, 0) - COALESCE(si.paid_amount, 0) > 0')
            ->select(
                'c.id as customer_id',
                DB::raw("COALESCE(c.code, '') as customer_code"),
                DB::raw("COALESCE(c.name_ar, c.name, '—') as customer_name"),
                DB::raw("COALESCE(SUM(CASE
                    WHEN COALESCE(si.due_date, si.date) >= ? THEN GREATEST(COALESCE(si.total, 0) - COALESCE(si.paid_amount, 0), 0)
                    ELSE 0
                END), 0) as current_amount"),
                DB::raw("COALESCE(SUM(CASE
                    WHEN COALESCE(si.due_date, si.date) < ?
                        AND (?::date - COALESCE(si.due_date, si.date)) BETWEEN 1 AND 30
                    THEN GREATEST(COALESCE(si.total, 0) - COALESCE(si.paid_amount, 0), 0)
                    ELSE 0
                END), 0) as bucket_1_30"),
                DB::raw("COALESCE(SUM(CASE
                    WHEN (?::date - COALESCE(si.due_date, si.date)) BETWEEN 31 AND 60
                    THEN GREATEST(COALESCE(si.total, 0) - COALESCE(si.paid_amount, 0), 0)
                    ELSE 0
                END), 0) as bucket_31_60"),
                DB::raw("COALESCE(SUM(CASE
                    WHEN (?::date - COALESCE(si.due_date, si.date)) BETWEEN 61 AND 90
                    THEN GREATEST(COALESCE(si.total, 0) - COALESCE(si.paid_amount, 0), 0)
                    ELSE 0
                END), 0) as bucket_61_90"),
                DB::raw("COALESCE(SUM(CASE
                    WHEN (?::date - COALESCE(si.due_date, si.date)) > 90
                    THEN GREATEST(COALESCE(si.total, 0) - COALESCE(si.paid_amount, 0), 0)
                    ELSE 0
                END), 0) as bucket_over_90"),
                DB::raw('COALESCE(SUM(GREATEST(COALESCE(si.total, 0) - COALESCE(si.paid_amount, 0), 0)), 0) as total_amount')
            )
            ->groupBy('c.id', 'c.code', 'c.name_ar', 'c.name')
            ->setBindings([$asOfDate, $asOfDate, $asOfDate, $asOfDate, $asOfDate, $asOfDate], 'select')
            ->orderBy('c.code')
            ->orderBy('c.name')
            ->get();

        $totalReceivables = (float) $rows->sum('total_amount');
        $currentAmount = (float) $rows->sum('current_amount');
        $overdueAmount = (float) (
            $rows->sum('bucket_1_30')
            + $rows->sum('bucket_31_60')
            + $rows->sum('bucket_61_90')
            + $rows->sum('bucket_over_90')
        );
        $customersCount = (int) $rows->count();

        if ($request->query('export') === 'excel') {
            return $this->exportExcel($rows->all(), $asOfDate);
        }

        return view('finance.reports.ar-aging', [
            'asOfDate' => Carbon::parse($asOfDate)->toDateString(),
            'rows' => $rows,
            'stats' => [
                'total_receivables' => $totalReceivables,
                'current_amount' => $currentAmount,
                'overdue_amount' => $overdueAmount,
                'customers_count' => $customersCount,
            ],
        ]);
    }

    private function exportExcel(array $rows, string $asOfDate): StreamedResponse
    {
        $fileName = 'ar-aging-' . now()->format('Ymd-His') . '.xls';

        return response()->streamDownload(function () use ($rows, $asOfDate): void {
            echo "\xEF\xBB\xBF";
            echo "أعمار الذمم المدينة\n";
            echo "كما في: {$asOfDate}\n\n";
            echo "الرمز\tاسم العميل\tحالي\t1-30\t31-60\t61-90\tأكثر من 90\tالإجمالي\n";

            foreach ($rows as $row) {
                echo $row->customer_code . "\t"
                    . $row->customer_name . "\t"
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
