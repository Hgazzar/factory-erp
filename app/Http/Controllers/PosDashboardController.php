<?php

namespace App\Http\Controllers;

use App\Models\CompanySetting;
use App\Models\Item;
use App\Models\PosDevice;
use App\Models\PosSale;
use App\Models\PosSaleLine;
use App\Models\PosSession;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PosDashboardController extends Controller
{
    public function index(Request $request): View|RedirectResponse
    {
        $user = auth()->user();
        if ($user && ! $user->isAdminOrSuperAdmin() && ! PosDevice::query()->exists()) {
            return redirect()
                ->route('dashboard')
                ->with('error', 'لا يوجد جهاز نقطة بيع مُعرّف لحسابك حالياً. يُرجى مراجعة الإدارة لتجهيز الجهاز.');
        }

        $day = $request->date('date') ?: now()->toDateString();

        $start = Carbon::parse($day)->startOfDay();
        $end = Carbon::parse($day)->endOfDay();

        $uid = (int) auth()->id();

        $todaySalesTotal = (float) PosSale::query()
            ->completed()
            ->whereBetween('created_at', [$start, $end])
            ->sum('total_price');

        $todayTransactionsCount = (int) PosSale::query()
            ->completed()
            ->whereBetween('created_at', [$start, $end])
            ->count();

        $activeDevicesCount = PosDevice::query()->active()->count();
        $devicesTotalCount = PosDevice::query()->count();
        $openSessionsCount = PosSession::query()->open()->count();

        $salesForDay = PosSale::query()
            ->completed()
            ->whereBetween('created_at', [$start, $end])
            ->get(['created_at', 'total_price']);

        $hourlySar = array_fill(0, 24, 0.0);
        foreach ($salesForDay as $sale) {
            $h = (int) $sale->created_at->format('G');
            $hourlySar[$h] += (float) $sale->total_price;
        }

        $paymentTotals = PosSale::query()
            ->completed()
            ->whereBetween('created_at', [$start, $end])
            ->selectRaw('payment_method, SUM(total_price) as total')
            ->groupBy('payment_method')
            ->pluck('total', 'payment_method');

        $paymentChart = $paymentTotals->map(fn ($v) => round((float) $v, 2))->toArray();

        $recentSales = PosSale::query()
            ->with(['posDevice'])
            ->completed()
            ->whereBetween('created_at', [$start, $end])
            ->latest()
            ->limit(15)
            ->get();

        $bestSelling = PosSaleLine::query()
            ->join('pos_sales', 'pos_sale_lines.pos_sale_id', '=', 'pos_sales.id')
            ->where('pos_sales.user_id', $uid)
            ->where('pos_sales.status', PosSale::STATUS_COMPLETED)
            ->whereBetween('pos_sales.created_at', [$start, $end])
            ->selectRaw('pos_sale_lines.item_id, SUM(pos_sale_lines.quantity) as qty_sold, SUM(pos_sale_lines.line_total) as revenue_sar')
            ->groupBy('pos_sale_lines.item_id')
            ->orderByDesc('qty_sold')
            ->limit(10)
            ->get();

        $itemIds = $bestSelling->pluck('item_id')->unique()->filter()->values();
        $items = Item::query()->whereIn('id', $itemIds)->get()->keyBy('id');

        $bestSellingProducts = $bestSelling->map(function ($row) use ($items) {
            $item = $items->get($row->item_id);

            return [
                'item_id' => (int) $row->item_id,
                'name' => $item ? ($item->name_ar ?: $item->name_en ?: $item->code) : ('#'.$row->item_id),
                'code' => $item?->code,
                'qty_sold' => (float) $row->qty_sold,
                'revenue_sar' => round((float) $row->revenue_sar, 2),
            ];
        });

        $devices = PosDevice::query()
            ->with(['warehouse'])
            ->orderBy('name')
            ->get()
            ->map(fn (PosDevice $d) => [
                'id' => $d->id,
                'name' => $d->name,
                'status' => $d->status,
                'warehouse_name' => $d->warehouse?->name_ar ?? $d->warehouse?->name_en,
            ]);

        return view('pos.dashboard', [
            'filterDate' => $day,
            'kpis' => [
                'today_sales_sar' => round($todaySalesTotal, 2),
                'today_sales_label' => $this->formatMoneyWithCurrency($todaySalesTotal),
                'today_transactions' => $todayTransactionsCount,
                'active_devices' => $activeDevicesCount,
                'devices_total' => $devicesTotalCount,
                'open_sessions' => $openSessionsCount,
            ],
            'hourlySar' => $hourlySar,
            'hourlyLabels' => range(0, 23),
            'paymentChart' => $paymentChart,
            'recentSales' => $recentSales,
            'bestSellingProducts' => $bestSellingProducts,
            'devices' => $devices,
        ]);
    }

    private function formatMoneyWithCurrency(float $amount): string
    {
        return CompanySetting::resolvedCurrencyCode().' '.number_format($amount, 2, '.', ',');
    }
}
