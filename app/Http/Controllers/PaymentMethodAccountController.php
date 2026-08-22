<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ResolvesOperationsTenant;
use App\Models\Account;
use App\Models\PaymentMethodAccount;
use App\Support\AccountingLedgerOptions;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class PaymentMethodAccountController extends Controller
{
    use ResolvesOperationsTenant;

    public function edit(): View
    {
        $uid = $this->resolveOperationsTenantUserId();
        PaymentMethodAccount::ensureDefaultsForUser($uid);

        $rows = PaymentMethodAccount::query()
            ->where('user_id', $uid)
            ->orderBy('method_key')
            ->get()
            ->keyBy('method_key');

        $options = AccountingLedgerOptions::cashEquivalentAssetAccountsForUser($uid);
        foreach ($rows as $row) {
            $options = $this->mergeCashEqOption($options, $uid, (int) $row->ledger_account_id);
        }

        $labels = [
            PaymentMethodAccount::KEY_CASH => 'نقدي',
            PaymentMethodAccount::KEY_TRANSFER => 'تحويل بنكي',
            PaymentMethodAccount::KEY_CARD => 'بطاقة / شبكة',
        ];

        return view('finance.payment-method-accounts.edit', compact('rows', 'options', 'labels'));
    }

    public function update(Request $request): RedirectResponse
    {
        $uid = $this->resolveOperationsTenantUserId();
        PaymentMethodAccount::ensureDefaultsForUser($uid);

        $allowed = collect(AccountingLedgerOptions::cashEquivalentAssetAccountsForUser($uid))
            ->pluck('value')
            ->map(fn ($v) => (int) $v)
            ->all();

        $data = $request->validate([
            'ledger_cash' => ['required', 'integer', Rule::in($allowed)],
            'ledger_transfer' => ['required', 'integer', Rule::in($allowed)],
            'ledger_card' => ['required', 'integer', Rule::in($allowed)],
        ]);

        foreach ([
            PaymentMethodAccount::KEY_CASH => (int) $data['ledger_cash'],
            PaymentMethodAccount::KEY_TRANSFER => (int) $data['ledger_transfer'],
            PaymentMethodAccount::KEY_CARD => (int) $data['ledger_card'],
        ] as $key => $accountId) {
            PaymentMethodAccount::query()
                ->where('user_id', $uid)
                ->where('method_key', $key)
                ->update(['ledger_account_id' => $accountId]);
        }

        return redirect()
            ->route('finance.payment-method-accounts.edit')
            ->with('success', 'تم حفظ ربط وسائل الدفع بالدليل.');
    }

    /**
     * @param  list<array{value: int, label: string}>  $options
     * @return list<array{value: int, label: string}>
     */
    private function mergeCashEqOption(array $options, int $userId, int $accountId): array
    {
        $col = collect($options);
        if ($col->contains('value', $accountId)) {
            return $options;
        }
        $acc = Account::query()->where('user_id', $userId)->whereKey($accountId)->first(['id', 'code', 'name_ar', 'name_en']);
        if ($acc) {
            $col->push([
                'value' => $acc->id,
                'label' => trim($acc->code.' — '.($acc->name_ar ?: $acc->name_en)),
            ]);
        }

        return $col->values()->all();
    }
}
