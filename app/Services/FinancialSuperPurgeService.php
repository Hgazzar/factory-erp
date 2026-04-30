<?php

namespace App\Services;

use App\Models\FixedAssetCategory;
use App\Models\PaymentMethodAccount;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * يمسح البيانات المالية المرتبطة بمستخدم مستهدف (مدفوعات، قيود يومية، حسابات دليل).
 * يفك مراجع restrict قبل حذف الحسابات حتى يعمل على PostgreSQL (Railway) وليس فقط MySQL.
 */
class FinancialSuperPurgeService
{
    /**
     * @return array{payments_deleted: int, journal_entries_deleted: int, journal_items_deleted: int, accounts_deleted: int, fixed_assets_deleted: int}
     */
    public function purge(int $targetUserId): array
    {
        $stats = [
            'fixed_assets_deleted' => 0,
            'journal_items_deleted' => 0,
            'journal_entries_deleted' => 0,
            'payments_deleted' => 0,
            'accounts_deleted' => 0,
        ];

        DB::transaction(function () use ($targetUserId, &$stats): void {
            $driver = DB::connection()->getDriverName();
            $fkWasDisabled = false;

            if ($driver === 'mysql') {
                DB::statement('SET FOREIGN_KEY_CHECKS=0');
                $fkWasDisabled = true;
            }

            try {
                $paymentIds = $this->paymentIdsOwnedByUser($targetUserId);

                if ($paymentIds->isNotEmpty()) {
                    $stats['fixed_assets_deleted'] = DB::table('fixed_assets')
                        ->whereIn('source_payment_id', $paymentIds->all())
                        ->delete();
                }

                $journalEntryIds = $this->collectJournalEntryIdsForFinancialPurge($targetUserId, $paymentIds);

                if ($journalEntryIds !== []) {
                    $stats['journal_items_deleted'] += DB::table('journal_items')
                        ->whereIn('journal_entry_id', $journalEntryIds)
                        ->delete();
                }

                if (Schema::hasTable('journal_items') && Schema::hasColumn('journal_items', 'user_id')) {
                    $stats['journal_items_deleted'] += DB::table('journal_items')
                        ->where('user_id', $targetUserId)
                        ->delete();
                }

                $accountIdsForUser = DB::table('accounts')->where('user_id', $targetUserId)->pluck('id');
                if ($accountIdsForUser->isNotEmpty()) {
                    $stats['journal_items_deleted'] += DB::table('journal_items')
                        ->whereIn('account_id', $accountIdsForUser)
                        ->delete();
                }

                if ($journalEntryIds !== []) {
                    $stats['journal_entries_deleted'] += DB::table('journal_entries')
                        ->whereIn('id', $journalEntryIds)
                        ->delete();
                }

                $stats['payments_deleted'] = $this->deletePaymentsOwnedByUser($targetUserId);

                $this->detachRestrictReferencesBeforePurgingAccounts($targetUserId);

                $stats['accounts_deleted'] = $this->deleteAccountsLeafFirst($targetUserId);

                $this->reprovisionTenantFinancialShell($targetUserId);
            } finally {
                if ($fkWasDisabled) {
                    DB::statement('SET FOREIGN_KEY_CHECKS=1');
                }
            }
        });

        return $stats;
    }

    /**
     * @return \Illuminate\Support\Collection<int, int>
     */
    private function paymentIdsOwnedByUser(int $targetUserId)
    {
        if (! Schema::hasTable('payments')) {
            return collect();
        }

        return DB::table('payments')->where(function ($sub) use ($targetUserId): void {
            if (Schema::hasColumn('payments', 'user_id')) {
                $sub->where('user_id', $targetUserId)
                    ->orWhere('created_by', $targetUserId);
            } else {
                $sub->where('created_by', $targetUserId);
            }
        })->pluck('id');
    }

    private function deletePaymentsOwnedByUser(int $targetUserId): int
    {
        if (! Schema::hasTable('payments')) {
            return 0;
        }

        return DB::table('payments')
            ->where(function ($sub) use ($targetUserId): void {
                if (Schema::hasColumn('payments', 'user_id')) {
                    $sub->where('user_id', $targetUserId)
                        ->orWhere('created_by', $targetUserId);
                } else {
                    $sub->where('created_by', $targetUserId);
                }
            })
            ->delete();
    }

    /**
     * @param  \Illuminate\Support\Collection<int, int>  $paymentIds
     * @return list<int>
     */
    private function collectJournalEntryIdsForFinancialPurge(int $targetUserId, $paymentIds): array
    {
        $ids = collect();

        if ($paymentIds->isNotEmpty() && Schema::hasTable('payments') && Schema::hasColumn('payments', 'journal_entry_id')) {
            $ids = $ids->merge(
                DB::table('payments')
                    ->whereIn('id', $paymentIds->all())
                    ->whereNotNull('journal_entry_id')
                    ->pluck('journal_entry_id')
            );
        }

        if (Schema::hasTable('journal_entries')) {
            $jeHasCreatedBy = Schema::hasColumn('journal_entries', 'created_by');
            $ids = $ids->merge(
                DB::table('journal_entries')
                    ->where(function ($q) use ($targetUserId, $jeHasCreatedBy): void {
                        $q->where('user_id', $targetUserId);
                        if ($jeHasCreatedBy) {
                            $q->orWhere('created_by', $targetUserId);
                        }
                    })
                    ->pluck('id')
            );
        }

        return $ids->unique()->filter()->values()->map(fn ($v) => (int) $v)->all();
    }

