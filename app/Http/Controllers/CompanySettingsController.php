<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\CompanySetting;
use App\Support\AccountingLedgerOptions;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CompanySettingsController extends Controller
{
    public function edit(): View
    {
        $uid = (int) auth()->id();
        $setting = CompanySetting::first() ?? new CompanySetting;

        $receivableOpts = $this->mergeAccountOption(
            AccountingLedgerOptions::receivableAssetAccountsForUser($uid),
            $uid,
            (int) ($setting->default_receivable_account_id ?? 0)
        );
        $payableOpts = $this->mergeAccountOption(
            AccountingLedgerOptions::payableLiabilityAccountsForUser($uid),
            $uid,
            (int) ($setting->default_payable_account_id ?? 0)
        );
        $purchaseDiscOpts = $this->mergeAccountOption(
            AccountingLedgerOptions::expenseAccountsForUser($uid),
            $uid,
            (int) ($setting->purchase_discount_ledger_account_id ?? 0)
        );
        $salesDiscOpts = $this->mergeAccountOption(
            AccountingLedgerOptions::revenueLeafAccountsForUser($uid),
            $uid,
            (int) ($setting->sales_allowed_discount_ledger_account_id ?? 0)
        );
        $payrollExpenseOpts = $this->mergeAccountOption(
            AccountingLedgerOptions::expenseAccountsForUser($uid),
            $uid,
            (int) ($setting->payroll_wage_expense_account_id ?? 0)
        );
        $payrollPayableOpts = $this->mergeAccountOption(
            AccountingLedgerOptions::liabilityLeafAccountsForUser($uid),
            $uid,
            (int) ($setting->payroll_wages_payable_account_id ?? 0)
        );
        $payrollCashOpts = $this->mergeAccountOption(
            AccountingLedgerOptions::cashEquivalentAssetAccountsForUser($uid),
            $uid,
            (int) ($setting->payroll_default_payment_account_id ?? 0)
        );

        return view('settings.company', compact(
            'setting',
            'receivableOpts',
            'payableOpts',
            'purchaseDiscOpts',
            'salesDiscOpts',
            'payrollExpenseOpts',
            'payrollPayableOpts',
            'payrollCashOpts'
        ));
    }

    public function update(Request $request): RedirectResponse
    {
        $uid = (int) auth()->id();
        $recvIds = collect(AccountingLedgerOptions::receivableAssetAccountsForUser($uid))->pluck('value')->map(fn ($v) => (int) $v)->all();
        $payIds = collect(AccountingLedgerOptions::payableLiabilityAccountsForUser($uid))->pluck('value')->map(fn ($v) => (int) $v)->all();
        $expIds = collect(AccountingLedgerOptions::expenseAccountsForUser($uid))->pluck('value')->map(fn ($v) => (int) $v)->all();
        $revIds = collect(AccountingLedgerOptions::revenueLeafAccountsForUser($uid))->pluck('value')->map(fn ($v) => (int) $v)->all();
        $liabIds = collect(AccountingLedgerOptions::liabilityLeafAccountsForUser($uid))->pluck('value')->map(fn ($v) => (int) $v)->all();
        $cashIds = collect(AccountingLedgerOptions::cashEquivalentAssetAccountsForUser($uid))->pluck('value')->map(fn ($v) => (int) $v)->all();

        $data = $request->validate([
            'name' => ['nullable', 'string', 'max:255'],
            'tax_number' => ['nullable', 'string', 'max:100'],
            'currency_code' => ['nullable', 'string', 'max:10'],
            'default_vat_percent' => ['required', 'numeric', 'min:0', 'max:100'],
            'default_receivable_account_id' => ['required', 'integer', Rule::in($recvIds)],
            'default_payable_account_id' => ['required', 'integer', Rule::in($payIds)],
            'purchase_discount_ledger_account_id' => ['required', 'integer', Rule::in($expIds)],
            'sales_allowed_discount_ledger_account_id' => ['required', 'integer', Rule::in($revIds)],
            'payroll_wage_expense_account_id' => ['nullable', 'integer', Rule::in($expIds)],
            'payroll_wages_payable_account_id' => ['nullable', 'integer', Rule::in($liabIds)],
            'payroll_default_payment_account_id' => ['nullable', 'integer', Rule::in($cashIds)],
            'commercial_register' => ['nullable', 'string', 'max:100'],
            'address' => ['nullable', 'string', 'max:500'],
            'logo_url' => ['nullable', 'string', 'max:500'],
            'logo_file' => ['nullable', 'image', 'max:2048'],
        ]);

        $setting = CompanySetting::first();
        if (! $setting) {
            $setting = new CompanySetting;
        }

        $setting->name = $data['name'] ?? null;
        $setting->tax_number = $data['tax_number'] ?? null;
        if (array_key_exists('currency_code', $data)) {
            $raw = trim((string) ($data['currency_code'] ?? ''));
            $setting->currency_code = $raw !== ''
                ? mb_strtoupper(mb_substr($raw, 0, 10))
                : 'SAR';
        }
        $setting->default_vat_percent = (float) $data['default_vat_percent'];
        $setting->default_receivable_account_id = (int) $data['default_receivable_account_id'];
        $setting->default_payable_account_id = (int) $data['default_payable_account_id'];
        $setting->purchase_discount_ledger_account_id = (int) $data['purchase_discount_ledger_account_id'];
        $setting->sales_allowed_discount_ledger_account_id = (int) $data['sales_allowed_discount_ledger_account_id'];
        $setting->payroll_wage_expense_account_id = ! empty($data['payroll_wage_expense_account_id'])
            ? (int) $data['payroll_wage_expense_account_id']
            : null;
        $setting->payroll_wages_payable_account_id = ! empty($data['payroll_wages_payable_account_id'])
            ? (int) $data['payroll_wages_payable_account_id']
            : null;
        $setting->payroll_default_payment_account_id = ! empty($data['payroll_default_payment_account_id'])
            ? (int) $data['payroll_default_payment_account_id']
            : null;
        $setting->commercial_register = $data['commercial_register'] ?? null;
        $setting->address = $data['address'] ?? null;

        if ($request->hasFile('logo_file')) {
            $oldPath = $setting->logo_url && str_starts_with($setting->logo_url, 'company/')
                ? $setting->logo_url
                : null;
            $path = $request->file('logo_file')->store('company', 'public');
            $setting->logo_url = $path;
            if ($oldPath) {
                Storage::disk('public')->delete($oldPath);
            }
        } elseif (array_key_exists('logo_url', $data)) {
            $setting->logo_url = $data['logo_url'] ?: null;
        }

        $setting->save();

        return redirect()
            ->route('settings.company.edit')
            ->with('success', 'تم حفظ إعدادات المنشأة بنجاح.');
    }

    /**
     * @param  list<array{value: int, label: string}>  $options
     * @return list<array{value: int, label: string}>
     */
    private function mergeAccountOption(array $options, int $userId, int $accountId): array
    {
        if ($accountId <= 0) {
            return $options;
        }
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
