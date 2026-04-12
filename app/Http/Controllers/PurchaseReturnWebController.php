<?php

namespace App\Http\Controllers;

use App\Models\DebitNote;
use App\Models\Item;
use App\Models\ItemWarehouse;
use App\Models\JournalEntry;
use App\Models\JournalItem;
use App\Models\PurchaseInvoice;
use App\Models\PurchaseReturn;
use App\Models\PurchaseReturnItem;
use App\Models\Supplier;
use App\Models\Warehouse;
use App\Support\DefaultLedgerAccounts;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class PurchaseReturnWebController extends Controller
{
    public function index(Request $request): View|Response
    {
        $query = PurchaseReturn::with(['supplier', 'purchaseInvoice', 'warehouse'])
            ->withCount('items')
            ->orderByDesc('date')
            ->orderByDesc('id');

        if ($request->filled('status') && $request->status !== '') {
            $query->where('status', $request->status);
        }
        if ($request->filled('q')) {
            $q = $request->q;
            $query->where(function ($qry) use ($q) {
                $qry->where('code', 'like', "%{$q}%")
                    ->orWhereHas('supplier', fn ($s) => $s->where('name', 'like', "%{$q}%")->orWhere('code', 'like', "%{$q}%"));
            });
        }

        if ($request->get('export') === 'csv') {
            $rows = (clone $query)->limit(5000)->get();
            $csv = "\xEF\xBB\xBF";
            $csv .= "رقم المرتجع,المورد,التاريخ,السبب,عدد الأصناف,الإجمالي,الحالة\n";
            foreach ($rows as $r) {
                $csv .= '"'.str_replace('"', '""', $r->code ?? '').'","'.str_replace('"', '""', $r->supplier?->name ?? '').'","'.($r->date?->format('Y-m-d') ?? '').'","'.str_replace('"', '""', $r->reason ?? '').'",'.($r->items_count ?? 0).','.(float) $r->total.',"'.($r->status ?? '')."\n";
            }

            return response($csv, 200, [
                'Content-Type' => 'text/csv; charset=UTF-8',
                'Content-Disposition' => 'attachment; filename="purchase-returns-'.date('Y-m-d').'.csv"',
            ]);
        }

        $returns = $query->paginate(20)->withQueryString();

        $totalReturnedAmount = (float) PurchaseReturn::sum('total');
        $totalCount = PurchaseReturn::count();
        $pendingCount = PurchaseReturn::where('status', 'pending')->count();
        $shippedCount = PurchaseReturn::where('status', 'shipped')->count();

        $reasonTypes = ['تالف', 'خطأ في الشحن', 'عدم المطابقة', 'آخر'];

        return view('purchases.returns.index', compact(
            'returns',
            'totalReturnedAmount',
            'totalCount',
            'pendingCount',
            'shippedCount',
            'reasonTypes'
        ));
    }

    public function create(): View
    {
        $suppliers = Supplier::where('is_active', true)->orderBy('name')->get();
        $warehouses = Warehouse::active()->orderBy('name_ar')->get();
        $items = Item::where('is_active', true)->orderBy('code')->get();
        $returnTypes = ['معيب', 'غير مطابق', 'تالف', 'خطأ في الشحن', 'آخر'];
        $lineStatuses = ['معيب', 'سليم', 'غير مطابق', 'تالف', 'أخرى'];

        return view('purchases.returns.create', compact('suppliers', 'warehouses', 'items', 'returnTypes', 'lineStatuses'));
    }

    public function invoicesBySupplier(Request $request): JsonResponse
    {
        $supplierId = $request->get('supplier_id');
        if (! $supplierId) {
            return response()->json(['invoices' => []]);
        }

        $invoices = PurchaseInvoice::where('supplier_id', $supplierId)
            ->orderByDesc('date')
            ->get(['id', 'date', 'reference', 'total'])
            ->map(fn ($inv) => [
                'id' => $inv->id,
                'label' => ($inv->reference ?: 'PINV-'.$inv->id).' ('.$inv->date->format('Y-m-d').') - SAR '.number_format((float) $inv->total, 2),
                'date' => $inv->date->format('Y-m-d'),
                'total' => (float) $inv->total,
            ]);

        return response()->json(['invoices' => $invoices]);
    }

    public function invoiceItems(PurchaseInvoice $invoice): JsonResponse
    {
        $invoice->load(['items.item']);

        $items = $invoice->items->map(function ($line) use ($invoice) {
            $returnedQty = PurchaseReturnItem::whereHas('purchaseReturn', fn ($q) => $q->where('purchase_invoice_id', $invoice->id))
                ->where('item_id', $line->item_id)
                ->sum('quantity');
            $maxReturnable = max(0, (float) $line->quantity - (float) $returnedQty);

            return [
                'item_id' => $line->item_id,
                'item_name' => $line->item->name_ar ?? $line->item->name_en ?? $line->item->code ?? '-',
                'invoice_quantity' => (float) $line->quantity,
                'returned_quantity' => (float) $returnedQty,
                'max_returnable' => $maxReturnable,
                'unit_price' => (float) $line->unit_price,
                'vat_percent' => (float) ($line->vat_percent ?? $invoice->vat_rate ?? 0),
            ];
        })->filter(fn ($r) => $r['max_returnable'] > 0)->values();

        return response()->json([
            'items' => $items,
            'warehouse_id' => $invoice->warehouse_id,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $uid = (int) auth()->id();
        $data = $request->validate([
            'supplier_id' => ['required', Rule::exists('suppliers', 'id')->where('user_id', $uid)],
            'warehouse_id' => ['required', Rule::exists('warehouses', 'id')->where('user_id', $uid)],
            'date' => ['required', 'date'],
            'reason_type' => ['required', 'string', 'max:100'],
            'reason' => ['nullable', 'string', 'max:2000'],
            'reference' => ['nullable', 'string', 'max:100'],
            'currency' => ['nullable', 'string', 'max:5'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'internal_notes' => ['nullable', 'string', 'max:2000'],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.item_id' => ['required', Rule::exists('items', 'id')->where('user_id', $uid)],
            'lines.*.quantity' => ['required', 'numeric', 'min:0.0001'],
            'lines.*.unit_price' => ['required', 'numeric', 'min:0'],
            'lines.*.vat_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'lines.*.line_status' => ['nullable', 'string', 'max:50'],
        ], [
            'supplier_id.required' => 'المورد مطلوب.',
            'warehouse_id.required' => 'المستودع مطلوب.',
            'date.required' => 'التاريخ مطلوب.',
            'lines.required' => 'يجب إضافة بند واحد على الأقل.',
        ]);

        $warehouseId = $data['warehouse_id'];

        $lines = collect($data['lines'])->map(function ($line) {
            $qty = (float) $line['quantity'];
            $price = (float) $line['unit_price'];
            $vatPct = (float) ($line['vat_percent'] ?? 0);
            $lineNet = $qty * $price;
            $lineVat = $lineNet * $vatPct / 100;
            $lineTotal = round($lineNet + $lineVat, 4);

            return [
                'item_id' => (int) $line['item_id'],
                'quantity' => $qty,
                'unit_price' => $price,
                'vat_percent' => $vatPct,
                'line_status' => $line['line_status'] ?? null,
                'line_total' => $lineTotal,
            ];
        })->filter(fn ($l) => $l['quantity'] > 0)->values();

        if ($lines->isEmpty()) {
            return back()->withInput()->with('error', 'يجب إضافة بند واحد على الأقل بكمية صحيحة.');
        }

        $subtotal = $lines->sum(fn ($l) => $l['quantity'] * $l['unit_price']);
        $vatAmount = $lines->sum('line_total') - $subtotal;
        $total = $subtotal + $vatAmount;

        DB::transaction(function () use ($request, $data, $lines, $total, $vatAmount, $subtotal, $warehouseId, $uid) {
            $purchaseReturn = PurchaseReturn::create([
                'user_id' => $uid,
                'code' => null,
                'date' => $data['date'],
                'supplier_id' => $data['supplier_id'],
                'purchase_invoice_id' => null,
                'warehouse_id' => $warehouseId,
                'reason_type' => $data['reason_type'],
                'reason' => $data['reason'] ?? null,
                'reference' => $data['reference'] ?? null,
                'notes' => $data['notes'] ?? null,
                'internal_notes' => $data['internal_notes'] ?? null,
                'total' => $total,
                'vat_amount' => $vatAmount,
                'currency' => $data['currency'] ?? 'SAR',
                'status' => 'completed',
            ]);

            $purchaseReturn->update(['code' => 'PR-'.$purchaseReturn->id]);
            $code = $purchaseReturn->code;

            $items = Item::whereIn('id', $lines->pluck('item_id'))->get();
            $itemNames = $items->mapWithKeys(fn ($i) => [$i->id => $i->name_ar ?? $i->name_en ?? $i->code ?? 'صنف #'.$i->id])->toArray();

            foreach ($lines as $line) {
                $purchaseReturn->items()->create($line);

                $pivot = ItemWarehouse::firstOrCreate(
                    ['user_id' => $uid, 'item_id' => $line['item_id'], 'warehouse_id' => $warehouseId],
                    ['quantity' => 0, 'reserved_quantity' => 0]
                );
                $pivot->quantity = max(0, $pivot->quantity - $line['quantity']);
                $pivot->save();
            }

            $debitNote = DebitNote::create([
                'user_id' => $uid,
                'supplier_id' => $data['supplier_id'],
                'purchase_invoice_id' => null,
                'date' => $data['date'],
                'reference' => $code,
                'original_invoice_ref' => $data['reference'] ?? null,
                'amount' => $subtotal,
                'tax_amount' => $vatAmount,
                'reason_type' => $data['reason_type'],
                'reason' => $data['reason'] ?? null,
                'notes' => $data['notes'] ?? null,
                'status' => 'approved',
                'created_by' => $request->user()?->id,
            ]);

            $debitNote->items()->createMany($lines->map(fn ($line) => [
                'description' => $itemNames[$line['item_id']] ?? 'صنف #'.$line['item_id'],
                'quantity' => $line['quantity'],
                'unit_price' => $line['unit_price'],
                'tax_percent' => $line['vat_percent'],
                'line_total' => $line['line_total'],
            ])->all());

            $this->applyAccountingImpact($debitNote);
            $debitNote->approved_at = now();
            $debitNote->save();

            $purchaseReturn->debit_note_id = $debitNote->id;
            $purchaseReturn->save();
        });

        return redirect()
            ->route('purchases.returns.index')
            ->with('success', 'تم إنشاء مرتجع المشتريات وإصدار إشعار الدائن وتحديث المخزون بنجاح.');
    }

    private function applyAccountingImpact(DebitNote $debitNote): void
    {
        if ($debitNote->journal_entry_id) {
            return;
        }

        $total = (float) $debitNote->amount + (float) $debitNote->tax_amount;
        $vatAmount = (float) $debitNote->tax_amount;

        $suppliersAccount = DefaultLedgerAccounts::accountsPayable();
        $purchaseReturnsAccount = DefaultLedgerAccounts::purchaseReturns();
        $vatAccount = DefaultLedgerAccounts::vatPayable();

        $entry = JournalEntry::create([
            'user_id' => (int) $debitNote->user_id,
            'date' => $debitNote->date,
            'reference' => $debitNote->note_number,
            'description' => 'إشعار دائن (مرتجع مشتريات) للمورد #'.$debitNote->supplier_id,
            'total' => $total,
        ]);

        JournalItem::create([
            'journal_entry_id' => $entry->id,
            'account_id' => $suppliersAccount->id,
            'description' => 'إشعار دائن '.$debitNote->note_number,
            'debit' => $total,
            'credit' => 0,
        ]);

        $net = max(0, $total - $vatAmount);
        JournalItem::create([
            'journal_entry_id' => $entry->id,
            'account_id' => $purchaseReturnsAccount->id,
            'description' => 'مردودات المشتريات - إشعار دائن',
            'debit' => 0,
            'credit' => $net,
        ]);

        if ($vatAmount > 0) {
            JournalItem::create([
                'journal_entry_id' => $entry->id,
                'account_id' => $vatAccount->id,
                'description' => 'ضريبة إشعار دائن',
                'debit' => 0,
                'credit' => $vatAmount,
            ]);
        }

        $debitNote->journal_entry_id = $entry->id;
        $debitNote->save();
    }
}