    /**
     * يفك الارتباطات التي تمنع حذف حسابات المستأجر على PostgreSQL (بدون تعطيل FK).
     */
    private function detachRestrictReferencesBeforePurgingAccounts(int $targetUserId): void
    {
        $accountIds = DB::table('accounts')->where('user_id', $targetUserId)->pluck('id');
        if ($accountIds->isEmpty()) {
            return;
        }

        $ids = $accountIds->all();

        if (Schema::hasTable('bank_reconciliations') && Schema::hasColumn('bank_reconciliations', 'account_id')) {
            DB::table('bank_reconciliations')->whereIn('account_id', $ids)->delete();
        }

        $categoryIds = collect();
        if (Schema::hasTable('fixed_asset_categories') && Schema::hasColumn('fixed_asset_categories', 'user_id')) {
            $categoryIds = DB::table('fixed_asset_categories')
                ->where('user_id', $targetUserId)
                ->pluck('id');
        }

        if (Schema::hasTable('fixed_assets')) {
            if ($categoryIds->isNotEmpty()) {
                DB::table('fixed_assets')
                    ->whereIn('fixed_asset_category_id', $categoryIds->all())
                    ->delete();
            }
            if (Schema::hasColumn('fixed_assets', 'ledger_account_id')) {
                DB::table('fixed_assets')
                    ->whereIn('ledger_account_id', $ids)
                    ->update(['ledger_account_id' => null]);
            }
        }

        if (Schema::hasTable('fixed_asset_categories') && Schema::hasColumn('fixed_asset_categories', 'user_id')) {
            DB::table('fixed_asset_categories')->where('user_id', $targetUserId)->delete();
        }

        if (Schema::hasTable('bank_accounts') && Schema::hasColumn('bank_accounts', 'user_id')) {
            DB::table('bank_accounts')->where('user_id', $targetUserId)->delete();
        }

        if (Schema::hasTable('payment_method_accounts') && Schema::hasColumn('payment_method_accounts', 'user_id')) {
            DB::table('payment_method_accounts')->where('user_id', $targetUserId)->delete();
        }

        if (Schema::hasTable('tax_rates') && Schema::hasColumn('tax_rates', 'user_id')) {
            DB::table('tax_rates')->where('user_id', $targetUserId)->delete();
        }

        if (Schema::hasTable('employees') && Schema::hasColumn('employees', 'ledger_account_id')) {
            DB::table('employees')
                ->where('user_id', $targetUserId)
                ->whereIn('ledger_account_id', $ids)
                ->update(['ledger_account_id' => null]);
        }

        $companyAccountCols = [
            'default_receivable_account_id',
            'default_payable_account_id',
            'purchase_discount_ledger_account_id',
            'sales_allowed_discount_ledger_account_id',
            'payroll_wage_expense_account_id',
            'payroll_wages_payable_account_id',
            'payroll_default_payment_account_id',
        ];
        if (Schema::hasTable('company_settings')) {
            foreach ($companyAccountCols as $col) {
                if (Schema::hasColumn('company_settings', $col)) {
                    DB::table('company_settings')->whereIn($col, $ids)->update([$col => null]);
                }
            }
        }

        $itemCategoryCols = ['inventory_account_id', 'sales_income_account_id', 'cogs_account_id'];
        if (Schema::hasTable('item_categories')) {
            foreach ($itemCategoryCols as $col) {
                if (Schema::hasColumn('item_categories', $col)) {
                    DB::table('item_categories')->whereIn($col, $ids)->update([$col => null]);
                }
            }
        }
    }

    /**
     * حذف حسابات الدليل للمستخدم من الأوراق إلى الجذر لتفادي انتهاك FK على parent_id.
     */
    private function deleteAccountsLeafFirst(int $userId): int
    {
        $deletedTotal = 0;
        $n = 0;

        do {
            $leafIds = DB::table('accounts')
                ->where('user_id', $userId)
                ->whereNotIn('id', function ($q): void {
                    $q->select('parent_id')
                        ->from('accounts')
                        ->whereNotNull('parent_id');
                })
                ->pluck('id');

            if ($leafIds->isEmpty()) {
                break;
            }

            $n = DB::table('accounts')->whereIn('id', $leafIds)->delete();
            $deletedTotal += $n;
        } while ($n > 0);

        return $deletedTotal;
    }

    /**
     * بعد المسح التجريبي: دليل قياسي + وسائل دفع + ضريبة VAT + فئة أصول افتراضية (كما بعد التسجيل تقريباً).
     */
    private function reprovisionTenantFinancialShell(int $userId): void
    {
        ChartOfAccountsProvisioner::ensureForUser($userId);
        PaymentMethodAccount::ensureDefaultsForUser($userId);

        if (Schema::hasTable('tax_rates')) {
            $ledgerId = DB::table('accounts')->where('user_id', $userId)->where('code', '2030')->value('id');
            if ($ledgerId && ! DB::table('tax_rates')->where('user_id', $userId)->where('code', 'VAT')->exists()) {
                DB::table('tax_rates')->insert([
                    'user_id' => $userId,
                    'code' => 'VAT',
                    'name_ar' => 'ضريبة القيمة المضافة',
                    'name_en' => 'VAT',
                    'rate_percent' => 15,
                    'ledger_account_id' => (int) $ledgerId,
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        FixedAssetCategory::ensureDefaultForUser($userId);
    }
}
