<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\AuditLog;
use App\Models\AuditTrail;
use App\Models\JournalEntry;
use App\Models\JournalItem;
use App\Models\Payment;
use App\Models\PurchaseInvoice;
use App\Models\Supplier;
use App\Support\DefaultLedgerAccounts;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use RuntimeException;

class PaymentWebController extends Controller
{
    public function index(): View
    {
        $payments = Payment::with(['supplier', 'expenseAccount', 'creator', 'purchaseInvoices'])
            ->orderByDesc('date')
            ->orderByDesc('id')
            ->paginate(20);

        return view('finance.payments.index', compact('payments'));
    }

    public function create(): View
    {
        $suppliers = Supplier::where('is_active', true)->orderBy('name')->get();
        $expenseAccounts = Account::where('type', Account::TYPE_EXPENSE)->orderBy('code')->get();

        return view('finance.payments.create', compact('suppliers', 'expenseAccounts'));
    }

    /**
     * فواتير المشتريات غير المسددة بالكامل لمورد محدد (للقائمة المنسدلة في سند الصرف).
     */
    public function supplierPurchaseInvoices(Request $request): JsonResponse
    {
        $uid = (int) auth()->id();
        $request->validate([
            'supplier_id' => ['required', Rule::exists('suppliers', 'id')->where('user_id', $uid)],
        ]);

        $rows = PurchaseInvoice::query()
            ->where('supplier_id', $request->integer('supplier_id'))
            ->whereRaw('(total - COALESCE(paid_amount, 0)) > 0.0001')
            ->orderByDesc('date')
            ->orderByDesc('id')
            ->get(['id', 'reference', 'date', 'total', 'paid_amount']);

        return response()->json([
            'invoices' => $rows->map(function (PurchaseInvoice $inv) {
                $balance = max(0, (float) $inv->total - (float) ($inv->paid_amount ?? 0));

                return [
                    'id' => $inv->id,
                    'label' => ($inv->reference ?: '#'.$inv->id).' — '.($inv->date?->format('Y-m-d') ?? '').' — متبقي: '.rtrim(rtrim(number_format($balance, 4, '.', ''), '0'), '.'),
                    'balance' => round($balance, 4),
                ];
            }),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $uid = (int) auth()->id();
        $data = $request->validate([
            'type' => ['required', 'in:supplier,expense'],
            'supplier_id' => ['nullable', Rule::exists('suppliers', 'id')->where('user_id', $uid)],
            'expense_account_id' => ['nullable', Rule::exists('accounts', 'id')->where('user_id', $uid)],
            'date' => ['required', 'date'],
            'reference' => ['nullable', 'string', 'max:50'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'purchase_invoice_id' => ['nullable', Rule::exists('purchase_invoices', 'id')->where('user_id', $uid)],
        ]);

        if ($data['type'] === 'supplier' && empty($data['supplier_id'])) {
            return back()->withInput()->with('error', 'يجب اختيار المورد في سند صرف مورد.');
        }

        if ($data['type'] === 'expense' && empty($data['expense_account_id'])) {
            return back()->withInput()->with('error', 'يجب اختيار حساب مصروف في سند صرف مصروف.');
        }

        if ($data['type'] === 'expense' && ! empty($data['purchase_invoice_id'])) {
            return back()->withInput()->with('error', 'لا يمكن ربط فاتورة مشتريات مع سند صرف مصروف.');
        }

        $user = $request->user();
        $amount = (float) $data['amount'];

        try {
            DB::transaction(function () use ($data, $user, $amount, $uid) {
                $purchaseInvoice = null;
                if (! empty($data['purchase_invoice_id']) && $data['type'] === 'supplier') {
                    /** @var PurchaseInvoice $purchaseInvoice */
                    $purchaseInvoice = PurchaseInvoice::query()
                        ->whereKey($data['purchase_invoice_id'])
                        ->lockForUpdate()
                        ->firstOrFail();

                    if ((int) $purchaseInvoice->supplier_id !== (int) $data['supplier_id']) {
                        throw new RuntimeException('فاتورة المشتريات المختارة لا تخص المورد المحدد.');
                    }

                    $balance = max(0, (float) $purchaseInvoice->total - (float) ($purchaseInvoice->paid_amount ?? 0));
                    if ($amount > $balance + 0.0001) {
                        throw new RuntimeException('مبلغ السند أكبر من الرصيد المتبقي للفاتورة ('.rtrim(rtrim(number_format($balance, 4, '.', ''), '0'), '.').').');
                    }
                }

                $treasuryAccount = $this->resolveTreasuryAccount();

                $description = $data['type'] === 'supplier'
                    ? 'سند صرف للمورد #'.$data['supplier_id'].($purchaseInvoice ? ' — فاتورة '.($purchaseInvoice->reference ?: '#'.$purchaseInvoice->id) : '')
                    : 'سند صرف مصروف من حساب #'.$data['expense_account_id'];

                $entry = JournalEntry::create([
                    'user_id' => $uid,
                    'date' => $data['date'],
                    'reference' => 'PMT',
                    'description' => $description,
                    'total' => $amount,
                ]);

                if ($data['type'] === 'supplier') {
                    $suppliersAccount = $this->resolveAccountsPayableAccount();

                    JournalItem::query()->create([
                        'journal_entry_id' => $entry->id,
                        'account_id' => $suppliersAccount->id,
                        'description' => $purchaseInvoice
                            ? 'تخفيض ذمة المورد — فاتورة '.($purchaseInvoice->reference ?: '#'.$purchaseInvoice->id)
                            : 'تسوية رصيد مورد',
                        'debit' => $amount,
                        'credit' => 0,
                    ]);
                } else {
                    JournalItem::query()->create([
                        'journal_entry_id' => $entry->id,
                        'account_id' => $data['expense_account_id'],
                        'description' => 'صرف مصروف',
                        'debit' => $amount,
                        'credit' => 0,
                    ]);
                }

                JournalItem::query()->create([
                    'journal_entry_id' => $entry->id,
                    'account_id' => $treasuryAccount->id,
                    'description' => 'صرف من الخزينة/البنك',
                    'debit' => 0,
                    'credit' => $amount,
                ]);

                $payment = Payment::query()->create([
                    'user_id' => (int) $user->id,
                    'supplier_id' => $data['type'] === 'supplier' ? $data['supplier_id'] : null,
                    'expense_account_id' => $data['type'] === 'expense' ? $data['expense_account_id'] : null,
                    'date' => $data['date'],
                    'reference' => $data['reference'] ?? null,
                    'amount' => $amount,
                    'type' => $data['type'],
                    'journal_entry_id' => $entry->id,
                    'created_by' => $user->id,
                ]);

                $entry->reference = 'PMT-'.$payment->id;
                $entry->save();

                if ($purchaseInvoice !== null) {
                    $oldPaid = (float) ($purchaseInvoice->paid_amount ?? 0);
                    $oldStatus = (string) $purchaseInvoice->status;

                    $purchaseInvoice->paid_amount = $oldPaid + $amount;
                    $purchaseInvoice->refreshPaymentStatus();
                    $purchaseInvoice->save();

                    $payment->purchaseInvoices()->attach($purchaseInvoice->id, ['amount' => $amount]);

                    AuditTrail::log('update', 'purchase_invoices', $purchaseInvoice->id, [
                        'paid_amount' => $oldPaid,
                        'status' => $oldStatus,
                    ], [
                        'paid_amount' => (float) $purchaseInvoice->paid_amount,
                        'status' => $purchaseInvoice->status,
                        'payment_id' => $payment->id,
                        'payment_reference' => 'PMT-'.$payment->id,
                    ]);
                }

                AuditLog::create([
                    'actor_id' => $user->id,
                    'target_user_id' => null,
                    'action' => 'payment_created',
                    'old_role' => null,
                    'new_role' => null,
                    'logged_at' => now(),
                ]);
            });
        } catch (RuntimeException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()
            ->route('finance.payments.index')
            ->with('success', 'تم حفظ سند الصرف وإنشاء القيد المحاسبي بنجاح.');
    }

    private function resolveTreasuryAccount(): Account
    {
        $code = (string) config('accounting.treasury_account_code', DefaultLedgerAccounts::CODE_CASH);
        $acc = Account::query()->where('code', $code)->first();
        if ($acc) {
            return $acc;
        }

        return $code === DefaultLedgerAccounts::CODE_BANK
            ? DefaultLedgerAccounts::bankMain()
            : DefaultLedgerAccounts::cashOnHand();
    }

    private function resolveAccountsPayableAccount(): Account
    {
        $code = (string) config('accounting.accounts_payable_code', DefaultLedgerAccounts::CODE_AP);
        $acc = Account::query()->where('code', $code)->first();
        if ($acc) {
            return $acc;
        }

        return DefaultLedgerAccounts::accountsPayable();
    }
}
