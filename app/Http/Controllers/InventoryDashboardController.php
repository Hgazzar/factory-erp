<?php

namespace App\Http\Controllers;

use App\Models\DeliveryOrder;
use App\Models\Item;
use App\Models\ItemWarehouse;
use App\Models\ProductionOrder;
use App\Models\Warehouse;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class InventoryDashboardController extends Controller
{
    public function index(): View
    {
        $totalProducts = Item::count();
        $activeProducts = Item::where('is_active', true)->count();

        // نفاد المخزون: أصناف لا يوجد لها رصيد إجمالي > 0 (متوافق مع Postgres بدون groupBy على items)
        $itemIdsWithStock = ItemWarehouse::query()
            ->select('item_id')
            ->groupBy('item_id')
            ->havingRaw('SUM(quantity) > 0')
            ->pluck('item_id');
        $outOfStockCount = Item::whereNotIn('id', $itemIdsWithStock)->count();

        // تنبيهات المخزون المنخفض يُحسب من نفس مصفوفة حالة المخزون أدناه لتجنب استعلام groupBy ثانٍ
        $lowStockCount = 0;

        // القيمة الإجمالية: مجموع (كمية × سعر التكلفة) لكل صنف في كل مستودع
        $totalValue = (float) DB::table('item_warehouse')
            ->join('items', 'items.id', '=', 'item_warehouse.item_id')
            ->selectRaw('SUM(item_warehouse.quantity * COALESCE(items.cost, 0)) as total')
            ->value('total');

        $warehousesCount = Warehouse::count();
        $warehousesActive = Warehouse::where('is_active', true)->count();

        // الجرد المعلق والتحويلات المعلقة (لا توجد جداول حالياً)
        $pendingStocktake = 0;
        $pendingTransfers = 0;

        // المخزون حسب المستودع: إجمالي الكميات في كل مستودع
        $warehouseTotals = DB::table('item_warehouse')
            ->select('warehouse_id', DB::raw('SUM(quantity) as total_quantity'))
            ->groupBy('warehouse_id')
            ->pluck('total_quantity', 'warehouse_id');
        $inventoryByWarehouse = Warehouse::orderBy('code')->get()->map(fn ($w) => [
            'label' => $w->name_ar ?: $w->name_en ?: $w->code ?: 'مستودع #'.$w->id,
            'quantity' => (float) ($warehouseTotals->get($w->id, 0)),
        ]);

        // حالة المخزون: توزيع (في المخزون / منخفض / نفاد) — استعلام واحد على item_warehouse لتفادي أخطاء groupBy مع Postgres
        $totalsByItem = DB::table('item_warehouse')
            ->select('item_id', DB::raw('SUM(quantity) as total_qty'))
            ->groupBy('item_id')
            ->pluck('total_qty', 'item_id');
        $inStockCount = 0;
        $lowStockItemsCount = 0;
        $outOfStockItemsCount = 0;
        foreach (Item::select('id', 'min_stock', 'is_active')->get() as $item) {
            $qty = (float) ($totalsByItem->get($item->id, 0));
            $minStock = (float) ($item->min_stock ?? 0);
            if ($qty <= 0) {
                $outOfStockItemsCount++;
            } elseif ($minStock > 0 && $qty <= $minStock) {
                $lowStockItemsCount++;
                if ($item->is_active) {
                    $lowStockCount++;
                }
            } else {
                $inStockCount++;
            }
        }
        $inventoryStatus = [
            ['label' => 'في المخزون', 'count' => $inStockCount, 'color' => '#22c55e'],
            ['label' => 'منخفض', 'count' => $lowStockItemsCount, 'color' => '#f59e0b'],
            ['label' => 'نفاد', 'count' => $outOfStockItemsCount, 'color' => '#ef4444'],
        ];

        $pendingProductionOrdersCount = ProductionOrder::query()
            ->where('status', ProductionOrder::STATUS_PENDING)
            ->count();
        $pendingDeliveryOrdersCount = DeliveryOrder::query()
            ->where('status', DeliveryOrder::STATUS_PENDING)
            ->count();

        return view('inventory.dashboard', compact(
            'totalProducts',
            'activeProducts',
            'lowStockCount',
            'outOfStockCount',
            'totalValue',
            'pendingStocktake',
            'pendingTransfers',
            'warehousesCount',
            'warehousesActive',
            'inventoryByWarehouse',
            'inventoryStatus',
            'pendingProductionOrdersCount',
            'pendingDeliveryOrdersCount'
        ));
    }
}
