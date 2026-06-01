<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Sales;

use App\Models\CompanySetting;
use App\Models\CrmActivity;
use App\Models\Customer;
use App\Models\InventoryTransaction;
use App\Models\Item;
use App\Models\ItemWarehouse;
use App\Models\JournalEntry;
use App\Models\JournalItem;
use App\Models\SalesInvoice;
use App\Models\SalesInvoiceItem;
use App\Models\Warehouse;
use App\Services\Sales\SalesInvoicePostingService;
use App\Support\DefaultLedgerAccounts;
use App\Support\Invoicing\InvoiceOrderLinkGuard;
use PHPUnit\Framework\Attributes\Test;
use RuntimeException;
use Tests\Support\AccountingTestCase;

final class SalesInvoicePostingServiceTest extends AccountingTestCase
{
    private SalesInvoicePostingService $service;

    private Customer $customer;

    private Warehouse $warehouse;

    private Item $finishedItem;

    private Item $rawItem;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(SalesInvoicePostingService::class);

        DefaultLedgerAccounts::cashOnHand();
        DefaultLedgerAccounts::accountsReceivable();
        DefaultLedgerAccounts::salesRevenue();
        DefaultLedgerAccounts::vatPayable();
        DefaultLedgerAccounts::ensureCogsRoot();
        DefaultLedgerAccounts::inventoryRawMaterials();
        DefaultLedgerAccounts::inventoryFinishedGoods();

        $this->customer = Customer::withoutGlobalScopes()->create([
            'user_id' => $this->tenant->id,
            'code' => 'CUST-0001',
            'name' => 'Test Customer',
            'is_active' => true,
        ]);

        $this->warehouse = Warehouse::factory()->forTenant($this->tenant)->create();

        $this->finishedItem = Item::withoutGlobalScopes()->create([
            'user_id' => $this->tenant->id,
            'code' => 'FG-001',
            'name_ar' => 'Finished Item',
            'type' => Item::TYPE_FINISHED_GOOD,
            'cost' => 40,
            'current_stock' => 10,
            'is_active' => true,
        ]);

        $this->rawItem = Item::withoutGlobalScopes()->create([
            'user_id' => $this->tenant->id,
            'code' => 'RM-001',
            'name_ar' => 'Raw Item',
            'type' => Item::TYPE_RAW_MATERIAL,
            'cost' => 15,
            'current_stock' => 20,
            'is_active' => true,
        ]);

        foreach ([$this->finishedItem, $this->rawItem] as $item) {
            ItemWarehouse::withoutGlobalScopes()->create([
                'user_id' => $this->tenant->id,
                'item_id' => $item->id,
                'warehouse_id' => $this->warehouse->id,
                'quantity' => (float) $item->current_stock,
                'reserved_quantity' => 0,
            ]);
        }

