<?php

declare(strict_types=1);

namespace Tests\Unit\Support;

use App\Models\Account;
use App\Models\User;
use App\Services\AccountingService;
use App\Services\FinancialRecordingService;
use App\Support\LedgerAccountBalance;
use PHPUnit\Framework\Attributes\Test;
use Tests\Support\AccountingTestCase;

final class LedgerAccountBalanceTest extends AccountingTestCase
{
    #[Test]
    public function for_account_id_returns_opening_balance_when_no_movements(): void
    {
        $account = Account::factory()->forTenant($this->tenant)->asset()->withBalances(150.0, 150.0)->create();

        $this->assertEqualsWithDelta(150.0, LedgerAccountBalance::forAccountId($account->id), 0.0001);
    }

    #[Test]
    public function for_account_id_includes_journal_movements(): void
    {
        $cash = $this->makeAccount(Account::TYPE_ASSET, openingBalance: 100.0);
        $revenue = $this->makeAccount(Account::TYPE_REVENUE, code: '4900');

        app(FinancialRecordingService::class)->recordBalancedJournal(
            userId: (int) $this->tenant->id,
            date: now()->toDateString(),
            reference: 'LEDGER-001',
            description: 'اختبار رصيد',
            lines: [
                ['account_id' => $cash->id, 'debit' => 50, 'credit' => 0],
                ['account_id' => $revenue->id, 'debit' => 0, 'credit' => 50],
            ],
        );

        $this->assertEqualsWithDelta(150.0, LedgerAccountBalance::forAccountId($cash->id), 0.0001);
    }

    #[Test]
    public function ledger_balance_matches_accounting_service_current_balance_after_posting(): void
    {
        $asset = $this->makeAccount(Account::TYPE_ASSET, openingBalance: 200.0);
        $revenue = $this->makeAccount(Account::TYPE_REVENUE, code: '4910');

        app(FinancialRecordingService::class)->recordBalancedJournal(
            userId: (int) $this->tenant->id,
            date: now()->toDateString(),
            reference: 'LEDGER-002',
            description: 'مزامنة',
            lines: [
                ['account_id' => $asset->id, 'debit' => 75, 'credit' => 0],
                ['account_id' => $revenue->id, 'debit' => 0, 'credit' => 75],
            ],
        );

        $ledgerBalance = LedgerAccountBalance::forAccountId($asset->id);
        $storedBalance = (float) Account::withoutGlobalScopes()->whereKey($asset->id)->value('current_balance');

        $this->assertEqualsWithDelta($ledgerBalance, $storedBalance, 0.0001);
        $this->assertEqualsWithDelta(275.0, $ledgerBalance, 0.0001);
    }

    #[Test]
    public function for_account_code_resolves_tenant_scoped_account(): void
    {
        $account = Account::factory()->forTenant($this->tenant)->asset()->withBalances(80.0, 80.0)->create([
            'code' => '1888',
        ]);

        $this->assertEqualsWithDelta(80.0, LedgerAccountBalance::forAccountCode('1888'), 0.0001);
        $this->assertEqualsWithDelta(80.0, LedgerAccountBalance::forAccountId($account->id), 0.0001);
    }

    #[Test]
    public function sum_for_account_code_across_users_aggregates_all_tenants(): void
    {
        $other = User::factory()->create(['role' => 'admin']);

        $a1 = Account::factory()->forTenant($this->tenant)->asset()->withBalances(10.0, 10.0)->create(['code' => '1999']);
        $a2 = Account::factory()->forTenant($other)->asset()->withBalances(20.0, 20.0)->create(['code' => '1999']);

        app(AccountingService::class)->applyJournalLineToCurrentBalance($a1, 5.0, 0.0);
        app(AccountingService::class)->applyJournalLineToCurrentBalance($a2, 0.0, 0.0);

        $sum = LedgerAccountBalance::sumForAccountCodeAcrossUsers('1999');

        $this->assertEqualsWithDelta(
            LedgerAccountBalance::forAccountId($a1->id) + LedgerAccountBalance::forAccountId($a2->id),
            $sum,
            0.0001
        );
    }
}
