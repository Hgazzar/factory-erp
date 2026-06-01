<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ResolvesOperationsTenant;
use App\Models\CompanySetting;
use App\Services\Inventory\InventoryValuationReportService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class InventoryValuationReportWebController extends Controller
{
    use ResolvesOperationsTenant;

    public function __construct(
        private readonly InventoryValuationReportService $report,
    ) {}

    public function index(Request $request): View|StreamedResponse|Response
    {
        $tenantUserId = $this->resolveOperationsTenantUserId();
        $search = $request->input('q');
        $rows = $this->report->rows($tenantUserId, is_string($search) ? $search : null);
        $grandTotal = $this->report->grandTotal($rows);

        $export = $request->query('export');
        if ($export === 'excel' || $export === 'csv') {
            return $this->exportExcel($rows, $grandTotal);
        }
        if ($export === 'pdf') {
            return $this->exportPdf($tenantUserId, $rows, $grandTotal);
        }

        return view('inventory.reports.valuation', [
            'rows' => $rows,
            'grandTotal' => $grandTotal,
            'search' => $search,
        ]);
    }

    /**
     * @param  Collection<int, object>  $rows
     */
    private function exportExcel(Collection $rows, float $grandTotal): StreamedResponse
    {
        $fileName = 'inventory-valuation-'.now()->format('Ymd-His').'.xls';

        return response()->streamDownload(function () use ($rows, $grandTotal): void {
            echo "\xEF\xBB\xBF";
            echo "تقرير تقييم المخزون\n";
            echo "تاريخ التصدير: ".now()->format('Y-m-d H:i')."\n\n";
            echo "الرمز\tاسم الصنف\tالكمية\tتكلفة الوحدة\tإجمالي القيمة\n";

            foreach ($rows as $row) {
                echo $row->code."\t"
                    .$row->name."\t"
                    .number_format((float) $row->quantity, 4, '.', '')."\t"
                    .number_format((float) $row->unit_cost, 4, '.', '')."\t"
                    .number_format((float) $row->total_value, 4, '.', '')."\n";
            }

            echo "\t\t\tالإجمالي\t".number_format($grandTotal, 4, '.', '')."\n";
        }, $fileName, [
            'Content-Type' => 'application/vnd.ms-excel; charset=UTF-8',
        ]);
    }

    /**
     * @param  Collection<int, object>  $rows
     */
    private function exportPdf(int $tenantUserId, Collection $rows, float $grandTotal): Response
    {
        $company = CompanySetting::forTenant($tenantUserId);

        $pdf = Pdf::loadView('inventory.reports.valuation-pdf', [
            'rows' => $rows,
            'grandTotal' => $grandTotal,
            'companyName' => $company?->name ?? config('app.name'),
            'generatedAt' => now()->format('Y-m-d H:i'),
        ])->setPaper('a4', 'landscape');

        return $pdf->download('inventory-valuation-'.now()->format('Ymd-His').'.pdf');
    }
}
