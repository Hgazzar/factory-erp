<?php

namespace App\Http\Controllers;

use App\Models\PurchaseInvoice;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\ReceiveNote;
use App\Models\Supplier;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class PurchaseReportController extends Controller
{
    public function index(Request $request): View|Response
    {
        $now = Carbon::now();

        $totalPurchaseValue = (float) PurchaseInvoice::sum('total');
        $totalOrders = PurchaseInvoice::count();
        $averageOrderValue = $totalOrders > 0 ? round($totalPurchaseValue / $totalOrders, 2) : 0;
        $activeSuppliersCount = Supplier::where('is_active', true)->count();
        $totalSuppliersCount = Supplier::count();

        $receivedGoodsCount = ReceiveNote::count();
        $pendingOrdersCount = PurchaseOrder::whereIn('status', ['pending', 'draft', 'sent'])->count();
        $paidInvoicesCount = PurchaseInvoice::whereRaw('COALESCE(paid_amount, 0) >= total')->count();
        $requestedProductsCount = (int) PurchaseOrderItem::sum('quantity');

        if ($request->get('export') === 'csv') {
            $csv = "\xEF\xBB\xBF";
            $csv .= "مؤشر,القيمة\n";
            $csv .= "إجمالي قيمة المشتريات," . round($totalPurchaseValue, 2) . "\n";
            $csv .= "إجمالي الطلبات," . $totalOrders . "\n";
            $csv .= "متوسط قيمة الطلب," . $averageOrderValue . "\n";
            $csv .= "الموردين النشطين," . $activeSuppliersCount . "\n";
            $csv .= "البضائع المستلمة," . $receivedGoodsCount . "\n";
            $csv .= "الطلبات المعلقة," . $pendingOrdersCount . "\n";
            $csv .= "الفواتير المدفوعة," . $paidInvoicesCount . "\n";
            $csv .= "المنتجات المطلوبة," . $requestedProductsCount . "\n";
            return response($csv, 200, [
                'Content-Type' => 'text/csv; charset=UTF-8',
                'Content-Disposition' => 'attachment; filename="purchases-report-' . date('Y-m-d') . '.csv"',
            ]);
        }

        $monthsEn = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
        $monthlyTrend = [];
        for ($i = 11; $i >= 0; $i--) {
            $date = $now->copy()->subMonths($i);
            $monthStart = $date->copy()->startOfMonth();
            $monthEnd = $date->copy()->endOfMonth();
            $purchases = (float) PurchaseInvoice::whereBetween('date', [$monthStart, $monthEnd])->sum('total');
            $paid = (float) PurchaseInvoice::whereBetween('date', [$monthStart, $monthEnd])->sum('paid_amount');
            $monthlyTrend[] = [
                'month' => $monthsEn[(int) $monthStart->format('n') - 1],
                'purchases' => round($purchases, 2),
                'paid' => round($paid, 2),
            ];
        }

        $suppliersDistribution = PurchaseInvoice::query()
            ->select('supplier_id', DB::raw('SUM(total) as total_amount'))
            ->groupBy('supplier_id')
            ->orderByDesc('total_amount')
            ->limit(10)
            ->with('supplier:id,name')
            ->get()
            ->map(function ($row) {
                return [
                    'name' => $row->supplier?->name ?? '—',
                    'total' => round((float) $row->total_amount, 2),
                ];
            });

        return view('purchases.reports.index', [
            'totalPurchaseValue' => $totalPurchaseValue,
            'totalOrders' => $totalOrders,
            'averageOrderValue' => $averageOrderValue,
            'activeSuppliersCount' => $activeSuppliersCount,
            'totalSuppliersCount' => $totalSuppliersCount,
            'receivedGoodsCount' => $receivedGoodsCount,
            'pendingOrdersCount' => $pendingOrdersCount,
            'paidInvoicesCount' => $paidInvoicesCount,
            'requestedProductsCount' => $requestedProductsCount,
            'monthlyTrend' => $monthlyTrend,
            'suppliersDistribution' => $suppliersDistribution,
        ]);
    }
}
