<?php

namespace App\Http\Controllers;

use App\Support\DefaultLedgerAccounts;
use App\Models\CreditNote;
use App\Models\Customer;
use App\Models\JournalEntry;
use App\Models\JournalItem;
use App\Models\SalesInvoice;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CreditNoteController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('search', ''));
        $status = (string) $request->query('status', '');

        $creditNotes = CreditNote::query()
            ->with(['customer'])
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($inner) use ($search) {
                    $inner->where('note_number', 'like', '%' . $search . '%')
                        ->orWhere('original_invoice_ref', 'like', '%' . $search . '%')
                        ->orWhereHas('customer', function ($customerQuery) use ($search) {
                            $customerQuery->where('name', 'like', '%' . $search . '%')
                                ->orWhere('name_ar', 'like', '%' . $search . '%');
                        });
                });
            })
            ->when(in_array($status, ['draft', 'approved', 'cancelled'], true), fn ($query) => $query->where('status', $status))
            ->orderByDesc('date')
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        return view('finance.credit-notes.index', compact('creditNotes', 'search', 'status'));
    }

    public function create(): View
    {
        $customers = Customer::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'code', 'name', 'name_ar']);

        $invoices = SalesInvoice::query()
            ->with('customer:id,name,name_ar')
            ->orderByDesc('date')
            ->limit(100)
            ->get(['id', 'customer_id', 'reference', 'date', 'total', 'paid_amount']);

        return view('finance.credit-notes.create', compact('customers', 'invoices'));
    }

    public function store(Request $request): RedirectResponse
    {
        $uid = (int) auth()->id();
        $data = $request->validate([
            'customer_id' => ['required', Rule::exists('customers', 'id')->where('user_id', $uid)],
            'sales_invoice_id' => ['nullable', Rule::exists('sales_invoices', 'id')->where('user_id', $uid)],
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

        if (! empty($data['sales_invoice_id'])) {
            $invoice = SalesInvoice::query()->find($data['sales_invoice_id']);
            if ((int) $invoice->customer_id !== (int) $data['customer_id']) {
                return back()
                    ->withInput()
                    ->with('error', 'الفاتورة الأصلية لا تخص العميل المحدد.');
            }
        }

        $lines = $this->prepareLines($data['lines']);

        $subtotal = (float) $lines->sum('line_subtotal');
        $taxAmount = (float) $lines->sum('line_tax');
        $status = $data['status'] ?? 'draft';

        DB::transaction(function () use ($request, $data, $lines, $subtotal, $taxAmount, $status, $uid): void {
            $creditNote = CreditNote::query()->create([
                'user_id' => $uid,
                'customer_id' => $data['customer_id'],
                'sales_invoice_id' => $data['sales_invoice_id'] ?? null,
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

            $creditNote->items()->createMany($lines->map(fn (array $line) => [
                'description' => $line['description'],
                'quantity' => $line['quantity'],
                'unit_price' => $line['unit_price'],
                'tax_percent' => $line['tax_percent'],
                'line_total' => $line['line_total'],
            ])->all());

            if ($status === 'approved') {
                $this->applyAccountingImpact($creditNote);
            }
        });

        return redirect()
            ->route('finance.credit-notes.index')
            ->with('success', 'تم حفظ إشعار الائتمان بنجاح.');
    }

    public function show(CreditNote $creditNote): View
    {
        $creditNote->load(['customer', 'salesInvoice']);

        return view('finance.credit-notes.show', compact('creditNote'));
    }

    public function edit(CreditNote $creditNote): View
    {
        if ($creditNote->status !== 'draft') {
            return redirect()
                ->route('finance.credit-notes.show', $creditNote)
                ->with('error', 'لا يمكن تعديل إشعار ائتمان معتمد أو ملغى.');
        }

        $creditNote->load('items');

        $customers = Customer::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'code', 'name', 'name_ar']);

        $invoices = SalesInvoice::query()
            ->with('customer:id,name,name_ar')
            ->orderByDesc('date')
            ->limit(100)
            ->get(['id', 'customer_id', 'reference', 'date', 'total', 'paid_amount']);

        return view('finance.credit-notes.edit', compact('creditNote', 'customers', 'invoices'));
    }

    public function update(Request $request, CreditNote $creditNote): RedirectResponse
    {
        if ($creditNote->status !== 'draft') {
            return redirect()
                ->route('finance.credit-notes.show', $creditNote)
                ->with('error', 'لا يمكن تعديل إشعار ائتمان معتمد أو ملغى.');
        }

        $uid = (int) auth()->id();
        $data = $request->validate([
            'customer_id' => ['required', Rule::exists('customers', 'id')->where('user_id', $uid)],
            'sales_invoice_id' => ['nullable', Rule::exists('sales_invoices', 'id')->where('user_id', $uid)],
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

        if (! empty($data['sales_invoice_id'])) {
            $invoice = SalesInvoice::query()->find($data['sales_invoice_id']);
            if ((int) $invoice->customer_id !== (int) $data['customer_id']) {
                return back()
                    ->withInput()
                    ->with('error', 'الفاتورة الأصلية لا تخص العميل المحدد.');
            }
        }

        $lines = $this->prepareLines($data['lines']);
        $subtotal = (float) $lines->sum('line_subtotal');
        $taxAmount = (float) $lines->sum('line_tax');

        DB::transaction(function () use ($creditNote, $data, $lines, $subtotal, $taxAmount): void {
            $creditNote->update([
                'customer_id' => $data['customer_id'],
                'sales_invoice_id' => $data['sales_invoice_id'] ?? null,
                'date' => $data['date'],
                'reference' => $data['reference'] ?? null,
                'original_invoice_ref' => $data['original_invoice_ref'] ?? null,
                'amount' => $subtotal,
                'tax_amount' => $taxAmount,
                'reason_type' => $data['reason_type'],
                'reason' => $data['notes'] ?? null,
                'notes' => $data['notes'] ?? null,
            ]);

            $creditNote->items()->delete();
            $creditNote->items()->createMany($lines->map(fn (array $line) => [
                'description' => $line['description'],
                'quantity' => $line['quantity'],
                'unit_price' => $line['unit_price'],
                'tax_percent' => $line['tax_percent'],
                'line_total' => $line['line_total'],
            ])->all());
        });

        return redirect()
            ->route('finance.credit-notes.index')
            ->with('success', 'تم تحديث إشعار الائتمان بنجاح.');
    }

    public function destroy(CreditNote $creditNote): RedirectResponse
    {
        if ($creditNote->status !== 'draft') {
            return redirect()
                ->route('finance.credit-notes.index')
                ->with('error', 'يمكن حذف إشعار الائتمان في حالة المسودة فقط.');
        }

        DB::transaction(function () use ($creditNote): void {
            $creditNote->delete();
        });

        return redirect()
            ->route('finance.credit-notes.index')
            ->with('success', 'تم حذف إشعار الائتمان بنجاح.');
    }

    public function approve(CreditNote $creditNote): RedirectResponse
    {
        if ($creditNote->status !== 'draft') {
            return redirect()
                ->route('finance.credit-notes.index')
                ->with('error', 'يمكن اعتماد الإشعار وهو في حالة مسودة فقط.');
        }

        DB::transaction(function () use ($creditNote): void {
            $creditNote->status = 'approved';
            $creditNote->save();

            $this->applyAccountingImpact($creditNote);
        });

        return redirect()
            ->route('finance.credit-notes.index')
            ->with('success', 'تم اعتماد إشعار الائتمان وتحديث الأثر المحاسبي بنجاح.');
    }

    public function cancel(CreditNote $creditNote): RedirectResponse
    {
        if ($creditNote->status !== 'approved') {
            return redirect()
                ->route('finance.credit-notes.index')
                ->with('error', 'يمكن إلغاء الإشعار المعتمد فقط.');
        }

        DB::transaction(function () use ($creditNote): void {
            $this->rollbackAccountingImpact($creditNote);
            $creditNote->status = 'cancelled';
            $creditNote->save();
        });

        return redirect()
            ->route('finance.credit-notes.index')
            ->with('success', 'تم إلغاء إشعار الائتمان وعكس الأثر المحاسبي بنجاح.');
    }

    private function applyAccountingImpact(CreditNote $creditNote): void
    {
        if ($creditNote->journal_entry_id) {
            return;
        }

        $total = (float) $creditNote->amount + (float) $creditNote->tax_amount;
        $vatAmount = (float) $creditNote->tax_amount;

        $customersAccount = DefaultLedgerAccounts::accountsReceivable();
        $salesReturnsAccount = DefaultLedgerAccounts::salesReturns();
        $vatAccount = DefaultLedgerAccounts::vatPayable();

        $entry = JournalEntry::query()->create([
            'user_id' => (int) $creditNote->user_id,
            'date' => $creditNote->date,
            'reference' => $creditNote->note_number,
            'description' => 'إشعار ائتمان للعميل #' . $creditNote->customer_id,
            'total' => $total,
        ]);

        // تقليل مديونية العميل (رصيد العملاء الدائن)
        JournalItem::query()->create([
            'journal_entry_id' => $entry->id,
            'account_id' => $customersAccount->id,
            'description' => 'إشعار ائتمان ' . $creditNote->note_number,
            'debit' => 0,
            'credit' => $total,
        ]);

        $net = max(0, $total - $vatAmount);
        JournalItem::query()->create([
            'journal_entry_id' => $entry->id,
            'account_id' => $salesReturnsAccount->id,
            'description' => 'تخفيض مبيعات - إشعار ائتمان',
            'debit' => $net,
            'credit' => 0,
        ]);

        if ($vatAmount > 0) {
            JournalItem::query()->create([
                'journal_entry_id' => $entry->id,
                'account_id' => $vatAccount->id,
                'description' => 'ضريبة إشعار ائتمان',
                'debit' => $vatAmount,
                'credit' => 0,
            ]);
        }

        if ($creditNote->sales_invoice_id) {
            $invoice = SalesInvoice::query()->find($creditNote->sales_invoice_id);
            if ($invoice) {
                $invoice->paid_amount = min(
                    (float) $invoice->total,
                    (float) $invoice->paid_amount + $total
                );
                $invoice->save();
            }
        }

        $creditNote->journal_entry_id = $entry->id;
        $creditNote->approved_at = now();
        $creditNote->save();
    }

    private function rollbackAccountingImpact(CreditNote $creditNote): void
    {
        $total = (float) $creditNote->amount + (float) $creditNote->tax_amount;

        if ($creditNote->sales_invoice_id) {
            $invoice = SalesInvoice::query()->find($creditNote->sales_invoice_id);
            if ($invoice) {
                $invoice->paid_amount = max(0, (float) $invoice->paid_amount - $total);
                $invoice->save();
            }
        }

        if ($creditNote->journal_entry_id) {
            $entry = JournalEntry::query()->find($creditNote->journal_entry_id);
            if ($entry) {
                $entry->items()->delete();
                $entry->delete();
            }
        }

        $creditNote->journal_entry_id = null;
        $creditNote->approved_at = null;
        $creditNote->save();
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

