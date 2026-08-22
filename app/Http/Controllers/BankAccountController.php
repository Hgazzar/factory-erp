<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ResolvesOperationsTenant;
use App\Models\Account;
use App\Models\BankAccount;
use App\Models\Cheque;
use App\Models\Payment;
use App\Support\DefaultLedgerAccounts;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class BankAccountController extends Controller
{
    use ResolvesOperationsTenant;

    public function index(Request $request): View
    {
        $search = trim((string) $request->query('search', ''));
        $status = (string) $request->query('status', '');

        $accounts = BankAccount::query()
            ->with(['ledgerAccount:id,code,name_ar'])
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($inner) use ($search) {
                    $inner->where('bank_name', 'like', '%' . $search . '%')
                        ->orWhere('branch_name', 'like', '%' . $search . '%')
                        ->orWhere('account_number', 'like', '%' . $search . '%')
                        ->orWhere('iban', 'like', '%' . $search . '%');
                });
            })
            ->when(in_array($status, ['active', 'inactive'], true), fn ($query) => $query->where('status', $status))
            ->orderBy('bank_name')
            ->orderBy('account_number')
            ->paginate(15)
            ->withQueryString();

        return view('finance.bank-accounts.index', compact('accounts', 'search', 'status'));
    }

    public function create(): View
    {
        return view('finance.bank-accounts.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate($this->rules());
        $uid = $this->resolveOperationsTenantUserId();

        DB::transaction(function () use ($request, $data, $uid): void {
            $ledger = $this->createLedgerAccountForBank($uid, (string) $data['bank_name'], (string) $data['account_number']);

            BankAccount::query()->create([
                'user_id' => $uid,
                'bank_name' => $data['bank_name'],
                'branch_name' => $data['branch_name'] ?? null,
                'account_number' => $data['account_number'],
                'iban' => $data['iban'] ?? null,
                'currency' => $data['currency'],
                'ledger_account_id' => $ledger->id,
                'opening_balance' => 0,
                'status' => $data['status'],
                'created_by' => $request->user()?->id,
            ]);
        });

        return redirect()
            ->route('finance.bank-accounts.index')
            ->with('success', 'تم إنشاء الحساب البنكي وحسابه في الدليل تلقائياً.');
    }

    public function edit(BankAccount $bankAccount): View
    {
        $bankAccount->load(['ledgerAccount:id,code,name_ar,name_en']);

        return view('finance.bank-accounts.edit', ['account' => $bankAccount]);
    }

    public function update(Request $request, BankAccount $bankAccount): RedirectResponse
    {
        $data = $request->validate($this->rules($bankAccount->id));

        DB::transaction(function () use ($bankAccount, $data): void {
            $bankAccount->update([
                'bank_name' => $data['bank_name'],
                'branch_name' => $data['branch_name'] ?? null,
                'account_number' => $data['account_number'],
                'iban' => $data['iban'] ?? null,
                'currency' => $data['currency'],
                'status' => $data['status'],
            ]);

            $this->syncLinkedLedgerAccountLabels($bankAccount->fresh());
        });

        return redirect()
            ->route('finance.bank-accounts.index')
            ->with('success', 'تم تحديث الحساب البنكي بنجاح.');
    }

    public function destroy(BankAccount $bankAccount): RedirectResponse
    {
        $hasLinkedCheques = Cheque::query()
            ->where('bank_name', $bankAccount->bank_name)
            ->exists();

        $hasLinkedPayments = Payment::query()
            ->where('bank_account_id', $bankAccount->id)
            ->exists();

        $hasLinkedExpenses = Payment::query()
            ->where('type', 'expense')
            ->whereIn('payment_method', ['bank', 'check'])
            ->where(function ($query) use ($bankAccount) {
                $query->where('reference', 'like', '%' . $bankAccount->account_number . '%')
                    ->orWhere('notes', 'like', '%' . $bankAccount->account_number . '%')
                    ->orWhere('notes', 'like', '%' . $bankAccount->bank_name . '%');
            })
            ->exists();

        if ($hasLinkedCheques || $hasLinkedPayments || $hasLinkedExpenses) {
            return redirect()
                ->route('finance.bank-accounts.index')
                ->with('error', 'لا يمكن حذف الحساب لوجود عمليات مالية مرتبطة به، يمكنك تعطيله بدلاً من ذلك.');
        }

        DB::transaction(function () use ($bankAccount): void {
            $bankAccount->delete();
        });

        return redirect()
            ->route('finance.bank-accounts.index')
            ->with('success', 'تم حذف الحساب البنكي بنجاح.');
    }

    private function rules(?int $ignoreId = null): array
    {
        $uid = $this->resolveOperationsTenantUserId();

        return [
            'bank_name' => ['required', 'string', 'max:150'],
            'branch_name' => ['nullable', 'string', 'max:150'],
            'account_number' => [
                'required',
                'string',
                'max:100',
                Rule::unique('bank_accounts', 'account_number')->where('user_id', $uid)->ignore($ignoreId),
            ],
            'iban' => [
                'nullable',
                'string',
                'max:34',
                Rule::unique('bank_accounts', 'iban')->where('user_id', $uid)->ignore($ignoreId),
            ],
            'currency' => ['required', 'string', 'size:3'],
            'status' => ['required', 'in:active,inactive'],
        ];
    }

    /**
     * الحساب الأب لفرع كل بنك: من الإعدادات (افتراضي 1020) أو إنشاء شجرة البنك القياسية.
     */
    private function resolveBankLedgerParent(int $userId): Account
    {
        $code = trim((string) config('accounting.bank_ledger_parent_code', '1020'));

        $parent = Account::withoutGlobalScopes()
            ->where('user_id', $userId)
            ->where('code', $code)
            ->first();

        if ($parent) {
            return $parent;
        }

        return DefaultLedgerAccounts::bankMain();
    }

    /**
     * إنشاء حساب أصول فرعي تحت مجموعة البنوك وربطه لاحقاً بسجل bank_accounts.
     */
    private function createLedgerAccountForBank(int $userId, string $bankName, string $accountNumber): Account
    {
        $parent = $this->resolveBankLedgerParent($userId);
        $code = Account::generateNextNumericCodeForUser($userId, (int) $parent->id);

        $labelAr = 'بنك — '.trim($bankName).' ('.trim($accountNumber).')';

        return Account::withoutGlobalScopes()->create([
            'user_id' => $userId,
            'code' => $code,
            'name_ar' => $labelAr,
            'name_en' => 'Bank — '.trim($bankName),
            'type' => Account::TYPE_ASSET,
            'parent_id' => $parent->id,
            'opening_balance' => 0,
            'is_active' => true,
            'is_bank' => true,
            'allow_direct_posting' => true,
        ]);
    }

    private function syncLinkedLedgerAccountLabels(BankAccount $bank): void
    {
        if (! $bank->ledger_account_id) {
            return;
        }

        $ledger = Account::withoutGlobalScopes()
            ->where('user_id', $bank->user_id)
            ->whereKey($bank->ledger_account_id)
            ->first();

        if (! $ledger) {
            return;
        }

        $labelAr = 'بنك — '.trim((string) $bank->bank_name).' ('.trim((string) $bank->account_number).')';

        $ledger->update([
            'name_ar' => $labelAr,
            'name_en' => 'Bank — '.trim((string) $bank->bank_name),
        ]);
    }
}
