<?php

namespace App\Http\Controllers;

use App\Models\SalesInvoice;
use App\Models\SalesPayment;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SalesDashboardController extends Controller
{
    public function index(Request $request): View
    {
        $now = Carbon::now();
        $thisMonthStart = $now->copy()->startOfMonth();
        $thisMonthEnd = $now->copy()->endOfMonth();
        $lastMonthStart = $now->copy()->subMonth()->startOfMonth();
        $lastMonthEnd = $now->copy()->subMonth()->endOfMonth();
        $today = $now->toDateString();

        $postedQuery = SalesInvoice::query()->whereNotNull('posted_at');

        $overdueRows = (clone $postedQuery)
            ->whereRaw('COALESCE(paid_amount, 0) < total')
            ->whereNotNull('due_date')
            ->whereDate('due_date', '<', $today);

        $overdueAmount = (float) (clone $overdueRows)
            ->selectRaw('SUM(total - COALESCE(paid_amount, 0)) as balance')
            ->value('balance');
        $overdueCount = (int) (clone $overdueRows)->count();

        $dueRows = (clone $postedQuery)
            ->whereRaw('COALESCE(paid_amount, 0) < total')
            ->where(function ($q) use ($today) {
                $q->whereNull('due_date')
                    ->orWhereDate('due_date', '>=', $today);
            });

        $dueAmount = (float) (clone $dueRows)
            ->selectRaw('SUM(total - COALESCE(paid_amount, 0)) as balance')
            ->value('balance');
        $dueCount = (int) (clone $dueRows)->count();

        $thisMonthSales = (float) (clone $postedQuery)
            ->whereBetween('date', [$thisMonthStart, $thisMonthEnd])
            ->sum('total');

        $lastMonthSales = (float) (clone $postedQuery)
            ->whereBetween('date', [$lastMonthStart, $lastMonthEnd])
            ->sum('total');

        $totalSales = (float) (clone $postedQuery)->sum('total');

        $salesChangePercent = $lastMonthSales > 0
            ? round((($thisMonthSales - $lastMonthSales) / $lastMonthSales) * 100, 1)
            : ($thisMonthSales > 0 ? 100.0 : 0.0);

        $totalInvoices = (int) (clone $postedQuery)->count();

        $avgInvoiceValue = $totalInvoices > 0
            ? round($totalSales / $totalInvoices, 2)
            : 0.0;

        $paymentsThisMonth = (float) SalesPayment::query()
            ->whereBetween('date', [$thisMonthStart->toDateString(), $thisMonthEnd->toDateString()])
            ->sum('amount');

        return view('sales.dashboard', [
            'overdueAmount' => round($overdueAmount, 2),
            'overdueCount' => $overdueCount,
            'dueAmount' => round($dueAmount, 2),
            'dueCount' => $dueCount,
            'thisMonthSales' => round($thisMonthSales, 2),
            'lastMonthSales' => round($lastMonthSales, 2),
            'totalSales' => round($totalSales, 2),
            'salesChangePercent' => $salesChangePercent,
            'totalInvoices' => $totalInvoices,
            'quoteConversion' => 0,
            'avgPaymentDays' => 0,
            'avgInvoiceValue' => $avgInvoiceValue,
            'paymentsThisMonth' => round($paymentsThisMonth, 2),
        ]);
    }
}