        CompanySetting::withoutGlobalScopes()->create([
            'user_id' => $this->tenant->id,
            'default_vat_percent' => 15,
        ]);
    }

    #[Test]
    public function create_and_post_reduces_stock_and_creates_revenue_and_cogs_journals(): void
    {
        $invoice = $this->service->createAndPost($this->tenant->id, [
            'customer_id' => $this->customer->id,
            'warehouse_id' => $this->warehouse->id,
            'date' => '2026-05-30',
            'due_date' => '2026-06-30',
            'reference' => 'SINV-TEST-1',
            'payment_method' => SalesInvoice::PAYMENT_CREDIT,
            'posting_source' => SalesInvoice::POSTING_SOURCE_DIRECT,
        ], [
            [
                'item_id' => $this->finishedItem->id,
                'quantity' => 2,
                'unit_price' => 100,
                'tax_percent' => 15,
            ],
            [
                'item_id' => $this->rawItem->id,
                'quantity' => 3,
                'unit_price' => 50,
                'tax_percent' => 15,
            ],
        ]);

        $invoice->refresh()->load(['items', 'journalEntry', 'cogsJournalEntry']);

        $this->assertTrue($invoice->isPosted());
        $this->assertTrue($invoice->isInventoryPosted());
        $this->assertSame(SalesInvoice::STATUS_UNPAID, $invoice->status);
        $this->assertNotNull($invoice->journal_entry_id);
        $this->assertNotNull($invoice->cogs_journal_entry_id);

        $fgPivot = ItemWarehouse::withoutGlobalScopes()
            ->where('item_id', $this->finishedItem->id)
            ->where('warehouse_id', $this->warehouse->id)
            ->first();
        $this->assertEqualsWithDelta(8, (float) $fgPivot->quantity, 0.0001);

        $rmPivot = ItemWarehouse::withoutGlobalScopes()
            ->where('item_id', $this->rawItem->id)
            ->where('warehouse_id', $this->warehouse->id)
            ->first();
        $this->assertEqualsWithDelta(17, (float) $rmPivot->quantity, 0.0001);

        $this->assertSame(2, InventoryTransaction::withoutGlobalScopes()->count());

        $fgLine = $invoice->items->firstWhere('item_id', $this->finishedItem->id);
        $rmLine = $invoice->items->firstWhere('item_id', $this->rawItem->id);
        $this->assertEqualsWithDelta(40, (float) $fgLine->unit_cost, 0.0001);
        $this->assertEqualsWithDelta(15, (float) $rmLine->unit_cost, 0.0001);

        $expectedCogs = round(2 * 40 + 3 * 15, 4);
        $cogsDebit = (float) JournalItem::query()
            ->where('journal_entry_id', $invoice->cogs_journal_entry_id)
            ->sum('debit');
        $this->assertEqualsWithDelta($expectedCogs, $cogsDebit, 0.0001);

        $this->assertDatabaseHas('crm_activities', [
            'customer_id' => $this->customer->id,
            'sales_invoice_id' => $invoice->id,
            'type' => CrmActivity::TYPE_SALES_INVOICE,
        ]);
    }

    #[Test]
    public function cash_sale_marks_invoice_as_paid(): void
    {
        $invoice = $this->service->createAndPost($this->tenant->id, [
            'customer_id' => $this->customer->id,
            'warehouse_id' => $this->warehouse->id,
            'date' => '2026-05-30',
            'due_date' => '2026-05-30',
            'payment_method' => SalesInvoice::PAYMENT_CASH,
            'posting_source' => SalesInvoice::POSTING_SOURCE_DIRECT,
        ], [
            [
                'item_id' => $this->finishedItem->id,
                'quantity' => 1,
                'unit_price' => 115,
                'tax_percent' => 15,
            ],
        ]);

        $invoice->refresh();

        $this->assertSame(SalesInvoice::STATUS_PAID, $invoice->status);
        $this->assertEqualsWithDelta((float) $invoice->total, (float) $invoice->paid_amount, 0.0001);
    }

    #[Test]
    public function rejects_insufficient_stock(): void
    {
        $this->expectException(RuntimeException::class);

        $this->service->createAndPost($this->tenant->id, [
            'customer_id' => $this->customer->id,
            'warehouse_id' => $this->warehouse->id,
            'date' => '2026-05-30',
            'due_date' => '2026-06-30',
            'payment_method' => SalesInvoice::PAYMENT_CREDIT,
            'posting_source' => SalesInvoice::POSTING_SOURCE_DIRECT,
        ], [
            [
                'item_id' => $this->finishedItem->id,
                'quantity' => 999,
                'unit_price' => 100,
            ],
        ]);
    }

    #[Test]
    public function normalize_lines_accepts_absolute_discount_from_csv_import(): void
    {
        $lines = $this->service->normalizeLines($this->tenant->id, [
            [
                'item_id' => $this->finishedItem->id,
                'quantity' => 2,
                'unit_price' => 100,
                'discount' => 20,
                'tax_percent' => 15,
            ],
        ]);

        $this->assertCount(1, $lines);
        $this->assertEqualsWithDelta(20, (float) $lines[0]['discount'], 0.0001);
        $this->assertEqualsWithDelta(207, (float) $lines[0]['line_total'], 0.0001);
    }

    #[Test]
    public function post_existing_is_idempotent(): void
    {
        $invoice = SalesInvoice::withoutGlobalScopes()->create([
            'user_id' => $this->tenant->id,
            'customer_id' => $this->customer->id,
            'warehouse_id' => $this->warehouse->id,
            'posting_source' => SalesInvoice::POSTING_SOURCE_DIRECT,
            'date' => '2026-05-30',
            'due_date' => '2026-06-30',
            'payment_method' => SalesInvoice::PAYMENT_CREDIT,
            'subtotal' => 100,
            'vat_rate' => 15,
            'vat_amount' => 15,
            'total' => 115,
            'status' => SalesInvoice::STATUS_DRAFT,
            'invoice_status' => 'draft',
        ]);

        SalesInvoiceItem::withoutGlobalScopes()->create([
            'user_id' => $this->tenant->id,
            'sales_invoice_id' => $invoice->id,
            'item_id' => $this->finishedItem->id,
            'quantity' => 1,
            'unit_price' => 100,
            'discount' => 0,
            'vat_percent' => 15,
            'line_total' => 115,
        ]);

        $posted = $this->service->postExisting($invoice);
        $journalId = $posted->journal_entry_id;
        $cogsId = $posted->cogs_journal_entry_id;

        $again = $this->service->postExisting($posted);

        $this->assertSame($journalId, $again->journal_entry_id);
        $this->assertSame($cogsId, $again->cogs_journal_entry_id);
        $this->assertSame(1, JournalEntry::query()->whereKey($journalId)->count());
    }

    #[Test]
    public function rejects_stockable_invoice_without_order_link_when_not_direct(): void
    {
        $invoice = SalesInvoice::withoutGlobalScopes()->create([
            'user_id' => $this->tenant->id,
            'customer_id' => $this->customer->id,
            'warehouse_id' => $this->warehouse->id,
            'posting_source' => SalesInvoice::POSTING_SOURCE_ORDER,
            'date' => '2026-05-30',
            'due_date' => '2026-06-30',
            'payment_method' => SalesInvoice::PAYMENT_CREDIT,
            'subtotal' => 100,
            'vat_rate' => 15,
            'vat_amount' => 15,
            'total' => 115,
            'status' => SalesInvoice::STATUS_DRAFT,
            'invoice_status' => 'draft',
        ]);

        SalesInvoiceItem::withoutGlobalScopes()->create([
            'user_id' => $this->tenant->id,
            'sales_invoice_id' => $invoice->id,
            'item_id' => $this->finishedItem->id,
            'quantity' => 1,
            'unit_price' => 100,
            'discount' => 0,
            'vat_percent' => 15,
            'line_total' => 115,
        ]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(InvoiceOrderLinkGuard::ORDER_LINK_EXCEPTION);

        $this->service->postExisting($invoice->fresh(['items.item']));
    }
}
