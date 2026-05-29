<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Inventory;

use App\Models\Account;
use App\Models\DeliveryOrder;
use App\Models\DeliveryOrderItem;
use App\Models\Item;
use App\Models\JournalEntry;
use App\Models\ProductionOrder;
use App\Models\User;
use App\Services\InventoryAccountingService;
use PHPUnit\Framework\Attributes\Test;
use Tests\Support\InventoryAccountingTestCase;

final class InventoryAccountingServiceTest extends InventoryAccountingTestCase
{
    private InventoryAccountingService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(InventoryAccountingService::class);
    }

    #[Test]
    public function production_completion_entry_is_balanced_and_transfers_value_from_raw_to_finished_goods(): void
    {
        $order = ProductionOrder::query()->create([
            'production_number' => 'PO-1001',
            'status' => ProductionOrder::STATUS_IN_PROGRESS,
        ]);

        $materialsValue = 1250.50;

        $entry = $this->service->createProductionCompletionEntry($order, $materialsValue);

        $this->assertInstanceOf(JournalEntry::class, $entry);
        $this->assertJournalIsBalanced($entry, $materialsValue);
        $this->assertEqualsWithDelta(
            $materialsValue,
            $this->journalLineAmount($entry, $this->ledger['fg'], 'debit'),
            0.0001,
            'Finished goods inventory should be debited'
        );
        $this->assertEqualsWithDelta(
            $materialsValue,
            $this->journalLineAmount($entry, $this->ledger['rm'], 'credit'),
            0.0001,
            'Raw materials inventory should be credited (release consumed materials)'
        );
        $this->assertJournalBelongsToTenant($entry, $this->tenant);
    }

    #[Test]
    public function summarize_delivery_lines_uses_current_weighted_average_cost(): void
    {
        $finished = Item::factory()->forTenant($this->tenant)->finishedGood()->create(['cost' => 75.0]);
        $raw = Item::factory()->forTenant($this->tenant)->rawMaterial()->create(['cost' => 20.0]);

        $fgLine = new DeliveryOrderItem([
            'item_id' => $finished->id,
            'quantity' => 4,
        ]);
        $fgLine->setRelation('item', $finished);

        $rmLine = new DeliveryOrderItem([
            'item_id' => $raw->id,
            'quantity' => 10,
        ]);
        $rmLine->setRelation('item', $raw);

        $split = $this->service->summarizeDeliveryLinesForCost(collect([$fgLine, $rmLine]));

        $expectedFgCost = round(4 * 75.0, 4);
        $expectedRmCost = round(10 * 20.0, 4);
        $expectedTotal = round($expectedFgCost + $expectedRmCost, 4);

        $this->assertEqualsWithDelta($expectedTotal, $split['cogs_total'], 0.0001);
        $this->assertEqualsWithDelta($expectedFgCost, $split['credit_finished_goods'], 0.0001);
        $this->assertEqualsWithDelta($expectedRmCost, $split['credit_raw_materials'], 0.0001);
    }

    #[Test]
    public function delivery_cost_entry_matches_summarized_inventory_value(): void
    {
        $finished = Item::factory()->forTenant($this->tenant)->finishedGood()->create(['cost' => 60.0]);
        $raw = Item::factory()->forTenant($this->tenant)->rawMaterial()->create(['cost' => 15.0]);

        $delivery = DeliveryOrder::query()->create([
            'user_id' => $this->tenant->id,
            'delivery_number' => 'DO-9001',
            'status' => DeliveryOrder::STATUS_PENDING,
        ]);

        $fgLine = DeliveryOrderItem::query()->create([
            'delivery_order_id' => $delivery->id,
            'item_id' => $finished->id,
            'quantity' => 5,
        ]);
        $fgLine->setRelation('item', $finished);

        $rmLine = DeliveryOrderItem::query()->create([
            'delivery_order_id' => $delivery->id,
            'item_id' => $raw->id,
            'quantity' => 8,
        ]);
        $rmLine->setRelation('item', $raw);

        $split = $this->service->summarizeDeliveryLinesForCost(collect([$fgLine, $rmLine]));
        $inventoryCost = $split['cogs_total'];

        $entry = $this->service->createDeliveryCostEntry($delivery, $split);

        $this->assertInstanceOf(JournalEntry::class, $entry);
        $this->assertJournalIsBalanced($entry, $inventoryCost);
        $this->assertEqualsWithDelta(
            $inventoryCost,
            $this->journalLineAmount($entry, $this->ledger['cogs'], 'debit'),
            0.0001,
            'COGS debit must equal inventory cost at WAC'
        );
        $this->assertEqualsWithDelta(
            $split['credit_finished_goods'],
            $this->journalLineAmount($entry, $this->ledger['fg'], 'credit'),
            0.0001
        );
        $this->assertEqualsWithDelta(
            $split['credit_raw_materials'],
            $this->journalLineAmount($entry, $this->ledger['rm'], 'credit'),
            0.0001
        );
    }

    #[Test]
    public function journal_total_equals_quantity_times_wac_for_each_delivery_line(): void
    {
        $item = Item::factory()->forTenant($this->tenant)->finishedGood()->create(['cost' => 33.3333]);

        $line = new DeliveryOrderItem([
            'item_id' => $item->id,
            'quantity' => 3,
        ]);
        $line->setRelation('item', $item);

        $split = $this->service->summarizeDeliveryLinesForCost(collect([$line]));
        $lineInventoryValue = round(3 * 33.3333, 4);

        $delivery = DeliveryOrder::query()->create([
            'user_id' => $this->tenant->id,
            'delivery_number' => 'DO-9002',
            'status' => DeliveryOrder::STATUS_PENDING,
        ]);

        $entry = $this->service->createDeliveryCostEntry($delivery, $split);

        $this->assertEqualsWithDelta($lineInventoryValue, $split['cogs_total'], 0.0001);
        $this->assertEqualsWithDelta($lineInventoryValue, (float) $entry->total, 0.0001);
    }

    #[Test]
    public function production_journal_amount_matches_materials_consumption_value(): void
    {
        $raw = Item::factory()->forTenant($this->tenant)->rawMaterial()->create(['cost' => 12.5]);
        $qtyConsumed = 40.0;
        $materialsValue = round($qtyConsumed * 12.5, 4);

        $order = ProductionOrder::query()->create([
            'production_number' => 'PO-2002',
            'status' => ProductionOrder::STATUS_IN_PROGRESS,
        ]);

        $entry = $this->service->createProductionCompletionEntry($order, $materialsValue);

        $this->assertEqualsWithDelta($materialsValue, (float) $entry->total, 0.0001);
        $this->assertEqualsWithDelta(
            $materialsValue,
            $this->journalLineAmount($entry, $this->ledger['fg'], 'debit'),
            0.0001
        );
    }

    #[Test]
    public function tenant_isolation_posts_only_to_authenticated_tenant_accounts(): void
    {
        $otherTenant = User::factory()->create(['role' => 'admin']);
        $otherLedger = $this->seedStandardInventoryAccounts($otherTenant);

        $this->assertNotSame($this->ledger['fg']->id, $otherLedger['fg']->id);

        $delivery = DeliveryOrder::query()->create([
            'user_id' => $this->tenant->id,
            'delivery_number' => 'DO-TENANT',
            'status' => DeliveryOrder::STATUS_PENDING,
        ]);

        $item = Item::factory()->forTenant($this->tenant)->finishedGood()->create(['cost' => 100.0]);
        $line = DeliveryOrderItem::query()->create([
            'delivery_order_id' => $delivery->id,
            'item_id' => $item->id,
            'quantity' => 2,
        ]);
        $line->setRelation('item', $item);

        $split = $this->service->summarizeDeliveryLinesForCost(collect([$line]));
        $entry = $this->service->createDeliveryCostEntry($delivery, $split);

        $this->assertJournalBelongsToTenant($entry, $this->tenant);
        $this->assertEqualsWithDelta(
            200.0,
            $this->journalLineAmount($entry, $this->ledger['cogs'], 'debit'),
            0.0001
        );
        $this->assertEqualsWithDelta(
            0.0,
            $this->journalLineAmount($entry, $otherLedger['cogs'], 'debit'),
            0.0001
        );
    }

    #[Test]
    public function delivery_journal_uses_delivery_owner_as_tenant(): void
    {
        $delivery = DeliveryOrder::query()->create([
            'user_id' => $this->tenant->id,
            'delivery_number' => 'DO-OWNER',
            'status' => DeliveryOrder::STATUS_PENDING,
        ]);

        $item = Item::factory()->forTenant($this->tenant)->finishedGood()->create(['cost' => 50.0]);
        $line = DeliveryOrderItem::query()->create([
            'delivery_order_id' => $delivery->id,
            'item_id' => $item->id,
            'quantity' => 1,
        ]);
        $line->setRelation('item', $item);

        $split = $this->service->summarizeDeliveryLinesForCost(collect([$line]));
        $entry = $this->service->createDeliveryCostEntry($delivery, $split);

        $this->assertSame((int) $this->tenant->id, (int) JournalEntry::withoutGlobalScopes()->find($entry->id)->user_id);
    }

    #[Test]
    public function production_completion_returns_null_when_inventory_accounts_are_missing(): void
    {
        Account::withoutGlobalScopes()
            ->where('user_id', $this->tenant->id)
            ->whereIn('code', [
                config('accounting.raw_materials_inventory_code'),
                config('accounting.finished_goods_inventory_code'),
            ])
            ->delete();

        $order = ProductionOrder::query()->create([
            'production_number' => 'PO-EMPTY',
            'status' => ProductionOrder::STATUS_PENDING,
        ]);

        $this->assertNull($this->service->createProductionCompletionEntry($order, 500.0));
    }
}
