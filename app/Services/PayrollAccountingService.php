<?php

namespace App\Services;

use App\Models\CompanySetting;
use App\Models\JournalEntry;
use App\Models\JournalItem;
use App\Models\Payroll;
use App\Support\AccountingLedgerOptions;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use RuntimeException;

final class PayrollAccountingService
{
    /**
     * @throws \Throwable
     */
    public function createAccrualEntry(Payroll $payroll, int $userId, int $createdById): ?JournalEntry
    {
        $uid = (int) $payroll->user_id;
        $amount = (float) $payroll->total_amount;
        if ($amount <= 0) {
            return null;
        }

        $setting = CompanySetting::forTenant($uid);
        $expenseId = (int) ($setting?->payroll_wage_expense_account_id ?? 0);
        $payableId = (int) ($setting?->payroll_wages_payable_account_id ?? 0);
        if ($expenseId < 1 || $payableId < 1) {
            throw new RuntimeException('يُرجى حفظ إعدادات الربط المحاسبي للرواتب (مصروف الأجور والأجور المستحقة) في إعدادات المنشأة.');
        }

        $expenseIds = collect(AccountingLedgerOptions::expenseAccountsForUser($uid))->pluck('value')->map(fn ($v) => (int) $v)->all();
        $liabilityIds = collect(AccountingLedgerOptions::liabilityLeafAccountsForUser($uid))->pluck('value')->map(fn ($v) => (int) $v)->all();
        if (! in_array($expenseId, $expenseIds, true) || ! in_array($payableId, $liabilityIds, true)) {
            throw new RuntimeException('الحسابات المختارة لمسير الرواتب غير صالحة للمستأجر الحالي. راجع إعدادات المنشأة.');
        }

        $accrualDate = Carbon::create((int) $payroll->year, (int) $payroll->month, 1)->endOfMonth();
        $headline = $this->accrualDescription($payroll);

        $entry = JournalEntry::query()->create([
            'user_id' => $userId,
            'created_by' => $createdById,
            'date' => $accrualDate,
            'reference' => 'PR-ACC-'.$payroll->id,
            'description' => $headline,
            'total' => $amount,
        ]);

        JournalItem::query()->create([
            'journal_entry_id' => $entry->id,
            'account_id' => $expenseId,
            'description' => 'تكلفة أجور (مدين) — '.$headline,
            'debit' => $amount,
            'credit' => 0,
        ]);

        JournalItem::query()->create([
            'journal_entry_id' => $entry->id,
            'account_id' => $payableId,
            'description' => 'أجور مستحقة (دائن) — '.$headline,
            'debit' => 0,
            'credit' => $amount,
        ]);

        return $entry;
    }

    /**
     * @throws \Throwable
     */
    public function postPaymentAndMarkPaid(
        Payroll $payroll,
        int $userId,
        int $createdById,
        ?int $paymentAccountId,
        string $paymentDateYmd
    ): void {
        $uid = (int) $payroll->user_id;
        $amount = (float) $payroll->total_amount;
        if ($amount <= 0) {
            DB::transaction(function () use ($payroll) {
                $payroll->status = Payroll::STATUS_PAID;
                $payroll->save();
            });

            return;
        }

        if ($paymentAccountId === null || $paymentAccountId < 1) {
            throw new RuntimeException('يُرجى اختيار حساب الصرف (خزينة أو بنك) لدفع الرواتب.');
        }

        $setting = CompanySetting::forTenant($uid);
        $payableId = (int) ($setting?->payroll_wages_payable_account_id ?? 0);
        if ($payableId < 1) {
            throw new RuntimeException('يُرجى ضبط حساب «الأجور المستحقة» في إعدادات الربط المحاسبي للرواتب.');
        }

        $cashIds = collect(AccountingLedgerOptions::cashEquivalentAssetAccountsForUser($uid))
            ->pluck('value')
            ->map(fn ($v) => (int) $v)
            ->all();
        if (! in_array((int) $paymentAccountId, $cashIds, true)) {
            throw new RuntimeException('حساب الصرف المختار غير مدرج في الأصول النقدية/البنوك المسموح بها.');
        }
        $liabilityIds = collect(AccountingLedgerOptions::liabilityLeafAccountsForUser($uid))
            ->pluck('value')
            ->map(fn ($v) => (int) $v)
            ->all();
        if (! in_array($payableId, $liabilityIds, true)) {
            throw new RuntimeException('حساب الأجور المستحقة في إعدادات المنشأة غير صالح. راجع الإعدادات.');
        }

        $payrollDate = Carbon::parse($paymentDateYmd);
        $headline = $this->paymentDescription($payroll);

        DB::transaction(function () use ($payroll, $userId, $createdById, $amount, $payableId, $paymentAccountId, $headline, $payrollDate) {
            if ($amount > 0) {
                $entry = JournalEntry::query()->create([
                    'user_id' => $userId,
                    'created_by' => $createdById,
                    'date' => $payrollDate,
                    'reference' => 'PR-PAY-'.$payroll->id,
                    'description' => $headline,
                    'total' => $amount,
                ]);

                JournalItem::query()->create([
                    'journal_entry_id' => $entry->id,
                    'account_id' => $payableId,
                    'description' => 'تسوية أجور مستحقة (مدين) — '.$headline,
                    'debit' => $amount,
                    'credit' => 0,
                ]);

                JournalItem::query()->create([
                    'journal_entry_id' => $entry->id,
                    'account_id' => $paymentAccountId,
                    'description' => 'صرف من نقد/بنك (دائن) — '.$headline,
                    'debit' => 0,
                    'credit' => $amount,
                ]);

                $payroll->payment_journal_entry_id = $entry->id;
            }

            $payroll->status = Payroll::STATUS_PAID;
            $payroll->save();
        });
    }

    public function accrualDescription(Payroll $payroll): string
    {
        $period = $payroll->periodLabelAr();

        return "إثبات رواتب شهر {$period} - دورة رقم #{$payroll->id}";
    }

    public function paymentDescription(Payroll $payroll): string
    {
        $period = $payroll->periodLabelAr();

        return "دفع رواتب شهر {$period} - دورة رقم #{$payroll->id}";
    }
}
