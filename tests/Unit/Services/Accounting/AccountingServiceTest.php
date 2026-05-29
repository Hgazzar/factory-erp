<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Accounting;

use App\Models\Account;
use App\Models\JournalEntry;
use App\Models\JournalItem;
use App\Services\AccountingService;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\Support\AccountingTestCase;

final class AccountingServiceTest extends AccountingTestCase
{
    private AccountingService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(AccountingService::class);
    }

    #[Test]
    public function asset_account_increases_on_debit(): void
    {
        $cash = $this->makeAccount(Account::TYPE_ASSET, openingBalance: 100.0);

        $this->service->applyJournalLineToCurrentBalance($cash, debit: 50.0, credit: 0.0);

        $this->assertBalance($cash, 150.0);
    }

    #[Test]
    public function asset_account_decreases_on_credit(): void
    {
        $cash = $this->makeAccount(Account::TYPE_ASSET, openingBalance: 200.0);

        $this->service->applyJournalLineToCurrentBalance($cash, debit: 0.0, credit: 75.0);

        $this->assertBalance($cash, 125.0);
    }

    #[Test]
    public function liability_account_decreases_on_debit(): void
    {
        $payable = $this->makeAccount(Account::TYPE_LIABILITY, openingBalance: 500.0);

        $this->service->applyJournalLineToCurrentBalance($payable, debit: 120.0, credit: 0.0);

        $this->assertBalance($payable, 380.0);
    }

    #[Test]
    public function liability_account_increases_on_credit(): void
    {
        $payable = $this->makeAccount(Account::TYPE_LIABILITY, openingBalance: 100.0);

        $this->service->applyJournalLineToCurrentBalance($payable, debit: 0.0, credit: 40.0);

        $this->assertBalance($payable, 140.0);
    }

    #[Test]
    public function revenue_account_increases_on_credit(): void
    {
        $revenue = $this->makeAccount(Account::TYPE_REVENUE, openingBalance: 0.0);

        $this->service->applyJournalLineToCurrentBalance($revenue, debit: 0.0, credit: 1000.0);

        $this->assertBalance($revenue, 1000.0);
    }

    #[Test]
    public function expense_account_increases_on_debit(): void
    {
        $expense = $this->makeAccount(Account::TYPE_EXPENSE, openingBalance: 0.0);

        $this->service->applyJournalLineToCurrentBalance($expense, debit: 250.0, credit: 0.0);

        $this->assertBalance($expense, 250.0);
    }

    #[Test]
    #[DataProvider('zeroMovementProvider')]
    public function zero_debit_and_credit_does_not_change_balance(float $opening): void
    {
        $account = $this->makeAccount(Account::TYPE_ASSET, openingBalance: $opening);

        $this->service->applyJournalLineToCurrentBalance($account, debit: 0.0, credit: 0.0);

        $this->assertBalance($account, $opening);
    }

    public static function zeroMovementProvider(): array
    {
        return [
            'zero opening' => [0.0],
            'non-zero opening' => [99.5],
        ];
    }

    #[Test]
    public function sync_journal_entry_balances_updates_all_linked_accounts(): void
    {
        $cash = $this->makeAccount(Account::TYPE_ASSET, openingBalance: 0.0, code: '1100');
        $revenue = $this->makeAccount(Account::TYPE_REVENUE, openingBalance: 0.0, code: '4100');

        $entry = JournalEntry::factory()
            ->forTenant($this->tenant)
            ->total(1000)
            ->create();

        JournalItem::factory()
            ->forTenant($this->tenant)
            ->create([
                'journal_entry_id' => $entry->id,
                'account_id' => $cash->id,
                'debit' => 1000,
                'credit' => 0,
            ]);

        JournalItem::factory()
            ->forTenant($this->tenant)
            ->create([
                'journal_entry_id' => $entry->id,
                'account_id' => $revenue->id,
                'debit' => 0,
                'credit' => 1000,
            ]);

        $this->service->syncJournalEntryBalances((int) $entry->id);

        $this->assertBalance($cash, 1000.0);
        $this->assertBalance($revenue, 1000.0);
    }

    #[Test]
    public function sync_journal_entry_balances_is_additive_when_called_on_existing_opening(): void
    {
        $cash = $this->makeAccount(Account::TYPE_ASSET, openingBalance: 300.0);

        $entry = JournalEntry::factory()
            ->forTenant($this->tenant)
            ->total(100)
            ->create();

        JournalItem::factory()
            ->forTenant($this->tenant)
            ->create([
                'journal_entry_id' => $entry->id,
                'account_id' => $cash->id,
                'debit' => 100,
                'credit' => 0,
            ]);

        JournalItem::factory()
            ->forTenant($this->tenant)
            ->create([
                'journal_entry_id' => $entry->id,
                'account_id' => $this->makeAccount(Account::TYPE_REVENUE, code: '4999')->id,
                'debit' => 0,
                'credit' => 100,
            ]);

        $this->service->syncJournalEntryBalances((int) $entry->id);

        $this->assertBalance($cash, 400.0);
    }
}
