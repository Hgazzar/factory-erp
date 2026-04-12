<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\SalesInvoice;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SalesDashboardController extends Controller
{
    public function index(Request $request): View
    {
        $now = Carbon::now();
        $thisMonthStart = $now->copy()->startOfMonth();
        $lastMonthStart = $now->copy()->subMonth()->startOfMonth();
        $lastMonthEnd = $now->copy()->subMonth()->endOfMonth();

        // يمكن ربطها لاحقاً بنماذج الفواتير والعملاء
        $overdueAmount = 0;
        $overdueCount = 0;
        $dueAmount = 0;
        $dueCount = 0;
        $thisMonthSales = 0;
        $lastMonthSales = 0;
        $totalSales = 0;
        $salesChangePercent = 0;
        $totalInvoices = SalesInvoice::count();
        $quoteConversion = 0;
        $avgPaymentDays = 0;
        $avgInvoiceValue = $totalInvoices > 0 ? SalesInvoice::sum('total') / $totalInvoices : 0;

        return view('sales.dashboard', [
            'overdueAmount' => $overdueAmount,
            'overdueCount' => $overdueCount,
            'dueAmount' => $dueAmount,
            'dueCount' => $dueCount,
            'thisMonthSales' => $thisMonthSales,
            'lastMonthSales' => $lastMonthSales,
            'totalSales' => $totalSales,
            'salesChangePercent' => $salesChangePercent,
            'totalInvoices' => $totalInvoices,
            'quoteConversion' => $quoteConversion,
            'avgPaymentDays' => $avgPaymentDays,
            'avgInvoiceValue' => $avgInvoiceValue,
        ]);
    }
}
