<?php

namespace App\Http\Controllers;

use App\Models\Item;
use App\Models\PurchaseOrder;
use App\Models\ReceiveNote;
use App\Models\Supplier;
use App\Models\Warehouse;
use App\Services\ExcelImportService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ReceiveNoteWebController extends Controller
{
    public function index(Request $request): View|Response
    {
        $query = ReceiveNote::query()
            ->with(['supplier', 'purchaseOrder', 'warehouse'])
            ->orderByDesc('receive_date')
            ->orderByDesc('id');

        if ($request->filled('q')) {
            $q = $request->q;
            $query->where(function ($qb) use ($q) {
                $qb->where('code', 'like', "%{$q}%")
                    ->orWhereHas('supplier', fn ($s) => $s->where('name', 'like', "%{$q}%")->orWhere('code', 'like', "%{$q}%"))
                    ->orWhereHas('warehouse', fn ($w) => $w->where('name_ar', 'like', "%{$q}%")->orWhere('code', 'like', "%{$q}%"));
            });
        }

        if ($request->get('export') === 'csv') {
            $rows = $query->limit(5000)->get();
            $csv = "\xEF\xBB\xBF";
            $csv .= "رقم السند,المورد,أمر الشراء,المستودع,تاريخ الاستلام,الحالة\n";
            foreach ($rows as $rn) {
                $csv .= '"' . str_replace('"', '""', $rn->code ?? '') . '","' . str_replace('"', '""', $rn->supplier?->name ?? '') . '","' . str_replace('"', '""', $rn->purchaseOrder?->code ?? '') . '","' . str_replace('"', '""', $rn->warehouse?->name_ar ?? $rn->warehouse?->code ?? '') . '","' . ($rn->receive_date?->format('Y-m-d') ?? '') . '","' . ($rn->status ?? '') . "\n";
            }
            return response($csv, 200, [
                'Content-Type' => 'text/csv; charset=UTF-8',
                'Content-Disposition' => 'attachment; filename="receive-notes-' . date('Y-m-d') . '.csv"',
            ]);
        }

        $receiveNotes = $query->paginate(15)->withQueryString();

        return view('purchases.receive_notes.index', compact('receiveNotes'));
    }

    public function create(): View
    {
        $suppliers = Supplier::orderBy('name')->get();
        $purchaseOrders = PurchaseOrder::with('supplier')->orderByDesc('order_date')->get();
        $warehouses = Warehouse::where('is_active', true)->orderBy('name_ar')->get();
        $items = Item::where('is_active', true)->with('unit')->orderBy('name_ar')->get();

        return view('purchases.receive_notes.create', compact('suppliers', 'purchaseOrders', 'warehouses', 'items'));
    }

    public function store(Request $request): RedirectResponse
    {
        $uid = (int) auth()->id();
        $data = $request->validate([
            'supplier_id' => ['required', Rule::exists('suppliers', 'id')->where('user_id', $uid)],
            'purchase_order_id' => ['nullable', Rule::exists('purchase_orders', 'id')->where('user_id', $uid)],
            'warehouse_id' => ['required', Rule::exists('warehouses', 'id')->where('user_id', $uid)],
            'receive_date' => ['required', 'date'],
            'reference' => ['nullable', 'string', 'max:100'],
            'supplier_delivery_notice' => ['nullable', 'string', 'max:255'],
            'requires_inspection' => ['nullable', 'boolean'],
            'status' => ['nullable', 'string', 'in:completed,pending,draft'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'internal_notes' => ['nullable', 'string', 'max:2000'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.item_id' => ['nullable', Rule::exists('items', 'id')->where('user_id', $uid)],
            'items.*.description' => ['nullable', 'string', 'max:500'],
            'items.*.quantity_required' => ['nullable', 'numeric', 'min:0'],
            'items.*.quantity' => ['required', 'numeric', 'min:0'],
            'items.*.quantity_accepted' => ['nullable', 'numeric', 'min:0'],
            'items.*.quantity_rejected' => ['nullable', 'numeric', 'min:0'],
            'items.*.unit' => ['nullable', 'string', 'max:50'],
            'items.*.unit_cost' => ['nullable', 'numeric', 'min:0'],
            'items.*.line_cost' => ['nullable', 'numeric', 'min:0'],
        ], [
            'supplier_id.required' => 'المورد مطلوب.',
            'warehouse_id.required' => 'المستودع مطلوب.',
            'receive_date.required' => 'تاريخ الاستلام مطلوب.',
            'items.required' => 'يجب إضافة صنف واحد على الأقل.',
        ]);

        $code = 'RN-' . str_pad((string) ((int) ReceiveNote::max('id') + 1), 4, '0', STR_PAD_LEFT);

        $receiveNote = ReceiveNote::create([
            'user_id' => $uid,
            'code' => $code,
            'supplier_id' => $data['supplier_id'],
            'purchase_order_id' => $data['purchase_order_id'] ?: null,
            'warehouse_id' => $data['warehouse_id'],
            'receive_date' => $data['receive_date'],
            'reference' => $data['reference'] ?? null,
            'supplier_delivery_notice' => $data['supplier_delivery_notice'] ?? null,
            'requires_inspection' => $request->boolean('requires_inspection'),
            'status' => $data['status'] ?? ReceiveNote::STATUS_COMPLETED,
            'notes' => $data['notes'] ?? null,
            'internal_notes' => $data['internal_notes'] ?? null,
        ]);

        foreach ($data['items'] as $row) {
            if (! isset($row['quantity']) || (float) ($row['quantity'] ?? 0) < 0) {
                continue;
            }
            $qty = (float) ($row['quantity'] ?? 0);
            $unitCost = (float) ($row['unit_cost'] ?? 0);
            $lineCost = isset($row['line_cost']) && $row['line_cost'] !== '' ? (float) $row['line_cost'] : ($qty * $unitCost);
            $receiveNote->items()->create([
                'item_id' => $row['item_id'] ?: null,
                'description' => $row['description'] ?? null,
                'quantity_required' => (float) ($row['quantity_required'] ?? 0),
                'quantity' => $qty,
                'quantity_accepted' => (float) ($row['quantity_accepted'] ?? 0),
                'quantity_rejected' => (float) ($row['quantity_rejected'] ?? 0),
                'unit' => $row['unit'] ?? null,
                'unit_cost' => $unitCost,
                'line_cost' => $lineCost,
            ]);
        }

        return redirect()
            ->route('purchases.receive-notes.index')
            ->with('success', 'تم تسجيل سند الاستلام بنجاح.');
    }

    public function importTemplate(): Response
    {
        $csv = "\xEF\xBB\xBF";
        $csv .= implode(',', [
            'code',
            'supplier_code',
            'warehouse_code',
            'receive_date',
            'status',
            'notes',
        ]) . "\n";

        return response($csv, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename=\"receive-notes-import-template.csv\"',
        ]);
    }

    public function import(Request $request, ExcelImportService $importService): RedirectResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:csv,txt,xlsx,xls', 'max:10240'],
        ]);

        try {
            $summary = DB::transaction(function () use ($request, $importService) {
                return $importService->importSimple(
                    $request->file('file'),
                    ['supplier_code', 'warehouse_code', 'receive_date'],
                    function (array $row, int $line) {
                        $supplierCode = $row['supplier_code'] ?? null;
                        $warehouseCode = $row['warehouse_code'] ?? null;
                        $receiveDate = $row['receive_date'] ?? null;

                        if (! $supplierCode || ! $warehouseCode || ! $receiveDate) {
                            throw new \RuntimeException("بعض الحقول الرئيسية مفقودة في السطر رقم {$line} (supplier_code, warehouse_code, receive_date).");
                        }

                        $supplier = Supplier::where('code', $supplierCode)->first();
                        if (! $supplier) {
                            throw new \RuntimeException("تعذر العثور على المورد بالكود {$supplierCode} في السطر رقم {$line}.");
                        }

                        $warehouse = Warehouse::where('code', $warehouseCode)->first();
                        if (! $warehouse) {
                            throw new \RuntimeException("تعذر العثور على المستودع بالكود {$warehouseCode} في السطر رقم {$line}.");
                        }

                        $code = $row['code'] ?? null;
                        $match = [];
                        if ($code) {
                            $match['code'] = $code;
                        }

                        $data = [
                            'user_id' => (int) ($supplier->user_id ?? auth()->id() ?? 1),
                            'supplier_id' => $supplier->id,
                            'warehouse_id' => $warehouse->id,
                            'receive_date' => $receiveDate,
                            'status' => $row['status'] ?: ReceiveNote::STATUS_COMPLETED,
                            'notes' => $row['notes'] ?: null,
                        ];

                        $existing = null;
                        if (! empty($match)) {
                            $existing = ReceiveNote::where($match)->first();
                        }

                        if ($existing) {
                            $existing->update($data);

                            return 'updated';
                        }

                        if (! $code) {
                            $nextId = (int) ReceiveNote::max('id') + 1;
                            $code = 'RN-' . str_pad((string) $nextId, 4, '0', STR_PAD_LEFT);
                        }

                        $data['code'] = $code;

                        ReceiveNote::create($data);

                        return 'created';
                    }
                );
            });
        } catch (\Throwable $e) {
            return back()
                ->withInput()
                ->with('error', $e->getMessage());
        }

        return redirect()
            ->route('purchases.receive-notes.index')
            ->with('success', "تم استيراد سندات الاستلام بنجاح. تمت إضافة {$summary['created']} وتحديث {$summary['updated']}.");
    }
}
