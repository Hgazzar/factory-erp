<?php

namespace App\Http\Controllers;

use App\Models\Item;
use App\Models\ItemWarehouse;
use App\Models\StockMovement;
use App\Models\StockTransfer;
use App\Models\StockTransferItem;
use App\Models\Warehouse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class StockTransferController extends Controller
{
    public function index(Request $request): View
    {
        $query = StockTransfer::with(['sourceWarehouse', 'destWarehouse'])
            ->withCount('items')
            ->orderByDesc('transfer_date')
            ->orderByDesc('id');

        if ($request->filled('search')) {
            $q = $request->search;
            $query->where(function ($qry) use ($q) {
                $qry->where('transfer_number', 'like', "%{$q}%")
                    ->orWhereHas('sourceWarehouse', fn ($w) => $w->where('name_ar', 'like', "%{$q}%")->orWhere('code', 'like', "%{$q}%"))
                    ->orWhereHas('destWarehouse', fn ($w) => $w->where('name_ar', 'like', "%{$q}%")->orWhere('code', 'like', "%{$q}%"));
            });
        }

        $transfers = $query->paginate(15)->withQueryString();
        return view('inventory.transfers.index', compact('transfers'));
    }

    public function create(): View
    {
        $warehouses = Warehouse::where('is_active', true)->orderBy('name_ar')->get(['id', 'code', 'name_ar']);
        return view('inventory.transfers.create', compact('warehouses'));
    }

    /**
     * إرجاع الأصناف المتوفرة في مستودع معين مع الكمية (للمستخدم عند إضافة صف).
     */
    public function itemsByWarehouse(Request $request)
    {
        $warehouseId = $request->integer('warehouse_id');
        if ($warehouseId <= 0) {
            return response()->json([]);
        }

        $items = ItemWarehouse::where('warehouse_id', $warehouseId)
            ->whereRaw('quantity > 0')
            ->with('item:id,code,name_ar,name_en,cost')
            ->get()
            ->map(fn ($iw) => [
                'id' => $iw->item_id,
                'code' => $iw->item->code,
                'name_ar' => $iw->item->name_ar,
                'name_en' => $iw->item->name_en,
                'available_quantity' => (float) $iw->quantity,
                'unit_cost' => (float) ($iw->item->cost ?? 0),
            ]);

        return response()->json($items);
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'transfer_date' => 'required|date',
            'expected_arrival_date' => 'nullable|date',
            'source_warehouse_id' => 'required|exists:warehouses,id',
            'dest_warehouse_id' => 'required|exists:warehouses,id|different:source_warehouse_id',
            'items' => 'required|array|min:1',
            'items.*.item_id' => 'required|exists:items,id',
            'items.*.quantity' => 'required|numeric|min:0.0001',
            'items.*.notes' => 'nullable|string|max:500',
        ], [
            'dest_warehouse_id.different' => 'يجب أن يكون المستودع الوجهة مختلفاً عن المستودع المصدر.',
        ]);

        $sourceId = (int) $request->source_warehouse_id;
        $destId = (int) $request->dest_warehouse_id;

        foreach ($request->items as $row) {
            $qty = (float) $row['quantity'];
            $available = (float) ItemWarehouse::where('warehouse_id', $sourceId)
                ->where('item_id', $row['item_id'])
                ->value('quantity');
            if ($qty > $available) {
                $item = Item::find($row['item_id']);
                return redirect()->back()
                    ->withInput()
                    ->with('error', 'الكمية المراد تحويلها لأحد الأصناف تتجاوز الرصيد المتوفر في المستودع المصدر. صنف: ' . ($item->name_ar ?? $item->code));
            }
        }

        $year = now()->format('Y');
        $nextSeq = StockTransfer::whereYear('created_at', $year)->count() + 1;
        $transferNumber = 'TRF-' . $year . '-' . str_pad((string) $nextSeq, 4, '0', STR_PAD_LEFT);

        $transfer = StockTransfer::create([
            'user_id' => $uid,
            'transfer_number' => $transferNumber,
            'transfer_date' => $request->transfer_date,
            'expected_arrival_date' => $request->filled('expected_arrival_date') ? $request->expected_arrival_date : null,
            'source_warehouse_id' => $sourceId,
            'dest_warehouse_id' => $destId,
            'status' => 'completed',
            'notes' => $request->notes,
        ]);

        foreach ($request->items as $row) {
            $itemId = (int) $row['item_id'];
            $qty = (float) $row['quantity'];
            if ($qty <= 0) {
                continue;
            }

            $item = Item::find($itemId);
            $unitCost = $item ? (float) ($item->cost ?? 0) : 0;

            StockTransferItem::create([
                'stock_transfer_id' => $transfer->id,
                'item_id' => $itemId,
                'quantity' => $qty,
                'unit_cost' => $unitCost,
                'notes' => $row['notes'] ?? null,
            ]);

            $sourcePivot = ItemWarehouse::firstOrCreate(
                ['user_id' => $uid, 'item_id' => $itemId, 'warehouse_id' => $sourceId],
                ['quantity' => 0, 'reserved_quantity' => 0]
            );
            $sourcePivot->decrement('quantity', $qty);

            $destPivot = ItemWarehouse::firstOrCreate(
                ['user_id' => $uid, 'item_id' => $itemId, 'warehouse_id' => $destId],
                ['quantity' => 0, 'reserved_quantity' => 0]
            );
            $destPivot->increment('quantity', $qty);

            StockMovement::create([
                'user_id' => $uid,
                'warehouse_id' => $sourceId,
                'item_id' => $itemId,
                'quantity' => -$qty,
                'movement_type' => 'transfer_out',
                'reference_type' => StockTransfer::class,
                'reference_id' => $transfer->id,
            ]);
            StockMovement::create([
                'user_id' => $uid,
                'warehouse_id' => $destId,
                'item_id' => $itemId,
                'quantity' => $qty,
                'movement_type' => 'transfer_in',
                'reference_type' => StockTransfer::class,
                'reference_id' => $transfer->id,
            ]);
        }

        return redirect()->route('inventory.transfers.index')->with('success', 'تم حفظ التحويل بنجاح.');
    }

    public function show(StockTransfer $transfer): View
    {
        $transfer->load(['sourceWarehouse', 'destWarehouse', 'items.item']);
        return view('inventory.transfers.show', compact('transfer'));
    }
}
