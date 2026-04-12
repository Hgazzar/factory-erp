<?php

namespace App\Http\Controllers;

use App\Models\InventoryAudit;
use App\Models\InventoryAuditLine;
use App\Models\ItemWarehouse;
use App\Models\StockMovement;
use App\Models\Warehouse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class InventoryAuditController extends Controller
{
    public function index(Request $request): View
    {
        $query = InventoryAudit::with('warehouse')
            ->orderByDesc('audit_date')
            ->orderByDesc('id');

        if ($request->filled('search')) {
            $q = $request->search;
            $query->where(function ($qry) use ($q) {
                $qry->where('audit_number', 'like', "%{$q}%")
                    ->orWhereHas('warehouse', fn ($w) => $w->where('name_ar', 'like', "%{$q}%")->orWhere('code', 'like', "%{$q}%"));
            });
        }

        $audits = $query->withCount('lines')
            ->paginate(15)->withQueryString();

        $ids = $audits->pluck('id')->toArray();
        $filled = DB::table('inventory_audit_lines')
            ->whereIn('inventory_audit_id', $ids)
            ->whereNotNull('actual_quantity')
            ->selectRaw('inventory_audit_id, COUNT(*) as cnt')
            ->groupBy('inventory_audit_id')
            ->get()
            ->keyBy('inventory_audit_id');
        $totals = DB::table('inventory_audit_lines')
            ->whereIn('inventory_audit_id', $ids)
            ->selectRaw('inventory_audit_id, SUM(difference_value) as total_value')
            ->groupBy('inventory_audit_id')
            ->get()
            ->keyBy('inventory_audit_id');

        $diffCounts = DB::table('inventory_audit_lines')
            ->whereIn('inventory_audit_id', $ids)
            ->whereRaw('difference != 0')
            ->selectRaw('inventory_audit_id, COUNT(*) as cnt')
            ->groupBy('inventory_audit_id')
            ->get()
            ->keyBy('inventory_audit_id');

        foreach ($audits as $audit) {
            $totalLines = $audit->lines_count;
            $filledLines = $filled->get($audit->id)?->cnt ?? 0;
            $audit->progress = $totalLines > 0 ? round($filledLines / $totalLines * 100, 1) : 0;
            $audit->total_difference_value = $totals->get($audit->id)?->total_value ?? 0;
            $audit->differences_count = $diffCounts->get($audit->id)?->cnt ?? 0;
        }

        return view('inventory.audits.index', compact('audits'));
    }

    public function create(): View
    {
        $warehouses = Warehouse::where('is_active', true)->orderBy('name_ar')->get(['id', 'code', 'name_ar']);
        $auditTypes = config('inventory.audit_types', []);
        $auditCategories = config('inventory.audit_categories', []);

        return view('inventory.audits.create', compact('warehouses', 'auditTypes', 'auditCategories'));
    }

    /**
     * إرجاع الأصناف للمستودع مع الرصيد الدفتري (لجرد كلي أو جزئي).
     */
    public function itemsForAudit(Request $request)
    {
        $warehouseId = $request->integer('warehouse_id');
        $type = $request->get('type', 'full');
        $category = $request->get('category');
        if ($warehouseId <= 0) {
            return response()->json([]);
        }

        $uid = (int) auth()->id();
        $query = ItemWarehouse::where('warehouse_id', $warehouseId)
            ->where('user_id', $uid)
            ->with('item:id,code,name_ar,name_en,type,cost,barcode');

        if ($type === 'partial' && $category) {
            $query->whereHas('item', fn ($q) => $q->where('type', $category));
        }

        $items = $query->get()->map(fn ($iw) => [
            'id' => $iw->item_id,
            'code' => $iw->item->code,
            'name_ar' => $iw->item->name_ar,
            'name_en' => $iw->item->name_en,
            'barcode' => $iw->item->barcode,
            'book_quantity' => (float) $iw->quantity,
            'unit_cost' => (float) ($iw->item->cost ?? 0),
        ]);

        return response()->json($items->values()->all());
    }

    public function store(Request $request): RedirectResponse
    {
        $uid = (int) auth()->id();
        $request->validate([
            'audit_date' => 'required|date',
            'warehouse_id' => ['required', Rule::exists('warehouses', 'id')->where('user_id', $uid)],
            'type' => 'required|in:full,partial',
            'category' => 'nullable|string|in:raw_material,finished_good,service',
            'lines' => 'required|array|min:1',
            'lines.*.item_id' => ['required', Rule::exists('items', 'id')->where('user_id', $uid)],
            'lines.*.book_quantity' => 'required|numeric|min:0',
            'lines.*.actual_quantity' => 'nullable|numeric|min:0',
            'lines.*.unit_cost' => 'nullable|numeric|min:0',
        ]);

        $year = now()->format('Y');
        $nextSeq = InventoryAudit::whereYear('created_at', $year)->count() + 1;
        $auditNumber = 'AUD-'.$year.'-'.str_pad((string) $nextSeq, 4, '0', STR_PAD_LEFT);

        $notes = trim(($request->description ?? '')."\n".($request->notes ?? ''));

        $audit = InventoryAudit::create([
            'user_id' => $uid,
            'audit_number' => $auditNumber,
            'audit_date' => $request->audit_date,
            'warehouse_id' => (int) $request->warehouse_id,
            'type' => $request->type,
            'category' => $request->type === 'partial' ? $request->category : null,
            'status' => 'draft',
            'notes' => $notes ?: null,
        ]);

        foreach ($request->lines as $row) {
            $bookQty = (float) $row['book_quantity'];
            $actualQty = isset($row['actual_quantity']) && $row['actual_quantity'] !== '' ? (float) $row['actual_quantity'] : null;
            $unitCost = isset($row['unit_cost']) && $row['unit_cost'] !== '' ? (float) $row['unit_cost'] : 0;
            $diff = $actualQty !== null ? $actualQty - $bookQty : 0;
            $diffValue = $diff * $unitCost;

            InventoryAuditLine::create([
                'inventory_audit_id' => $audit->id,
                'item_id' => (int) $row['item_id'],
                'book_quantity' => $bookQty,
                'actual_quantity' => $actualQty,
                'unit_cost' => $unitCost,
                'difference' => $diff,
                'difference_value' => $diffValue,
            ]);
        }

        return redirect()->route('inventory.audits.show', $audit)->with('success', 'تم حفظ الجرد كمسودة.');
    }

    public function show(InventoryAudit $audit): View
    {
        $audit->load(['warehouse', 'lines.item']);
        $audit->progress = $audit->lines->count() > 0
            ? round($audit->lines->whereNotNull('actual_quantity')->count() / $audit->lines->count() * 100, 1)
            : 0;
        $audit->total_difference_value = $audit->lines->sum('difference_value');

        return view('inventory.audits.show', compact('audit'));
    }

    public function approve(InventoryAudit $audit): RedirectResponse
    {
        if (! $audit->isDraft()) {
            return redirect()->route('inventory.audits.show', $audit)->with('error', 'الجرد معتمد مسبقاً.');
        }

        $warehouseId = $audit->warehouse_id;
        $uid = (int) $audit->user_id;

        foreach ($audit->lines as $line) {
            $diff = (float) $line->difference;
            if ($diff == 0) {
                continue;
            }

            $pivot = ItemWarehouse::firstOrCreate(
                ['user_id' => $uid, 'item_id' => $line->item_id, 'warehouse_id' => $warehouseId],
                ['quantity' => 0, 'reserved_quantity' => 0]
            );

            if ($diff > 0) {
                $pivot->increment('quantity', $diff);
            } else {
                $pivot->decrement('quantity', abs($diff));
            }

            StockMovement::create([
                'user_id' => $uid,
                'warehouse_id' => $warehouseId,
                'item_id' => $line->item_id,
                'quantity' => $diff,
                'movement_type' => 'inventory_audit',
                'reference_type' => InventoryAudit::class,
                'reference_id' => $audit->id,
            ]);
        }

        $audit->update(['status' => 'approved']);

        return redirect()->route('inventory.audits.show', $audit)->with('success', 'تم اعتماد الجرد وتحديث الأرصدة.');
    }
}
