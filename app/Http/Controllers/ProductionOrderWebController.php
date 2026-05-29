<?php

namespace App\Http\Controllers;

use App\Models\AuditTrail;
use App\Models\Item;
use App\Models\ProductionOrder;
use App\Models\ProductionOrderIngredient;
use App\Models\ProductionOrderItem;
use App\Models\Warehouse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;

class ProductionOrderWebController extends Controller
{
    public function index(): View
    {
        $orders = ProductionOrder::query()
            ->withCount(['productionItems', 'ingredients'])
            ->orderByDesc('id')
            ->paginate(20);

        return view('production_orders.index', compact('orders'));
    }

    public function create(): View
    {
        $finishedGoods = Item::query()
            ->active()
            ->ofType(Item::TYPE_FINISHED_GOOD)
            ->orderBy('name_ar')
            ->get(['id', 'code', 'name_ar', 'current_stock']);

        $rawMaterials = Item::query()
            ->active()
            ->ofType(Item::TYPE_RAW_MATERIAL)
            ->orderBy('name_ar')
            ->get(['id', 'code', 'name_ar', 'current_stock']);

        $bomSuggestionsBaseUrl = url('/production-orders/bom-suggestions');

        $warehouseOptions = Warehouse::query()
            ->orderBy('name_ar')
            ->get(['id', 'code', 'name_ar'])
            ->map(fn (Warehouse $w) => [
                'value' => (string) $w->id,
                'label' => $w->code.' — '.$w->name_ar,
            ])
            ->values()
            ->all();

        return view('production_orders.create', compact(
            'finishedGoods',
            'rawMaterials',
            'bomSuggestionsBaseUrl',
            'warehouseOptions',
        ));
    }

    /**
     * اقتراح خامات من BOM المبدئي المحفوظ للمنتج التام (اختياري حتى اكتمال مديول BOM).
     */
    public function bomSuggestions(Item $item): JsonResponse
    {
        if ($item->type !== Item::TYPE_FINISHED_GOOD) {
            return response()->json([
                'message' => 'يجب اختيار صنف من نوع منتج تام.',
                'components' => [],
            ], 422);
        }

        $rows = $item->bomComponents()
            ->with(['componentItem:id,type,code,name_ar,is_active'])
            ->get();

        $components = [];
        foreach ($rows as $row) {
            $c = $row->componentItem;
            if (! $c || $c->type !== Item::TYPE_RAW_MATERIAL || ! $c->is_active) {
                continue;
            }
            $components[] = [
                'item_id' => (int) $c->id,
                'quantity_per_unit' => (string) $row->quantity_per_unit,
            ];
        }

        return response()->json(['components' => $components]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'start_date' => ['nullable', 'date'],
            'raw_materials_warehouse_id' => ['required', 'integer', 'exists:warehouses,id'],
            'finished_goods_warehouse_id' => ['required', 'integer', 'exists:warehouses,id'],
            'finished_item_id' => ['required', 'integer', 'exists:items,id'],
            'planned_quantity' => ['required', 'numeric', 'min:0.0001'],
            'ingredients' => ['required', 'array', 'min:1'],
            'ingredients.*.item_id' => ['required', 'integer', 'exists:items,id'],
            'ingredients.*.quantity_to_consume' => ['required', 'numeric', 'min:0.0001'],
        ]);

        $finished = Item::query()->findOrFail((int) $validated['finished_item_id']);
        if ($finished->type !== Item::TYPE_FINISHED_GOOD) {
            return back()->withInput()->with('error', 'يجب اختيار صنف من نوع «منتج تام».');
        }

        $ingredientIds = [];
        foreach ($validated['ingredients'] as $row) {
            $iid = (int) $row['item_id'];
            $ing = Item::query()->findOrFail($iid);
            if ($ing->type !== Item::TYPE_RAW_MATERIAL) {
                return back()->withInput()->with('error', sprintf('الصنف «%s» ليس مادة خام.', $ing->code));
            }
            if (in_array($iid, $ingredientIds, true)) {
                return back()->withInput()->with('error', 'لا تكرر نفس المادة الخام في أكثر من سطر؛ ادمج الكميات في سطر واحد.');
            }
            $ingredientIds[] = $iid;
        }

        $created = null;

