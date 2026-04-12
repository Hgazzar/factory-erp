<?php

namespace App\Http\Controllers;

use App\Models\CostCenter;
use App\Models\Item;
use App\Models\ItemWarehouse;
use App\Models\StockAdjustment;
use App\Models\StockAdjustmentItem;
use App\Models\StockMovement;
use App\Models\Warehouse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class StockAdjustmentController extends Controller
{
    public function index(Request $request): View
    {
        $query = StockAdjustment::with(['warehouse', 'costCenter'])
            ->orderByDesc('adjustment_date')
            ->orderByDesc('id');

        if ($request->filled('search')) {
            $q = $request->search;
            $query->where(function ($qry) use ($q) {
                $qry->where('adjustment_number', 'like', "%{$q}%")
                    ->orWhereHas('warehouse', fn ($w) => $w->where('name_ar', 'like', "%{$q}%")->orWhere('code', 'like', "%{$q}%"));
            });
        }

        $adjustments = $query->withCount('items')
            ->withSum('items as total_quantity', 'quantity')
            ->paginate(15)->withQueryString();

        $ids = $adjustments->pluck('id')->toArray();
        $totals = StockAdjustmentItem::whereIn('stock_adjustment_id', $ids)
            ->selectRaw('stock_adjustment_id, SUM(quantity * unit_cost) as total_value, MAX(reason) as reason_sample')
            ->groupBy('stock_adjustment_id')
            ->get()
            ->keyBy('stock_adjustment_id');

        foreach ($adjustments as $adj) {
            $adj->total_value = $totals->get($adj->id)?->total_value ?? 0;
            $adj->reason_label = $totals->get($adj->id)?->reason_sample;
        }

        return view('inventory.adjustments.index', compact('adjustments'));
    }

    public function create(): View
    {
        $warehouses = Warehouse::where('is_active', true)->orderBy('name_ar')->get(['id', 'code', 'name_ar']);
        $costCenters = CostCenter::where('status', 'active')->orderBy('name')->get(['id', 'code', 'name']);
        $reasons = config('inventory.adjustment_reasons', []);
        $types = config('inventory.adjustment_types', ['add' => 'إضافة كمية', 'deduct' => 'خصم كمية']);
        return view('inventory.adjustments.create', compact('warehouses', 'costCenters', 'reasons', 'types'));
    }

    /**
     * إرجاع الأصناف حسب المستودع ونوع التسوية (إضافة = كل الأصناف مع الرصيد، خصم = فقط من لديه رصيد).
     */
    public function itemsForAdjustment(Request $request)
    {
        $warehouseId = $request->integer('warehouse_id');
        $type = $request->get('type', 'add');
        $uid = (int) auth()->id();
        if ($warehouseId <= 0) {
            return response()->json([]);
        }

        $query = ItemWarehouse::where('warehouse_id', $warehouseId)
            ->where('user_id', $uid)
            ->with('item:id,code,name_ar,name_en,cost');

        if ($type === 'deduct') {
            $query->whereRaw('quantity > 0');
        }

        $items = $query->get()->map(fn ($iw) => [
            'id' => $iw->item_id,
            'code' => $iw->item->code,
            'name_ar' => $iw->item->name_ar,
            'name_en' => $iw->item->name_en,
            'available_quantity' => (float) $iw->quantity,
            'unit_cost' => (float) ($iw->item->cost ?? 0),
        ]);

        if ($type === 'add') {
            $existingIds = $items->pluck('id')->toArray();
            Item::whereNotIn('id', $existingIds)->get(['id', 'code', 'name_ar', 'name_en', 'cost'])->each(function ($item) use (&$items) {
                $items->push([
                    'id' => $item->id,
                    'code' => $item->code,
                    'name_ar' => $item->name_ar,
                    'name_en' => $item->name_en,
                    'available_quantity' => 0,
                    'unit_cost' => (float) ($item->cost ?? 0),
                ]);
            });
        }

        return response()->json($items->values()->all());
    }

    public function store(Request $request): RedirectResponse
    {
        $uid = (int) auth()->id();
        $rules = [
            'adjustment_date' => 'required|date',
            'warehouse_id' => ['required', Rule::exists('warehouses', 'id')->where('user_id', $uid)],
            'type' => 'required|in:add,deduct',
            'items' => 'required|array|min:1',
            'items.*.item_id' => ['required', Rule::exists('items', 'id')->where('user_id', $uid)],
            'items.*.quantity' => 'required|numeric|min:0.0001',
            'items.*.unit_cost' => 'nullable|numeric|min:0',
            'items.*.reason' => 'nullable|string|max:50',
            'items.*.notes' => 'nullable|string|max:500',
        ];

        if ($request->type === 'deduct') {
            $rules['cost_center_id'] = ['required', Rule::exists('cost_centers', 'id')->where('user_id', $uid)];
        } else {
            $rules['cost_center_id'] = ['nullable', Rule::exists('cost_centers', 'id')->where('user_id', $uid)];
        }

        $request->validate($rules, [
            'cost_center_id.required' => 'مركز التكلفة مطلوب عند تسوية من نوع خصم (تلف، هالك، عينات).',
        ]);

        $warehouseId = (int) $request->warehouse_id;
        $type = $request->type;
        $costCenterId = $request->filled('cost_center_id') ? (int) $request->cost_center_id : null;

        foreach ($request->items as $row) {
            $qty = (float) $row['quantity'];
            if ($type === 'deduct') {
                $available = (float) ItemWarehouse::where('warehouse_id', $warehouseId)
                    ->where('user_id', $uid)
                    ->where('item_id', $row['item_id'])
                    ->value('quantity');
                if ($qty > $available) {
                    $item = Item::find($row['item_id']);
                    return redirect()->back()
                        ->withInput()
                        ->with('error', 'الكمية المراد خصمها تتجاوز الرصيد المتوفر. صنف: ' . ($item->name_ar ?? $item->code));
                }
            }
        }

        $year = now()->format('Y');
        $nextSeq = StockAdjustment::whereYear('created_at', $year)->count() + 1;
        $adjustmentNumber = 'ADJ-' . $year . '-' . str_pad((string) $nextSeq, 4, '0', STR_PAD_LEFT);

        $adjustment = StockAdjustment::create([
            'user_id' => $uid,
            'adjustment_number' => $adjustmentNumber,
            'adjustment_date' => $request->adjustment_date,
            'warehouse_id' => $warehouseId,
            'cost_center_id' => $costCenterId,
            'type' => $type,
            'status' => 'completed',
            'notes' => $request->notes,
        ]);

        foreach ($request->items as $row) {
            $itemId = (int) $row['item_id'];
            $qty = (float) $row['quantity'];
            if ($qty <= 0) {
                continue;
            }
            $unitCost = isset($row['unit_cost']) && $row['unit_cost'] !== '' ? (float) $row['unit_cost'] : (float) (Item::find($itemId)->cost ?? 0);

            StockAdjustmentItem::create([
                'stock_adjustment_id' => $adjustment->id,
                'item_id' => $itemId,
                'quantity' => $qty,
                'unit_cost' => $unitCost,
                'reason' => $row['reason'] ?? null,
                'notes' => $row['notes'] ?? null,
            ]);

            $pivot = ItemWarehouse::firstOrCreate(
                ['user_id' => $uid, 'item_id' => $itemId, 'warehouse_id' => $warehouseId],
                ['quantity' => 0, 'reserved_quantity' => 0]
            );

            if ($type === 'add') {
                $oldQty = (float) $pivot->quantity;
                $item = Item::find($itemId);
                $oldCost = (float) ($item->cost ?? 0);
                $pivot->increment('quantity', $qty);
                if ($oldQty + $qty > 0 && $unitCost >= 0) {
                    $newAvgCost = ($oldQty * $oldCost + $qty * $unitCost) / ($oldQty + $qty);
                    Item::where('id', $itemId)->update(['cost' => round($newAvgCost, 4)]);
                }
                StockMovement::create([
                    'user_id' => $uid,
                    'warehouse_id' => $warehouseId,
                    'item_id' => $itemId,
                    'quantity' => $qty,
                    'movement_type' => 'adjustment_in',
                    'reference_type' => StockAdjustment::class,
                    'reference_id' => $adjustment->id,
                ]);
            } else {
                $pivot->decrement('quantity', $qty);
                StockMovement::create([
                    'user_id' => $uid,
                    'warehouse_id' => $warehouseId,
                    'item_id' => $itemId,
                    'quantity' => -$qty,
                    'movement_type' => 'adjustment_out',
                    'reference_type' => StockAdjustment::class,
                    'reference_id' => $adjustment->id,
                    'cost_center_id' => $costCenterId,
                ]);
            }
        }

        return redirect()->route('inventory.adjustments.index')->with('success', 'تم حفظ التسوية بنجاح.');
    }

    public function show(StockAdjustment $adjustment): View
    {
        $adjustment->load(['warehouse', 'costCenter', 'items.item']);
        $adjustment->total_quantity = $adjustment->items->sum('quantity');
        $adjustment->total_value = $adjustment->items->sum(fn ($i) => (float) $i->quantity * (float) $i->unit_cost);
        return view('inventory.adjustments.show', compact('adjustment'));
    }
}
