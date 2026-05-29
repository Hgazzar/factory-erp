<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Accounting;

use App\Models\Account;
use App\Models\JournalEntry;
use App\Models\JournalItem;
use App\Services\FinancialRecordingService;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Test;
use Tests\Support\AccountingTestCase;

final class FinancialRecordingServiceTest extends AccountingTestCase
{
    private FinancialRecordingService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(FinancialRecordingService::class);
    }

    #[Test]
    public function it_rejects_journal_when_debit_and_credit_are_not_equal(): void
    {
        $debitAccount = $this->makeAccount(Account::TYPE_ASSET);
        $creditAccount = $this->makeAccount(Account::TYPE_REVENUE);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('يجب أن يكون إجمالي المدين مساوياً لإجمالي الدائن وأكبر من صفر.');

        $this->service->recordBalancedJournal(
            userId: (int) $this->tenant->id,
            date: now()->toDateString(),
            reference: 'UNBALANCED-001',
            description: 'قيد غير متوازن',
            lines: [
                ['account_id' => $debitAccount->id, 'debit' => 1000, 'credit' => 0],
                ['account_id' => $creditAccount->id, 'debit' => 0, 'credit' => 999],
            ],
        );
    }

    #[Test]
    public function it_rejects_journal_when_total_is_zero(): void
    {
        $account = $this->makeAccount(Account::TYPE_ASSET);

        $this->expectException(InvalidArgumentException::class);

        $this->service->recordBalancedJournal(
            userId: (int) $this->tenant->id,
            date: now()->toDateString(),
            reference: 'ZERO-001',
            description: 'قيد بصفر',
            lines: [
                ['account_id' => $account->id, 'debit' => 0, 'credit' => 0],
            ],
        );
    }

    #[Test]
    public function it_rejects_journal_when_only_one_side_has_amount_but_totals_mismatch(): void
    {
        $cash = $this->makeAccount(Account::TYPE_ASSET);

        $this->expectException(InvalidArgumentException::class);

        $this->service->recordBalancedJournal(
            userId: (int) $this->tenant->id,
            date: now()->toDateString(),
            reference: 'MISMATCH-001',
            description: null,
            lines: [
                ['account_id' => $cash->id, 'debit' => 500, 'credit' => 0],
                ['account_id' => $cash->id, 'debit' => 0, 'credit' => 0],
            ],
        );
    }

    #[Test]
    public function it_persists_balanced_journal_with_items(): void
    {
        $cash = $this->makeAccount(Account::TYPE_ASSET, code: '1010');
        $revenue = $this->makeAccount(Account::TYPE_REVENUE, code: '4000');

        $entry = $this->service->recordBalancedJournal(
            userId: (int) $this->tenant->id,
            date: '2026-05-01',
            reference: 'BAL-001',
            description: 'قيد متوازن',
            lines: [
                ['account_id' => $cash->id, 'debit' => 1500, 'credit' => 0, 'description' => 'تحصيل'],
                ['account_id' => $revenue->id, 'debit' => 0, 'credit' => 1500, 'description' => 'إيراد'],
            ],
            createdByUserId: (int) $this->tenant->id,
        );

        $this->assertInstanceOf(JournalEntry::class, $entry);
        $this->assertSame('BAL-001', $entry->reference);
        $this->assertSame('2026-05-01', $entry->date->toDateString());
        $this->assertEqualsWithDelta(1500.0, (float) $entry->total, 0.0001);

        $items = JournalItem::withoutGlobalScopes()
            ->where('journal_entry_id', $entry->id)
            ->orderBy('id')
            ->get();

        $this->assertCount(2, $items);
        $this->assertEqualsWithDelta(1500.0, (float) $items->sum('debit'), 0.0001);
        $this->assertEqualsWithDelta(1500.0, (float) $items->sum('credit'), 0.0001);
    }

    #[Test]
    public function it_updates_account_current_balances_after_balanced_journal(): void
    {
        $cash = $this->makeAccount(Account::TYPE_ASSET, openingBalance: 100.0, code: '1020');
        $revenue = $this->makeAccount(Account::TYPE_REVENUE, openingBalance: 0.0, code: '4010');

        $this->service->recordBalancedJournal(
            userId: (int) $this->tenant->id,
            date: now()->toDateString(),
            reference: 'BAL-002',
            description: 'قيد مع مزامنة أرصدة',
            lines: [
                ['account_id' => $cash->id, 'debit' => 400, 'credit' => 0],
                ['account_id' => $revenue->id, 'debit' => 0, 'credit' => 400],
            ],
        );

        $this->assertBalance($cash, 500.0);
        $this->assertBalance($revenue, 400.0);
    }

    #[Test]
    public function it_skips_lines_with_both_debit_and_credit_when_persisting(): void
    {
        $cash = $this->makeAccount(Account::TYPE_ASSET, code: '1030');
        $expense = $this->makeAccount(Account::TYPE_EXPENSE, code: '5030');
        $revenue = $this->makeAccount(Account::TYPE_REVENUE, code: '4020');

        $entry = $this->service->recordBalancedJournal(
            userId: (int) $this->tenant->id,
            date: now()->toDateString(),
            reference: 'BAL-003',
            description: 'تجاهل بند مدين ودائن معاً',
            lines: [
                ['account_id' => $cash->id, 'debit' => 200, 'credit' => 200],
                ['account_id' => $expense->id, 'debit' => 200, 'credit' => 0],
                ['account_id' => $revenue->id, 'debit' => 0, 'credit' => 200],
            ],
        );

        $persistedAccountIds = JournalItem::withoutGlobalScopes()
            ->where('journal_entry_id', $entry->id)
            ->pluck('account_id')
            ->all();

        $this->assertCount(2, $persistedAccountIds);
        $this->assertNotContains($cash->id, $persistedAccountIds);
        $this->assertBalance($cash, 0.0);
        $this->assertBalance($expense, 200.0);
        $this->assertBalance($revenue, 200.0);
    }
}
