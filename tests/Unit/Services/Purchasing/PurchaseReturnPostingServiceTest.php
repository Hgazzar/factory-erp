<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Purchasing;

use App\Models\CompanySetting;
use App\Models\InventoryTransaction;
use App\Models\Item;
use App\Models\ItemWarehouse;
use App\Models\JournalEntry;
use App\Models\PurchaseInvoice;
use App\Models\PurchaseInvoiceItem;
use App\Models\PurchaseReturn;
use App\Models\Supplier;
use App\Models\Warehouse;
use App\Services\Purchasing\PurchaseReturnPostingService;
use App\Support\DefaultLedgerAccounts;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Test;
use RuntimeException;
use Tests\Support\AccountingTestCase;

final class PurchaseReturnPostingServiceTest extends AccountingTestCase
{
    private PurchaseReturnPostingService $service;

    private Supplier $supplier;

    private Warehouse $warehouse;

    private Item $item;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(PurchaseReturnPostingService::class);

        DefaultLedgerAccounts::accountsPayable();
        DefaultLedgerAccounts::inventoryReceipts();
        DefaultLedgerAccounts::vatPayable();

        $this->supplier = Supplier::withoutGlobalScopes()->create([
            'user_id' => $this->tenant->id,
            'code' => 'SUP-0001',
            'name' => 'Test Supplier',
            'is_active' => true,
        ]);

        $this->warehouse = Warehouse::factory()->forTenant($this->tenant)->create();
        $this->item = Item::withoutGlobalScopes()->create([
            'user_id' => $this->tenant->id,
            'code' => 'RM-001',
            'name_ar' => 'Raw Item',
            'type' => 'raw',
            'cost' => 10,
            'current_stock' => 20,
            'is_active' => true,
        ]);

        ItemWarehouse::withoutGlobalScopes()->create([
            'user_id' => $this->tenant->id,
            'item_id' => $this->item->id,
            'warehouse_id' => $this->warehouse->id,
            'quantity' => 20,
            'reserved_quantity' => 0,
        ]);