        try {
            DB::transaction(function () use ($validated, $finished, &$created) {
                $order = ProductionOrder::create([
                    'production_number' => 'TMP-'.Str::uuid()->toString(),
                    'status' => ProductionOrder::STATUS_PENDING,
                    'start_date' => $validated['start_date'] ?? null,
                    'end_date' => null,
                    'raw_materials_warehouse_id' => (int) $validated['raw_materials_warehouse_id'],
                    'finished_goods_warehouse_id' => (int) $validated['finished_goods_warehouse_id'],
                ]);

                $order->production_number = sprintf('PO-%s-%06d', now()->format('Y'), $order->id);
                $order->save();

                ProductionOrderItem::create([
                    'production_order_id' => $order->id,
                    'item_id' => $finished->id,
                    'planned_quantity' => $validated['planned_quantity'],
                    'produced_quantity' => 0,
                ]);

                foreach ($validated['ingredients'] as $row) {
                    ProductionOrderIngredient::create([
                        'production_order_id' => $order->id,
                        'item_id' => (int) $row['item_id'],
                        'quantity_to_consume' => $row['quantity_to_consume'],
                    ]);
                }

                $created = $order;
            });
        } catch (\Throwable $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        if ($created) {
            AuditTrail::log('create', 'production_orders', $created->id, null, [
                'production_number' => $created->production_number,
                'status' => $created->status,
                'start_date' => $created->start_date?->format('Y-m-d'),
            ]);
        }

        return redirect()
            ->route('production-orders.show', $created->id)
            ->with('success', 'تم إنشاء أمر الإنتاج. عند الجاهزية استخدم «إتمام الإنتاج» لتحديث المخزون.');
    }

    public function show(ProductionOrder $productionOrder): View
    {
        $productionOrder->load([
            'productionItems.item:id,code,name_ar,type,current_stock',
            'ingredients.item:id,code,name_ar,type,current_stock',
            'rawMaterialsWarehouse:id,code,name_ar',
            'finishedGoodsWarehouse:id,code,name_ar',
        ]);

        return view('production_orders.show', compact('productionOrder'));
    }

    /**
     * مقارنة كميات الاستهلاك في أمر الإنتاج مع الرصيد الحالي (current_stock) لكل مادة خام.
     */
    private function ingredientShortages(ProductionOrder $productionOrder): array
    {
        $productionOrder->loadMissing([
            'ingredients.item:id,code,name_ar,type,current_stock,cost',
        ]);

        $shortages = [];

        foreach ($productionOrder->ingredients as $row) {
            $item = $row->item;
            if (! $item || $item->type !== Item::TYPE_RAW_MATERIAL) {
                continue;
            }

            $needed = (float) $row->quantity_to_consume;
            $available = (float) ($item->current_stock ?? 0);
            $gap = $needed - $available;

            if ($gap > 0.0000001) {
                $shortages[] = [
                    'item_id' => (int) $item->id,
                    'code' => (string) $item->code,
                    'name_ar' => (string) ($item->name_ar ?? ''),
                    'needed' => round($needed, 4),
                    'available' => round($available, 4),
                    'shortage' => round($gap, 4),
                    'cost' => (float) ($item->cost ?? 0),
                ];
            }
        }

        return $shortages;
    }

    public function ingredientShortage(ProductionOrder $productionOrder): JsonResponse
    {
        if (! in_array($productionOrder->status, [ProductionOrder::STATUS_PENDING, ProductionOrder::STATUS_IN_PROGRESS], true)) {
            return response()->json([
                'message' => 'التحقق متاح فقط لأوامر إنتاج «معلقة» أو «قيد التنفيذ».',
                'has_shortage' => false,
                'shortages' => [],
            ], 422);
        }

        $shortages = $this->ingredientShortages($productionOrder);

        return response()->json([
            'has_shortage' => $shortages !== [],
            'shortages' => $shortages,
        ]);
    }

    public function prefillPurchaseOrder(ProductionOrder $productionOrder): RedirectResponse
    {
        if (! in_array($productionOrder->status, [ProductionOrder::STATUS_PENDING, ProductionOrder::STATUS_IN_PROGRESS], true)) {
            return redirect()
                ->route('production-orders.show', $productionOrder)
                ->with('error', 'لا يمكن إنشاء طلب شراء من هذا الأمر في حالته الحالية.');
        }

        $shortages = $this->ingredientShortages($productionOrder);

        if ($shortages === []) {
            return redirect()
                ->route('production-orders.show', $productionOrder)
                ->with('info', 'لا يوجد ناقص من الخامات وفق الرصيد الحالي لهذا الأمر.');
        }

        $lines = [];
        foreach ($shortages as $s) {
            $lines[] = [
                'item_id' => $s['item_id'],
                'quantity' => $s['shortage'],
                'unit_price' => $s['cost'],
                'description' => 'تغطية ناقص — أمر إنتاج '.$productionOrder->production_number,
            ];
        }

        session([
            'purchase_order_prefill_lines' => $lines,
            'purchase_order_prefill_reference' => 'ناقص خامات — '.$productionOrder->production_number,
        ]);

        return redirect()->route('purchases.orders.create');
    }

    public function complete(Request $request, ProductionOrder $productionOrder): RedirectResponse
    {
        $productionOrder->load('productionItems');

        $rules = [];
        foreach ($productionOrder->productionItems as $line) {
            $rules['produced.'.$line->id] = ['required', 'numeric', 'min:0.0001'];
        }

        if ($rules === []) {
            return back()->with('error', 'لا توجد بنود منتج على هذا الأمر.');
        }

        $validated = $request->validate($rules);

        try {
            $productionOrder->markCompleted($validated['produced']);
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()
            ->route('production-orders.show', $productionOrder->id)
            ->with('success', 'تم إتمام الإنتاج: خصم الخامات وإضافة المنتج التام إلى الرصيد الحالي.');
    }
}
