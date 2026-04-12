<?php

namespace App\Http\Controllers;

use App\Models\InventoryTransaction;
use App\Models\Item;
use App\Models\ItemWarehouse;
use App\Models\JournalEntry;
use App\Models\JournalItem;
use App\Models\StockIn;
use App\Models\StockInLine;
use App\Models\Supplier;
use App\Models\Warehouse;
use App\Support\DefaultLedgerAccounts;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class StockInController extends Controller
{
    public function create(): View
    {
        $suppliers = Supplier::where('is_active', true)->orderBy('name')->get();
        $warehouses = Warehouse::active()->orderBy('name_ar')->get();
        $items = Item::active()->orderBy('code')->get(['id', 'code', 'name_ar', 'name_en']);

        return view('inventory.stock_in.create', compact('suppliers', 'warehouses', 'items'));
    }

    public function store(Request $request): RedirectResponse
    {
        $uid = (int) auth()->id();
        $data = $request->validate([
            'supplier_id' => ['required', Rule::exists('suppliers', 'id')->where('user_id', $uid)],
            'settlement_type' => ['required', 'in:on_account,cash'],
            'reference' => ['nullable', 'string', 'max:100'],
            'date' => ['required', 'date'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.item_id' => ['required', Rule::exists('items', 'id')->where('user_id', $uid)],
            'lines.*.warehouse_id' => ['required', Rule::exists('warehouses', 'id')->where('user_id', $uid)],
            'lines.*.quantity' => ['required', 'numeric', 'min:0.0001'],
            'lines.*.purchase_price' => ['required', 'numeric', 'min:0'],
        ], [
            'supplier_id.required' => 'المورد مطلوب.',
            'lines.required' => 'يجب إضافة بند واحد على الأقل.',
        ]);

        $lines = collect($data['lines'])
            ->map(fn ($line) => [
                'item_id' => (int) $line['item_id'],
                'warehouse_id' => (int) $line['warehouse_id'],
                'quantity' => (float) $line['quantity'],
                'purchase_price' => (float) $line['purchase_price'],
            ])
            ->filter(fn ($l) => $l['quantity'] > 0)
            ->values();

        if ($lines->isEmpty()) {
            return back()->withInput()->with('error', 'يجب إضافة على الأقل بنداً صالحاً.');
        }

        $stockIn = null;

        DB::transaction(function () use ($data, $lines, &$stockIn, $uid) {
            $stockIn = StockIn::create([
                'user_id' => $uid,
                'document_number' => null,
                'supplier_id' => $data['supplier_id'],
                'settlement_type' => $data['settlement_type'],
                'reference' => $data['reference'] ?? null,
                'date' => $data['date'],
                'notes' => $data['notes'] ?? null,
            ]);

            $stockIn->update([
                'document_number' => 'STIN-'.str_pad((string) $stockIn->id, 6, '0', STR_PAD_LEFT),
            ]);
            $stockIn->refresh();

            $touchedItemIds = collect();

            foreach ($lines as $line) {
                $stockLine = StockInLine::create([
                    'stock_in_id' => $stockIn->id,
                    'item_id' => $line['item_id'],
                    'warehouse_id' => $line['warehouse_id'],
                    'quantity' => $line['quantity'],
                    'purchase_price' => $line['purchase_price'],
                ]);

                InventoryTransaction::create([
                    'user_id' => $uid,
                    'item_id' => $line['item_id'],
                    'warehouse_id' => $line['warehouse_id'],
                    'quantity' => $line['quantity'],
                    'type' => 'stock_in',
                    'reference_id' => $stockLine->id,
                    'reference_type' => 'stock_in_lines',
                    'notes' => 'إذن إضافة مخزني '.$stockIn->document_number,
                ]);

                $pivot = ItemWarehouse::firstOrCreate(
                    [
                        'user_id' => $uid,
                        'item_id' => $line['item_id'],
                        'warehouse_id' => $line['warehouse_id'],
                    ],
                    [
                        'quantity' => 0,
                        'reserved_quantity' => 0,
                    ]
                );

                $oldQty = (float) $pivot->quantity;
                $qty = $line['quantity'];
                $unitCost = $line['purchase_price'];

                $item = Item::find($line['item_id']);
                $oldCost = (float) ($item->cost ?? 0);

                $pivot->quantity = $oldQty + $qty;
                $pivot->save();

                if ($oldQty + $qty > 0 && $unitCost >= 0) {
                    $newAvgCost = ($oldQty * $oldCost + $qty * $unitCost) / ($oldQty + $qty);
                    Item::where('id', $line['item_id'])->update(['cost' => round($newAvgCost, 4)]);
                }

                $touchedItemIds->push($line['item_id']);
            }

            foreach ($touchedItemIds->unique() as $itemId) {
                $sum = ItemWarehouse::where('item_id', $itemId)
                    ->where('user_id', $uid)
                    ->sum(DB::raw('quantity - reserved_quantity'));
                Item::where('id', $itemId)->update(['current_stock' => $sum]);
            }

            $grandTotal = round(
                (float) $lines->sum(fn ($line) => $line['quantity'] * $line['purchase_price']),
                4
            );

            if ($grandTotal > 0) {
                $inventoryAccount = DefaultLedgerAccounts::inventoryReceipts();
                $creditAccount = $data['settlement_type'] === 'cash'
                    ? DefaultLedgerAccounts::cashOnHand()
                    : DefaultLedgerAccounts::accountsPayable();

                $supplier = Supplier::find($data['supplier_id']);
                $supplierLabel = $supplier?->getLocalizedDisplayName() ?? (string) $data['supplier_id'];

                $entry = JournalEntry::create([
                    'user_id' => $uid,
                    'date' => $data['date'],
                    'reference' => $stockIn->document_number,
                    'description' => 'إذن إضافة مخزني — مورد: '.$supplierLabel,
                    'total' => $grandTotal,
                ]);

                JournalItem::create([
                    'journal_entry_id' => $entry->id,
                    'account_id' => $inventoryAccount->id,
                    'description' => 'زيادة مخزون (توريد)',
                    'debit' => $grandTotal,
                    'credit' => 0,
                ]);

                JournalItem::create([
                    'journal_entry_id' => $entry->id,
                    'account_id' => $creditAccount->id,
                    'description' => $data['settlement_type'] === 'cash'
                        ? 'صرف نقدي لشراء مخزون'
                        : 'ذمة مورد — توريد مخزون',
                    'debit' => 0,
                    'credit' => $grandTotal,
                ]);
            }
        });

        return redirect()
            ->route('inventory.stock-in.show', $stockIn)
            ->with('success', 'تم حفظ إذن الإضافة المخزني.')
            ->with('open_print', true);
    }

    public function show(Request $request, StockIn $stockIn): View
    {
        $stockIn->load(['supplier', 'lines.item', 'lines.warehouse']);

        $stockIn->line_value_total = $stockIn->lines->sum(
            fn ($l) => (float) $l->quantity * (float) $l->purchase_price
        );

        return view('inventory.stock_in.show', [
            'stockIn' => $stockIn,
            'autoPrint' => $request->boolean('print') || (bool) session()->pull('open_print'),
        ]);
    }
}
