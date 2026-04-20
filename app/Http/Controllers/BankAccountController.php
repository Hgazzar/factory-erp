<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\BankAccount;
use App\Models\Cheque;
use App\Models\Payment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class BankAccountController extends Controller
{
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
        return view('finance.bank-accounts.create', [
            'ledgerAccountOptions' => $this->ledgerAccountSelectOptions(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate($this->rules());

        DB::transaction(function () use ($request, $data): void {
            BankAccount::query()->create([
                'user_id' => (int) $request->user()->id,
                'bank_name' => $data['bank_name'],
                'branch_name' => $data['branch_name'] ?? null,
                'account_number' => $data['account_number'],
                'iban' => $data['iban'] ?? null,
                'currency' => $data['currency'],
                'ledger_account_id' => (int) $data['ledger_account_id'],
                'opening_balance' => 0,
                'status' => $data['status'],
                'created_by' => $request->user()?->id,
            ]);
        });

        return redirect()
            ->route('finance.bank-accounts.index')
            ->with('success', 'تم إنشاء الحساب البنكي بنجاح.');
    }

    public function edit(BankAccount $bankAccount): View
    {
        return view('finance.bank-accounts.edit', [
            'account' => $bankAccount,
            'ledgerAccountOptions' => $this->ledgerAccountSelectOptions(),
        ]);
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
                'ledger_account_id' => (int) $data['ledger_account_id'],
                'status' => $data['status'],
            ]);
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

        $hasLinkedExpenses = Payment::query()
            ->where('type', 'expense')
            ->whereIn('payment_method', ['bank', 'check'])
            ->where(function ($query) use ($bankAccount) {
                $query->where('reference', 'like', '%' . $bankAccount->account_number . '%')
                    ->orWhere('notes', 'like', '%' . $bankAccount->account_number . '%')
                    ->orWhere('notes', 'like', '%' . $bankAccount->bank_name . '%');
            })
            ->exists();

        if ($hasLinkedCheques || $hasLinkedExpenses) {
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
        $uid = (int) auth()->id();

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
            'ledger_account_id' => [
                'required',
                'integer',
                Rule::exists('accounts', 'id')->where(function ($q) use ($uid): void {
                    $q->where('user_id', $uid)->where('type', Account::TYPE_ASSET);
                }),
                Rule::unique('bank_accounts', 'ledger_account_id')->where('user_id', $uid)->ignore($ignoreId),
            ],
            'status' => ['required', 'in:active,inactive'],
        ];
    }

    /**
     * @return list<array{value:int,label:string}>
     */
    private function ledgerAccountSelectOptions(): array
    {
        $uid = (int) auth()->id();

        return Account::query()
            ->where('user_id', $uid)
            ->where('type', Account::TYPE_ASSET)
            ->where(function ($q): void {
                $q->where('is_active', true)
                    ->orWhereNull('is_active');
            })
            ->orderBy('code')
            ->get(['id', 'code', 'name_ar', 'name_en'])
            ->map(fn (Account $a) => [
                'value' => (int) $a->id,
                'label' => trim($a->code.' — '.(string) ($a->name_ar ?: $a->name_en ?: '')),
            ])
            ->values()
            ->all();
    }
}