        CompanySetting::withoutGlobalScopes()->create([
            'user_id' => $this->tenant->id,
            'default_vat_percent' => 15,
        ]);
    }

    #[Test]
    public function create_and_post_reduces_stock_and_creates_reverse_journal(): void
    {
        $purchaseReturn = $this->service->createAndPost($this->tenant->id, [
            'supplier_id' => $this->supplier->id,
            'warehouse_id' => $this->warehouse->id,
            'date' => '2026-06-01',
            'reason_type' => 'تالف',
            'reason' => 'بضاعة تالفة',
        ], [
            [
                'item_id' => $this->item->id,
                'quantity' => 5,
                'unit_price' => 100,
                'vat_percent' => 15,
            ],
        ]);

        $this->assertTrue($purchaseReturn->isPosted());
        $this->assertSame(PurchaseReturn::STATUS_COMPLETED, $purchaseReturn->status);
        $this->assertEqualsWithDelta(575.0, (float) $purchaseReturn->total, 0.0001);

        $pivotQty = (float) ItemWarehouse::withoutGlobalScopes()
            ->where('item_id', $this->item->id)
            ->where('warehouse_id', $this->warehouse->id)
            ->value('quantity');
        $this->assertEqualsWithDelta(15.0, $pivotQty, 0.0001);

        $this->assertSame(1, InventoryTransaction::withoutGlobalScopes()
            ->where('type', 'purchase_return_out')
            ->where('reference_id', $purchaseReturn->id)
            ->count());

        $entry = JournalEntry::withoutGlobalScopes()->findOrFail($purchaseReturn->journal_entry_id);
        $this->assertEqualsWithDelta(575.0, (float) $entry->total, 0.0001);

        $apId = (int) DefaultLedgerAccounts::accountsPayable()->id;
        $invId = (int) DefaultLedgerAccounts::inventoryReceipts()->id;
        $vatId = (int) DefaultLedgerAccounts::vatPayable()->id;

        $this->assertEqualsWithDelta(575.0, $this->journalLineAmount((int) $entry->id, $apId, 'debit'), 0.0001);
        $this->assertEqualsWithDelta(500.0, $this->journalLineAmount((int) $entry->id, $invId, 'credit'), 0.0001);
        $this->assertEqualsWithDelta(75.0, $this->journalLineAmount((int) $entry->id, $vatId, 'credit'), 0.0001);
    }

    private function postedInvoice(array $overrides = []): PurchaseInvoice
    {
        $entry = JournalEntry::withoutGlobalScopes()->create([
            'user_id' => $this->tenant->id,
            'date' => '2026-05-01',
            'reference' => 'PI-J',
            'total' => 1000,
        ]);

        return PurchaseInvoice::withoutGlobalScopes()->create(array_merge([
            'user_id' => $this->tenant->id,
            'supplier_id' => $this->supplier->id,
            'warehouse_id' => $this->warehouse->id,
            'date' => '2026-05-01',
            'reference' => 'PI-'.fake()->unique()->numerify('####'),
            'subtotal' => 1000,
            'vat_amount' => 150,
            'total' => 1150,
            'paid_amount' => 0,
            'status' => PurchaseInvoice::STATUS_UNPAID,
            'posted_at' => now(),
            'journal_entry_id' => $entry->id,
        ], $overrides));
    }

    #[Test]
    public function return_linked_to_invoice_reduces_invoice_total(): void
    {
        $invoice = $this->postedInvoice();

        $invoiceLine = PurchaseInvoiceItem::withoutGlobalScopes()->create([
            'user_id' => $this->tenant->id,
            'purchase_invoice_id' => $invoice->id,
            'item_id' => $this->item->id,
            'quantity' => 10,
            'unit_price' => 100,
            'discount' => 0,
            'vat_percent' => 15,
            'weighted_unit_cost' => 100,
            'line_total' => 1150,
        ]);

        $this->service->createAndPost($this->tenant->id, [
            'supplier_id' => $this->supplier->id,
            'purchase_invoice_id' => $invoice->id,
            'warehouse_id' => $this->warehouse->id,
            'date' => '2026-06-02',
            'reason_type' => 'عدم المطابقة',
        ], [
            [
                'item_id' => $this->item->id,
                'purchase_invoice_item_id' => $invoiceLine->id,
                'quantity' => 2,
                'unit_price' => 100,
                'vat_percent' => 15,
            ],
        ]);

        $fresh = PurchaseInvoice::withoutGlobalScopes()->findOrFail($invoice->id);
        $this->assertEqualsWithDelta(920.0, (float) $fresh->total, 0.0001);
        $this->assertEqualsWithDelta(800.0, (float) $fresh->subtotal, 0.0001);
        $this->assertEqualsWithDelta(120.0, (float) $fresh->vat_amount, 0.0001);
    }

    #[Test]
    public function return_rejects_quantity_exceeding_invoice_balance(): void
    {
        $invoice = $this->postedInvoice([
            'subtotal' => 500,
            'vat_amount' => 75,
            'total' => 575,
        ]);

        PurchaseInvoiceItem::withoutGlobalScopes()->create([
            'user_id' => $this->tenant->id,
            'purchase_invoice_id' => $invoice->id,
            'item_id' => $this->item->id,
            'quantity' => 5,
            'unit_price' => 100,
            'vat_percent' => 15,
            'line_total' => 575,
        ]);

        $this->expectException(InvalidArgumentException::class);

        $this->service->createAndPost($this->tenant->id, [
            'supplier_id' => $this->supplier->id,
            'purchase_invoice_id' => $invoice->id,
            'warehouse_id' => $this->warehouse->id,
            'date' => '2026-06-03',
            'reason_type' => 'تالف',
        ], [
            [
                'item_id' => $this->item->id,
                'quantity' => 10,
                'unit_price' => 100,
                'vat_percent' => 15,
            ],
        ]);
    }

    #[Test]
    public function return_rejects_insufficient_warehouse_stock(): void
    {
        $this->expectException(RuntimeException::class);

        $this->service->createAndPost($this->tenant->id, [
            'supplier_id' => $this->supplier->id,
            'warehouse_id' => $this->warehouse->id,
            'date' => '2026-06-04',
            'reason_type' => 'تالف',
        ], [
            [
                'item_id' => $this->item->id,
                'quantity' => 100,
                'unit_price' => 50,
                'vat_percent' => 15,
            ],
        ]);
    }

    private function journalLineAmount(int $journalEntryId, int $accountId, string $side): float
    {
        $column = $side === 'debit' ? 'debit' : 'credit';

        return (float) \App\Models\JournalItem::withoutGlobalScopes()
            ->where('journal_entry_id', $journalEntryId)
            ->where('account_id', $accountId)
            ->value($column);
    }
}
