<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ResolvesOperationsTenant;
use App\Models\Account;
use App\Models\TaxRate;
use App\Support\AccountingLedgerOptions;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class TaxRateController extends Controller
{
    use ResolvesOperationsTenant;

    public function index(Request $request): View
    {
        $search = trim((string) $request->query('search', ''));
        $rates = TaxRate::query()
            ->with('ledgerAccount:id,code,name_ar')
            ->when($search !== '', function ($q) use ($search) {
                $q->where(function ($inner) use ($search) {
                    $inner->where('code', 'like', '%'.$search.'%')
                        ->orWhere('name_ar', 'like', '%'.$search.'%')
                        ->orWhere('name_en', 'like', '%'.$search.'%');
                });
            })
            ->orderBy('code')
            ->paginate(20)
            ->withQueryString();

        return view('finance.tax-rates.index', compact('rates', 'search'));
    }

    public function create(): View
    {
        $uid = $this->resolveOperationsTenantUserId();
        $liabilityOptions = AccountingLedgerOptions::liabilityLeafAccountsForUser($uid);

        return view('finance.tax-rates.create', compact('liabilityOptions'));
    }

    public function store(Request $request): RedirectResponse
    {
        $uid = $this->resolveOperationsTenantUserId();
        $data = $this->validatedTax($request, $uid);

        $data['is_active'] = (bool) (int) $data['is_active'];
        TaxRate::query()->create(array_merge($data, ['user_id' => $uid]));

        return redirect()
            ->route('finance.tax-rates.index')
            ->with('success', 'تم إنشاء الضريبة وربطها بالدليل.');
    }

    public function edit(TaxRate $taxRate): View
    {
        $uid = $this->resolveOperationsTenantUserId();
        $liabilityOptions = $this->liabilityOptionsIncluding($uid, (int) $taxRate->ledger_account_id);

        return view('finance.tax-rates.edit', compact('taxRate', 'liabilityOptions'));
    }

    public function update(Request $request, TaxRate $taxRate): RedirectResponse
    {
        $uid = $this->resolveOperationsTenantUserId();
        $data = $this->validatedTax($request, $uid, $taxRate->id);
        $data['is_active'] = (bool) (int) $data['is_active'];
        $taxRate->update($data);

        return redirect()
            ->route('finance.tax-rates.index')
            ->with('success', 'تم تحديث الضريبة.');
    }

    public function destroy(TaxRate $taxRate): RedirectResponse
    {
        $taxRate->delete();

        return redirect()
            ->route('finance.tax-rates.index')
            ->with('success', 'تم حذف الضريبة.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validatedTax(Request $request, int $userId, ?int $ignoreId = null): array
    {
        return $request->validate([
            'code' => [
                'required',
                'string',
                'max:32',
                Rule::unique('tax_rates', 'code')->where('user_id', $userId)->ignore($ignoreId),
            ],
            'name_ar' => ['required', 'string', 'max:255'],
            'name_en' => ['nullable', 'string', 'max:255'],
            'rate_percent' => ['required', 'numeric', 'min:0', 'max:100'],
            'ledger_account_id' => [
                'required',
                Rule::exists('accounts', 'id')->where(function ($q) use ($userId) {
                    $q->where('user_id', $userId)
                        ->where('type', Account::TYPE_LIABILITY)
                        ->whereNotNull('parent_id');
                }),
            ],
            'is_active' => ['required', Rule::in([0, 1, '0', '1'])],
        ]);
    }

    /**
     * @return list<array{value: int, label: string}>
     */
    private function liabilityOptionsIncluding(int $userId, int $ledgerAccountId): array
    {
        $base = collect(AccountingLedgerOptions::liabilityLeafAccountsForUser($userId));
        if ($base->contains('value', $ledgerAccountId)) {
            return $base->all();
        }
        $acc = Account::query()->where('user_id', $userId)->whereKey($ledgerAccountId)->first(['id', 'code', 'name_ar', 'name_en']);
        if ($acc) {
            $base->push([
                'value' => $acc->id,
                'label' => trim($acc->code.' — '.($acc->name_ar ?: $acc->name_en)),
            ]);
        }

        return $base->values()->all();
    }
}
