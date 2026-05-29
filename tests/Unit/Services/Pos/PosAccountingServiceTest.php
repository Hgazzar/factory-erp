<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Pos;

use App\Models\Item;
use App\Models\PosSale;
use App\Services\PosAccountingService;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Test;
use Tests\Support\PosTestCase;

final class PosAccountingServiceTest extends PosTestCase
{
    private PosAccountingService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(PosAccountingService::class);
    }

    #[Test]
    public function cash_sale_without_vat_posts_balanced_journal_and_increases_cash(): void
    {
        $this->setTenantVatPercent(0);

        $item = Item::factory()->forTenant($this->tenant)->finishedGood()->create(['cost' => 30]);
        $sale = $this->makeCompletedPosSale([
            ['item' => $item, 'quantity' => 2, 'unit_price' => 50, 'unit_cost' => 30],
        ]);

        $gross = 100.0;
        $cogs = 60.0;

        $entry = $this->service->recordJournalForPosSale($sale);

        $this->assertJournalIsBalanced($entry);
        $this->assertEqualsWithDelta($gross, $this->journalLineAmount((int) $entry->id, $this->cashAccount->id, 'debit'), 0.0001);
        $this->assertEqualsWithDelta($gross, $this->journalLineAmount((int) $entry->id, $this->salesAccount->id, 'credit'), 0.0001);
        $this->assertEqualsWithDelta($cogs, $this->journalLineAmount((int) $entry->id, $this->cogsAccount->id, 'debit'), 0.0001);
        $this->assertEqualsWithDelta($cogs, $this->journalLineAmount((int) $entry->id, $this->fgInventoryAccount->id, 'credit'), 0.0001);
        $this->assertEqualsWithDelta(0.0, $this->journalLineAmount((int) $entry->id, $this->vatAccount->id, 'credit'), 0.0001);
        $this->assertBalance($this->cashAccount, $gross);
        $this->assertNotNull($sale->fresh()->journal_entry_id);
    }

    #[Test]
    public function taxable_inclusive_sale_splits_net_revenue_and_vat_payable(): void
    {
        $this->setTenantVatPercent(15);

        $item = Item::factory()->forTenant($this->tenant)->finishedGood()->create(['cost' => 20]);
        $sale = $this->makeCompletedPosSale([
            ['item' => $item, 'quantity' => 1, 'unit_price' => 115, 'unit_cost' => 20],
        ]);

        $gross = 115.0;
        ['net' => $netRev, 'vat' => $vatAmt] = $this->inclusiveVatSplit($gross, 15);

        $entry = $this->service->recordJournalForPosSale($sale);

        $this->assertJournalIsBalanced($entry);
        $this->assertEqualsWithDelta($gross, $this->journalLineAmount((int) $entry->id, $this->cashAccount->id, 'debit'), 0.0001);
        $this->assertEqualsWithDelta($netRev, $this->journalLineAmount((int) $entry->id, $this->salesAccount->id, 'credit'), 0.0001);
        $this->assertEqualsWithDelta($vatAmt, $this->journalLineAmount((int) $entry->id, $this->vatAccount->id, 'credit'), 0.0001);
        $this->assertEqualsWithDelta(20.0, $this->journalLineAmount((int) $entry->id, $this->cogsAccount->id, 'debit'), 0.0001);
        $this->assertBalance($this->cashAccount, $gross);
        $this->assertBalance($this->vatAccount, $vatAmt);
    }

    #[Test]
    public function discounted_sale_reduces_treasury_and_revenue_but_not_cogs(): void
    {
        $this->setTenantVatPercent(0);

        $item = Item::factory()->forTenant($this->tenant)->finishedGood()->create(['cost' => 50]);

        $fullPriceSale = $this->makeCompletedPosSale([
            ['item' => $item, 'quantity' => 1, 'unit_price' => 100, 'unit_cost' => 50],
        ]);
        $discountedSale = $this->makeCompletedPosSale([
            ['item' => $item, 'quantity' => 1, 'unit_price' => 80, 'unit_cost' => 50],
        ]);

        $this->service->recordJournalForPosSale($fullPriceSale);
        $this->service->recordJournalForPosSale($discountedSale);

        $this->assertBalance($this->cashAccount, 180.0);
        $this->assertBalance($this->salesAccount, 180.0);
        $this->assertBalance($this->cogsAccount, 100.0);
        $this->assertBalance($this->fgInventoryAccount, -100.0);
    }

    #[Test]
    public function card_sale_debits_bank_not_cash(): void
    {
        $this->setTenantVatPercent(0);

        $item = Item::factory()->forTenant($this->tenant)->finishedGood()->create(['cost' => 15]);
        $sale = $this->makeCompletedPosSale(
            [['item' => $item, 'quantity' => 2, 'unit_price' => 40, 'unit_cost' => 15]],
            PosSale::PAYMENT_CARD,
        );

        $gross = 80.0;

        $entry = $this->service->recordJournalForPosSale($sale);

        $this->assertEqualsWithDelta($gross, $this->journalLineAmount((int) $entry->id, $this->bankAccount->id, 'debit'), 0.0001);
        $this->assertEqualsWithDelta(0.0, $this->journalLineAmount((int) $entry->id, $this->cashAccount->id, 'debit'), 0.0001);
        $this->assertBalance($this->bankAccount, $gross);
        $this->assertBalance($this->cashAccount, 0.0);
    }

    #[Test]
    public function taxable_discounted_sale_still_balances_treasury_at_gross_total(): void
    {
        $this->setTenantVatPercent(15);

        $item = Item::factory()->forTenant($this->tenant)->finishedGood()->create(['cost' => 40]);
        $sale = $this->makeCompletedPosSale([
            ['item' => $item, 'quantity' => 1, 'unit_price' => 92, 'unit_cost' => 40],
        ]);

        $gross = 92.0;
        ['net' => $netRev, 'vat' => $vatAmt] = $this->inclusiveVatSplit($gross, 15);

        $entry = $this->service->recordJournalForPosSale($sale);

        $this->assertJournalIsBalanced($entry);
        $this->assertEqualsWithDelta($gross, $this->journalLineAmount((int) $entry->id, $this->cashAccount->id, 'debit'), 0.0001);
        $this->assertEqualsWithDelta($netRev, $this->journalLineAmount((int) $entry->id, $this->salesAccount->id, 'credit'), 0.0001);
        $this->assertEqualsWithDelta($vatAmt, $this->journalLineAmount((int) $entry->id, $this->vatAccount->id, 'credit'), 0.0001);
        $this->assertEqualsWithDelta(40.0, $this->journalLineAmount((int) $entry->id, $this->cogsAccount->id, 'debit'), 0.0001);
    }

    #[Test]
    public function it_rejects_duplicate_journal_posting(): void
    {
        $this->setTenantVatPercent(0);

        $item = Item::factory()->forTenant($this->tenant)->finishedGood()->create(['cost' => 10]);
        $sale = $this->makeCompletedPosSale([
            ['item' => $item, 'quantity' => 1, 'unit_price' => 25, 'unit_cost' => 10],
        ]);

        $this->service->recordJournalForPosSale($sale);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('تم ترحيل قيد محاسبي لهذا الإيصال مسبقاً');

        $this->service->recordJournalForPosSale($sale->fresh());
    }

    #[Test]
    public function it_rejects_sale_with_invalid_cogs(): void
    {
        $this->setTenantVatPercent(0);

        $item = Item::factory()->forTenant($this->tenant)->finishedGood()->create(['cost' => 0]);
        $sale = $this->makeCompletedPosSale([
            ['item' => $item, 'quantity' => 1, 'unit_price' => 50, 'unit_cost' => 0],
        ]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('تكلفة البضاعة المباعة غير صالحة');

        $this->service->recordJournalForPosSale($sale);
    }
}
