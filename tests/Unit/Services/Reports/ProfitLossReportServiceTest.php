<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Reports;

use App\Models\Account;
use App\Models\JournalEntry;
use App\Models\JournalItem;
use App\Services\Reports\ProfitLossReportService;
use App\Support\DefaultLedgerAccounts;
use PHPUnit\Framework\Attributes\Test;
use Tests\Support\AccountingTestCase;

final class ProfitLossReportServiceTest extends AccountingTestCase
{
    private ProfitLossReportService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(ProfitLossReportService::class);

        DefaultLedgerAccounts::salesRevenue();
        DefaultLedgerAccounts::ensureCogsRoot();
        DefaultLedgerAccounts::inventoryRawMaterials();
    }

    #[Test]
    public function generate_calculates_net_profit_from_journal_lines(): void
    {
        $sales = Account::withoutGlobalScopes()->where('user_id', $this->tenant->id)->where('code', '4000')->firstOrFail();
        $cogs = Account::withoutGlobalScopes()->where('user_id', $this->tenant->id)->where('code', '5000')->firstOrFail();
        $payroll = Account::withoutGlobalScopes()->create([
            'user_id' => $this->tenant->id,
            'code' => '6100',
            'name_ar' => 'رواتب',
            'type' => Account::TYPE_EXPENSE,
            'opening_balance' => 0,
            'current_balance' => 0,
            'is_active' => true,
        ]);
        $rent = Account::withoutGlobalScopes()->create([
            'user_id' => $this->tenant->id,
            'code' => '6200',
            'name_ar' => 'إيجار',
            'type' => Account::TYPE_EXPENSE,
            'opening_balance' => 0,
            'current_balance' => 0,
            'is_active' => true,
        ]);

        $entry = JournalEntry::withoutGlobalScopes()->create([
            'user_id' => $this->tenant->id,
            'date' => '2026-05-15',
            'reference' => 'PL-TEST',
            'description' => 'Test P&L entry',
            'total' => 1000,
        ]);

        JournalItem::withoutGlobalScopes()->create([
            'user_id' => $this->tenant->id,
            'journal_entry_id' => $entry->id,
            'account_id' => $sales->id,
            'debit' => 0,
            'credit' => 1000,
        ]);
        JournalItem::withoutGlobalScopes()->create([
            'user_id' => $this->tenant->id,
            'journal_entry_id' => $entry->id,
            'account_id' => $cogs->id,
            'debit' => 400,
            'credit' => 0,
        ]);
        JournalItem::withoutGlobalScopes()->create([
            'user_id' => $this->tenant->id,
            'journal_entry_id' => $entry->id,
            'account_id' => $payroll->id,
            'debit' => 200,
            'credit' => 0,
        ]);
        JournalItem::withoutGlobalScopes()->create([
            'user_id' => $this->tenant->id,
            'journal_entry_id' => $entry->id,
            'account_id' => $rent->id,
            'debit' => 100,
            'credit' => 0,
        ]);

        $report = $this->service->generate($this->tenant->id, '2026-05-01', '2026-05-31');

        $this->assertEqualsWithDelta(1000, $report['net_sales'], 0.0001);
        $this->assertEqualsWithDelta(400, $report['net_cogs'], 0.0001);
        $this->assertEqualsWithDelta(600, $report['gross_profit'], 0.0001);
        $this->assertEqualsWithDelta(200, $report['payroll_expense'], 0.0001);
        $this->assertEqualsWithDelta(100, $report['operating_expenses'], 0.0001);
        $this->assertEqualsWithDelta(300, $report['net_profit'], 0.0001);
    }
}
