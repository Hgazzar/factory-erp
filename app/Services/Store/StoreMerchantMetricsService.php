<?php

declare(strict_types=1);

namespace App\Services\Store;

use App\Models\PosProduct;
use App\Models\PosSale;
use App\Models\PosSaleItem;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

final class StoreMerchantMetricsService
{
    /**
     * @return array{
     *   sales_today: float,
     *   orders_today: int,
     *   revenue_month: float,
     *   top_products: list<array{name:string, qty:float, revenue:float}>,
     *   low_stock: list<array{name:string, qty:float, alert:float}>,
     *   recent_orders: list<array{id:int, invoice_number:string, total:float, customer:?string, created_at:string}>
     * }
     */
    public function snapshot(int $tenantUserId): array
    {
        $today = Carbon::today();

        $onlineToday = PosSale::withoutGlobalScopes()
            ->where('user_id', $tenantUserId)
            ->where('sale_channel', PosSale::CHANNEL_ONLINE_STORE)
            ->whereDate('created_at', $today);

        $salesToday = (float) (clone $onlineToday)->sum('total_amount');
        $ordersToday = (int) (clone $onlineToday)->count();

        $revenueMonth = (float) PosSale::withoutGlobalScopes()
            ->where('user_id', $tenantUserId)
            ->where('sale_channel', PosSale::CHANNEL_ONLINE_STORE)
            ->where('created_at', '>=', Carbon::now()->startOfMonth())
            ->sum('total_amount');

        $topProducts = PosSaleItem::query()
            ->select([
                'pos_products.name',
                DB::raw('SUM(pos_sale_items.quantity) as qty'),
                DB::raw('SUM(pos_sale_items.quantity * pos_sale_items.unit_price) as revenue'),
            ])
            ->join('pos_sales', 'pos_sales.id', '=', 'pos_sale_items.pos_sale_id')
            ->join('pos_products', 'pos_products.id', '=', 'pos_sale_items.pos_product_id')
            ->where('pos_sales.user_id', $tenantUserId)
            ->where('pos_sales.sale_channel', PosSale::CHANNEL_ONLINE_STORE)
            ->where('pos_sales.created_at', '>=', Carbon::now()->subDays(30))
            ->groupBy('pos_products.name')
            ->orderByDesc('qty')
            ->limit(5)
            ->get()
            ->map(fn ($row) => [
                'name' => (string) $row->name,
                'qty' => round((float) $row->qty, 2),
                'revenue' => round((float) $row->revenue, 2),
            ])
            ->values()
            ->all();

        $lowStock = PosProduct::query()
            ->where('user_id', $tenantUserId)
            ->where('is_active', true)
            ->where('is_published_online', true)
            ->whereColumn('current_quantity', '<=', 'low_stock_alert_quantity')
            ->where('low_stock_alert_quantity', '>', 0)
            ->orderBy('current_quantity')
            ->limit(8)
            ->get(['name', 'current_quantity', 'low_stock_alert_quantity'])
            ->map(fn (PosProduct $p) => [
                'name' => $p->name,
                'qty' => round((float) $p->current_quantity, 2),
                'alert' => round((float) $p->low_stock_alert_quantity, 2),
            ])
            ->values()
            ->all();

        $recentOrders = PosSale::withoutGlobalScopes()
            ->where('user_id', $tenantUserId)
            ->where('sale_channel', PosSale::CHANNEL_ONLINE_STORE)
            ->orderByDesc('id')
            ->limit(6)
            ->get(['id', 'invoice_number', 'total_amount', 'customer_name', 'created_at'])
            ->map(fn (PosSale $s) => [
                'id' => (int) $s->id,
                'invoice_number' => (string) $s->invoice_number,
                'total' => round((float) $s->total_amount, 2),
                'customer' => $s->customer_name,
                'created_at' => $s->created_at?->format('Y-m-d H:i') ?? '',
            ])
            ->values()
            ->all();

        return [
            'sales_today' => round($salesToday, 2),
            'orders_today' => $ordersToday,
            'revenue_month' => round($revenueMonth, 2),
            'top_products' => $topProducts,
            'low_stock' => $lowStock,
            'recent_orders' => $recentOrders,
        ];
    }
}
