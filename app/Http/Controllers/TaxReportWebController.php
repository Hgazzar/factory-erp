<?php

namespace App\Http\Controllers;

use App\Models\PurchaseInvoice;
use App\Models\SalesInvoice;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TaxReportWebController extends Controller
{
    /**
     * التقرير الضريبي – إجمالي الضريبة من المبيعات والمشتريات لفترة.
     */
    public function index(Request $request): View
    {
        $fromDate = $request->input('from_date');
        $toDate = $request->input('to_date');

        $salesVat = 0;
        $purchasesVat = 0;
        $salesTotal = 0;
        $purchasesTotal = 0;
        $salesCount = 0;
        $purchasesCount = 0;

        if ($fromDate || $toDate) {
            $salesQuery = SalesInvoice::query()
                ->when($fromDate, fn ($q) => $q->whereDate('date', '>=', $fromDate))
                ->when($toDate, fn ($q) => $q->whereDate('date', '<=', $toDate));
            $salesVat = (float) (clone $salesQuery)->sum('vat_amount');
            $salesTotal = (float) (clone $salesQuery)->sum('total');
            $salesCount = (clone $salesQuery)->count();

            $purchasesQuery = PurchaseInvoice::query()
                ->when($fromDate, fn ($q) => $q->whereDate('date', '>=', $fromDate))
                ->when($toDate, fn ($q) => $q->whereDate('date', '<=', $toDate));
            $purchasesVat = (float) (clone $purchasesQuery)->sum('vat_amount');
            $purchasesTotal = (float) (clone $purchasesQuery)->sum('total');
            $purchasesCount = (clone $purchasesQuery)->count();
        }

        $hasData = ($salesCount + $purchasesCount) > 0;

        return view('reports.tax.index', compact(
            'fromDate',
            'toDate',
            'salesVat',
            'purchasesVat',
            'salesTotal',
            'purchasesTotal',
            'salesCount',
            'purchasesCount',
            'hasData'
        ));
    }
}
