<?php

namespace App\Http\Controllers;

use App\Models\PurchaseInvoice;
use App\Models\Supplier;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ProcurementDashboardController extends Controller
{
    public function index(Request $request): View
    {
        $now = Carbon::now();
        $thisMonthStart = $now->copy()->startOfMonth();
        $lastMonthStart = $now->copy()->subMonth()->startOfMonth();
        $lastMonthEnd = $now->copy()->subMonth()->endOfMonth();

        $totalPurchases = (float) PurchaseInvoice::sum('total');
        $thisMonthPurchases = (float) PurchaseInvoice::where('date', '>=', $thisMonthStart)->sum('total');
        $lastMonthPurchases = (float) PurchaseInvoice::whereBetween('date', [$lastMonthStart, $lastMonthEnd])->sum('total');
        $purchasesChangePercent = $lastMonthPurchases > 0
            ? round((($thisMonthPurchases - $lastMonthPurchases) / $lastMonthPurchases) * 100, 1)
            : 0;

        $totalInvoices = PurchaseInvoice::count();
        $unpaidInvoicesAmount = (float) PurchaseInvoice::sum('total');
        $unpaidInvoicesCount = PurchaseInvoice::count();
        $pendingReceiptsCount = 0;
        $pendingPurchaseOrdersCount = 0;
        $activeSuppliersCount = Supplier::where('is_active', true)->count();
        $totalSuppliersCount = Supplier::count();

        $monthlyData = [];
        $monthsAr = ['يناير', 'فبراير', 'مارس', 'أبريل', 'مايو', 'يونيو', 'يوليو', 'أغسطس', 'سبتمبر', 'أكتوبر', 'نوفمبر', 'ديسمبر'];
        for ($i = 11; $i >= 0; $i--) {
            $date = $now->copy()->subMonths($i);
            $monthStart = $date->copy()->startOfMonth();
            $monthEnd = $date->copy()->endOfMonth();
            $monthlyData[] = [
                'label' => $monthsAr[(int) $monthStart->format('n') - 1],
                'value' => (float) PurchaseInvoice::whereBetween('date', [$monthStart, $monthEnd])->sum('total'),
            ];
        }

        $topSuppliers = PurchaseInvoice::query()
            ->where('date', '>=', $thisMonthStart)
            ->select('supplier_id', DB::raw('SUM(total) as total_amount'))
            ->groupBy('supplier_id')
            ->orderByDesc('total_amount')
            ->limit(5)
            ->with('supplier:id,name')
            ->get()
            ->map(function ($row) {
                return [
                    'name' => $row->supplier?->name ?? '—',
                    'total' => (float) $row->total_amount,
                ];
            });

        return view('purchases.dashboard', [
            'pendingReceiptsCount' => $pendingReceiptsCount,
            'unpaidInvoicesAmount' => $unpaidInvoicesAmount,
            'unpaidInvoicesCount' => $unpaidInvoicesCount,
            'pendingPurchaseOrdersCount' => $pendingPurchaseOrdersCount,
            'totalPurchases' => $totalPurchases,
            'thisMonthPurchases' => $thisMonthPurchases,
            'purchasesChangePercent' => $purchasesChangePercent,
            'activeSuppliersCount' => $activeSuppliersCount,
            'totalSuppliersCount' => $totalSuppliersCount,
            'totalInvoices' => $totalInvoices,
            'monthlyData' => $monthlyData,
            'topSuppliers' => $topSuppliers,
        ]);
    }
}
