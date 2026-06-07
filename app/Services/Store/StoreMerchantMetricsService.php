<?php

declare(strict_types=1);

namespace App\Services\Store;

use App\Models\PosProduct;
use App\Models\PosSale;
use App\Models\PosSaleItem;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

final class StoreMerchantMetricsService
{
    /**
     * @return array{
     *   sales_today: float,
     *   orders_today: int,
     *   revenue_month: float,
     *   pending_collection: int,
     *   payment_methods: list<array{method:string, label:string, orders:int, total:float}>,
     *   daily_sales: list<array{date:string, total:float, orders:int}>,
     *   top_products: list<array{name:string, qty:float, revenue:float}>,
     *   low_stock: list<array{name:string, qty:float, alert:float}>,
     *   recent_orders: list<array{id:int, invoice_number:string, total:float, customer:?string, created_at:string}>
     * }
     */
    public function snapshot(int $tenantUserId): array
    {
        $today = Carbon::today();
        $base = $this->onlineOrders($tenantUserId);

        $onlineToday = (clone $base)->revenueRecognized()->whereDate('created_at', $today);

        $salesToday = (float) (clone $onlineToday)->sum('total_amount');
        $ordersToday = (int) (clone $onlineToday)->count();

        $revenueMonth = (float) (clone $base)
            ->revenueRecognized()
            ->where('created_at', '>=', Carbon::now()->startOfMonth())
            ->sum('total_amount');

        return [
            'sales_today' => round($salesToday, 2),
            'orders_today' => $ordersToday,
            'revenue_month' => round($revenueMonth, 2),
            'pending_collection' => $this->pendingCollectionCount($tenantUserId),
            'payment_methods' => $this->paymentMethodsBreakdown($tenantUserId, Carbon::now()->startOfMonth()),
            'daily_sales' => $this->dailySalesSeries($tenantUserId, 7),
            'top_products' => $this->topProducts($tenantUserId),
            'low_stock' => $this->lowStockAlerts($tenantUserId),
            'recent_orders' => $this->recentOrders($tenantUserId),
        ];
    }

    public function pendingCollectionCount(int $tenantUserId): int
    {
        return (int) $this->onlineOrders($tenantUserId)->awaitingMerchantAction()->count();
    }

    /**
     * @return list<array{method:string, label:string, orders:int, total:float}>
     */
    public function paymentMethodsBreakdown(int $tenantUserId, Carbon $since): array
    {
        $labels = [
            PosSale::PAYMENT_COD => 'الدفع عند الاستلام',
            PosSale::PAYMENT_CARD => 'بطاقة / Paymob',
            PosSale::PAYMENT_MANUAL_TRANSFER => 'تحويل بنكي',
            PosSale::PAYMENT_CASH => 'نقدي',
            PosSale::PAYMENT_BANK => 'بنك',
            PosSale::PAYMENT_MIXED => 'مختلط',
            PosSale::PAYMENT_OTHER => 'أخرى',
        ];

        return $this->onlineOrders($tenantUserId)
            ->revenueRecognized()
            ->where('created_at', '>=', $since)
            ->select([
                'payment_method',
                DB::raw('COUNT(*) as orders'),
                DB::raw('SUM(total_amount) as total'),
            ])
            ->groupBy('payment_method')
            ->orderByDesc('total')
            ->get()
            ->map(fn ($row) => [
                'method' => (string) $row->payment_method,
                'label' => $labels[(string) $row->payment_method] ?? (string) $row->payment_method,
                'orders' => (int) $row->orders,
                'total' => round((float) $row->total, 2),
            ])
            ->values()
            ->all();
    }

    /**
     * @return list<array{date:string, total:float, orders:int}>
     */
    public function dailySalesSeries(int $tenantUserId, int $days = 7): array
    {
        $days = max(1, min($days, 90));
        $from = Carbon::today()->subDays($days - 1)->startOfDay();

        $rows = $this->onlineOrders($tenantUserId)
            ->revenueRecognized()
            ->where('created_at', '>=', $from)
            ->select([
                DB::raw('DATE(created_at) as sale_date'),
                DB::raw('SUM(total_amount) as total'),
                DB::raw('COUNT(*) as orders'),
            ])
            ->groupBy(DB::raw('DATE(created_at)'))
            ->orderBy('sale_date')
            ->get()
            ->keyBy(fn ($row) => (string) $row->sale_date);

        $series = [];
        for ($i = 0; $i < $days; $i++) {
            $date = Carbon::today()->subDays($days - 1 - $i)->toDateString();
            $row = $rows->get($date);
            $series[] = [
                'date' => $date,
                'total' => round((float) ($row->total ?? 0), 2),
                'orders' => (int) ($row->orders ?? 0),
            ];
        }

        return $series;
    }

    private function onlineOrders(int $tenantUserId): Builder
    {
        return PosSale::withoutGlobalScopes()
            ->forTenant($tenantUserId)
            ->onlineStore();
    }

    /**
     * @return list<array{name:string, qty:float, revenue:float}>
     */
    private function topProducts(int $tenantUserId): array
    {
        return PosSaleItem::query()
            ->select([
                'pos_products.name',
                DB::raw('SUM(pos_sale_items.quantity) as qty'),
                DB::raw('SUM(pos_sale_items.quantity * pos_sale_items.unit_price) as revenue'),
            ])
            ->join('pos_sales', 'pos_sales.id', '=', 'pos_sale_items.pos_sale_id')
            ->join('pos_products', 'pos_products.id', '=', 'pos_sale_items.pos_product_id')
            ->where('pos_sales.user_id', $tenantUserId)
            ->where('pos_sales.sale_channel', PosSale::CHANNEL_ONLINE_STORE)
            ->whereIn('pos_sales.status', PosSale::revenueRecognizedStatuses())
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
    }

    /**
     * @return list<array{name:string, qty:float, alert:float}>
     */
    private function lowStockAlerts(int $tenantUserId): array
    {
        return PosProduct::query()
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
    }

    /**
     * @return list<array{id:int, invoice_number:string, total:float, customer:?string, created_at:string}>
     */
    private function recentOrders(int $tenantUserId): array
    {
        return $this->onlineOrders($tenantUserId)
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
    }
}
