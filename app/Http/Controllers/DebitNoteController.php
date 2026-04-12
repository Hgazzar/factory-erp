<?php

namespace App\Http\Controllers;

use App\Support\DefaultLedgerAccounts;
use App\Models\DebitNote;
use App\Models\JournalEntry;
use App\Models\JournalItem;
use App\Models\PurchaseInvoice;
use App\Models\Supplier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class DebitNoteController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('search', ''));
        $status = (string) $request->query('status', '');

        $debitNotes = DebitNote::query()
            ->with(['supplier'])
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($inner) use ($search) {
                    $inner->where('note_number', 'like', '%' . $search . '%')
                        ->orWhere('original_invoice_ref', 'like', '%' . $search . '%')
                        ->orWhereHas('supplier', function ($supplierQuery) use ($search) {
                            $supplierQuery->where('name', 'like', '%' . $search . '%');
                        });
                });
            })
            ->when(in_array($status, ['draft', 'approved', 'cancelled'], true), fn ($query) => $query->where('status', $status))
            ->orderByDesc('date')
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        return view('finance.debit-notes.index', compact('debitNotes', 'search', 'status'));
    }

    public function create(): View
    {
        $suppliers = Supplier::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'code', 'name']);

        $purchaseInvoices = PurchaseInvoice::query()
            ->with('supplier:id,name')
            ->orderByDesc('date')
            ->limit(100)
            ->get(['id', 'supplier_id', 'reference', 'date', 'total']);

        return view('finance.debit-notes.create', compact('suppliers', 'purchaseInvoices'));
    }

    public function store(Request $request): RedirectResponse
    {
        $uid = (int) auth()->id();
        $data = $request->validate([
            'supplier_id' => ['required', Rule::exists('suppliers', 'id')->where('user_id', $uid)],
            'purchase_invoice_id' => ['nullable', Rule::exists('purchase_invoices', 'id')->where('user_id', $uid)],
            'date' => ['required', 'date'],
            'reference' => ['nullable', 'string', 'max:100'],
            'original_invoice_ref' => ['nullable', 'string', 'max:100'],
            'reason_type' => ['required', 'string', 'max:100'],
            'notes' => ['nullable', 'string', 'max:3000'],
            'status' => ['nullable', 'in:draft,approved,cancelled'],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.description' => ['required', 'string', 'max:255'],
            'lines.*.quantity' => ['required', 'numeric', 'min:0.0001'],
            'lines.*.unit_price' => ['required', 'numeric', 'min:0'],
            'lines.*.tax_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
        ]);

        if (! empty($data['purchase_invoice_id'])) {
            $invoice = PurchaseInvoice::query()->find($data['purchase_invoice_id']);
            if ((int) $invoice->supplier_id !== (int) $data['supplier_id']) {
                return back()
                    ->withInput()
                    ->with('error', 'فاتورة المشتريات المختارة لا تخص المورد المحدد.');
            }
        }

        $lines = $this->prepareLines($data['lines']);
        $subtotal = (float) $lines->sum('line_subtotal');
        $taxAmount = (float) $lines->sum('line_tax');
        $status = $data['status'] ?? 'draft';

        DB::transaction(function () use ($request, $data, $lines, $subtotal, $taxAmount, $status, $uid): void {
            $debitNote = DebitNote::query()->create([
                'user_id' => $uid,
                'supplier_id' => $data['supplier_id'],
                'purchase_invoice_id' => $data['purchase_invoice_id'] ?? null,
                'date' => $data['date'],
                'reference' => $data['reference'] ?? null,
                'original_invoice_ref' => $data['original_invoice_ref'] ?? null,
                'amount' => $subtotal,
                'tax_amount' => $taxAmount,
                'reason_type' => $data['reason_type'],
                'reason' => $data['notes'] ?? null,
                'notes' => $data['notes'] ?? null,
                'status' => $status,
                'created_by' => $request->user()?->id,
            ]);

            $debitNote->items()->createMany($lines->map(fn (array $line) => [
                'description' => $line['description'],
                'quantity' => $line['quantity'],
                'unit_price' => $line['unit_price'],
                'tax_percent' => $line['tax_percent'],
                'line_total' => $line['line_total'],
            ])->all());

            if ($status === 'approved') {
                $this->applyAccountingImpact($debitNote);
            }
        });

        return redirect()
            ->route('finance.debit-notes.index')
            ->with('success', 'تم حفظ إشعار المديونية بنجاح.');
    }

    public function show(DebitNote $debitNote): View
    {
        $debitNote->load(['supplier', 'purchaseInvoice', 'items']);

        return view('finance.debit-notes.show', compact('debitNote'));
    }

    public function edit(DebitNote $debitNote): View
    {
        if ($debitNote->status !== 'draft') {
            return redirect()
                ->route('finance.debit-notes.show', $debitNote)
                ->with('error', 'لا يمكن تعديل إشعار مديونية معتمد أو ملغى.');
        }

        $debitNote->load('items');

        $suppliers = Supplier::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'code', 'name']);

        $purchaseInvoices = PurchaseInvoice::query()
            ->with('supplier:id,name')
            ->orderByDesc('date')
            ->limit(100)
            ->get(['id', 'supplier_id', 'reference', 'date', 'total']);

        return view('finance.debit-notes.edit', compact('debitNote', 'suppliers', 'purchaseInvoices'));
    }

    public function update(Request $request, DebitNote $debitNote): RedirectResponse
    {
        if ($debitNote->status !== 'draft') {
            return redirect()
                ->route('finance.debit-notes.show', $debitNote)
                ->with('error', 'لا يمكن تعديل إشعار مديونية معتمد أو ملغى.');
        }

        $uid = (int) auth()->id();
        $data = $request->validate([
            'supplier_id' => ['required', Rule::exists('suppliers', 'id')->where('user_id', $uid)],
            'purchase_invoice_id' => ['nullable', Rule::exists('purchase_invoices', 'id')->where('user_id', $uid)],
            'date' => ['required', 'date'],
            'reference' => ['nullable', 'string', 'max:100'],
            'original_invoice_ref' => ['nullable', 'string', 'max:100'],
            'reason_type' => ['required', 'string', 'max:100'],
            'notes' => ['nullable', 'string', 'max:3000'],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.description' => ['required', 'string', 'max:255'],
            'lines.*.quantity' => ['required', 'numeric', 'min:0.0001'],
            'lines.*.unit_price' => ['required', 'numeric', 'min:0'],
            'lines.*.tax_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
        ]);

        if (! empty($data['purchase_invoice_id'])) {
            $invoice = PurchaseInvoice::query()->find($data['purchase_invoice_id']);
            if ((int) $invoice->supplier_id !== (int) $data['supplier_id']) {
                return back()
                    ->withInput()
                    ->with('error', 'فاتورة المشتريات المختارة لا تخص المورد المحدد.');
            }
        }

        $lines = $this->prepareLines($data['lines']);
        $subtotal = (float) $lines->sum('line_subtotal');
        $taxAmount = (float) $lines->sum('line_tax');

        DB::transaction(function () use ($debitNote, $data, $lines, $subtotal, $taxAmount): void {
            $debitNote->update([
                'supplier_id' => $data['supplier_id'],
                'purchase_invoice_id' => $data['purchase_invoice_id'] ?? null,
                'date' => $data['date'],
                'reference' => $data['reference'] ?? null,
                'original_invoice_ref' => $data['original_invoice_ref'] ?? null,
                'amount' => $subtotal,
                'tax_amount' => $taxAmount,
                'reason_type' => $data['reason_type'],
                'reason' => $data['notes'] ?? null,
                'notes' => $data['notes'] ?? null,
            ]);

            $debitNote->items()->delete();
            $debitNote->items()->createMany($lines->map(fn (array $line) => [
                'description' => $line['description'],
                'quantity' => $line['quantity'],
                'unit_price' => $line['unit_price'],
                'tax_percent' => $line['tax_percent'],
                'line_total' => $line['line_total'],
            ])->all());
        });

        return redirect()
            ->route('finance.debit-notes.index')
            ->with('success', 'تم تحديث إشعار المديونية بنجاح.');
    }

    public function destroy(DebitNote $debitNote): RedirectResponse
    {
        if ($debitNote->status !== 'draft') {
            return redirect()
                ->route('finance.debit-notes.index')
                ->with('error', 'يمكن حذف إشعار المديونية في حالة المسودة فقط.');
        }

        DB::transaction(function () use ($debitNote): void {
            $debitNote->delete();
        });

        return redirect()
            ->route('finance.debit-notes.index')
            ->with('success', 'تم حذف إشعار المديونية بنجاح.');
    }

    public function approve(DebitNote $debitNote): RedirectResponse
    {
        if ($debitNote->status !== 'draft') {
            return redirect()
                ->route('finance.debit-notes.index')
                ->with('error', 'يمكن اعتماد الإشعار وهو في حالة مسودة فقط.');
        }

        DB::transaction(function () use ($debitNote): void {
            $debitNote->status = 'approved';
            $debitNote->save();

            $this->applyAccountingImpact($debitNote);
        });

        return redirect()
            ->route('finance.debit-notes.index')
            ->with('success', 'تم اعتماد إشعار المديونية وتحديث الأثر المحاسبي بنجاح.');
    }

    public function cancel(DebitNote $debitNote): RedirectResponse
    {
        if ($debitNote->status !== 'approved') {
            return redirect()
                ->route('finance.debit-notes.index')
                ->with('error', 'يمكن إلغاء الإشعار المعتمد فقط.');
        }

        DB::transaction(function () use ($debitNote): void {
            $this->rollbackAccountingImpact($debitNote);
            $debitNote->status = 'cancelled';
            $debitNote->save();
        });

        return redirect()
            ->route('finance.debit-notes.index')
            ->with('success', 'تم إلغاء إشعار المديونية وعكس الأثر المحاسبي بنجاح.');
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

        $entry = JournalEntry::query()->create([
            'user_id' => (int) $debitNote->user_id,
            'date' => $debitNote->date,
            'reference' => $debitNote->note_number,
            'description' => 'إشعار مديونية للمورد #' . $debitNote->supplier_id,
            'total' => $total,
        ]);

        // تقليل مديونية المورد: مدين الموردين
        JournalItem::query()->create([
            'journal_entry_id' => $entry->id,
            'account_id' => $suppliersAccount->id,
            'description' => 'إشعار مديونية ' . $debitNote->note_number,
            'debit' => $total,
            'credit' => 0,
        ]);

        $net = max(0, $total - $vatAmount);
        JournalItem::query()->create([
            'journal_entry_id' => $entry->id,
            'account_id' => $purchaseReturnsAccount->id,
            'description' => 'مردودات المشتريات - إشعار مديونية',
            'debit' => 0,
            'credit' => $net,
        ]);

        if ($vatAmount > 0) {
            JournalItem::query()->create([
                'journal_entry_id' => $entry->id,
                'account_id' => $vatAccount->id,
                'description' => 'ضريبة إشعار مديونية',
                'debit' => 0,
                'credit' => $vatAmount,
            ]);
        }

        $debitNote->journal_entry_id = $entry->id;
        $debitNote->approved_at = now();
        $debitNote->save();
    }

    private function rollbackAccountingImpact(DebitNote $debitNote): void
    {
        if ($debitNote->journal_entry_id) {
            $entry = JournalEntry::query()->find($debitNote->journal_entry_id);
            if ($entry) {
                $entry->items()->delete();
                $entry->delete();
            }
        }

        $debitNote->journal_entry_id = null;
        $debitNote->approved_at = null;
        $debitNote->save();
    }

    private function prepareLines(array $inputLines)
    {
        return collect($inputLines)->map(function (array $line): array {
            $quantity = (float) $line['quantity'];
            $unitPrice = (float) $line['unit_price'];
            $taxPercent = (float) ($line['tax_percent'] ?? 0);
            $lineSubTotal = $quantity * $unitPrice;
            $lineTax = $lineSubTotal * $taxPercent / 100;

            return [
                'description' => $line['description'],
                'quantity' => $quantity,
                'unit_price' => $unitPrice,
                'tax_percent' => $taxPercent,
                'line_subtotal' => round($lineSubTotal, 4),
                'line_tax' => round($lineTax, 4),
                'line_total' => round($lineSubTotal + $lineTax, 4),
            ];
        })->values();
    }
}

