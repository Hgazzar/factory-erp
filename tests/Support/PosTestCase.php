<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Models\Account;
use App\Models\CompanySetting;
use App\Models\Item;
use App\Models\JournalEntry;
use App\Models\JournalItem;
use App\Models\PosDevice;
use App\Models\PosSale;
use App\Models\PosSaleLine;
use App\Models\Warehouse;
use App\Support\DefaultLedgerAccounts;

abstract class PosTestCase extends InvoicePaymentTestCase
{
    protected Account $salesAccount;

    protected Account $vatAccount;

    protected Account $cogsAccount;

    protected Account $fgInventoryAccount;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedPosLedgerAccounts();
    }

    protected function seedPosLedgerAccounts(): void
    {
        $this->salesAccount = Account::factory()->forTenant($this->tenant)->revenue()->create([
            'code' => DefaultLedgerAccounts::CODE_SALES_REVENUE,
            'name_ar' => 'إيرادات المبيعات',
        ]);
        $this->vatAccount = Account::factory()->forTenant($this->tenant)->liability()->create([
            'code' => DefaultLedgerAccounts::CODE_VAT_PAYABLE,
            'name_ar' => 'ضريبة القيمة المضافة المستحقة',
        ]);
        $this->cogsAccount = Account::factory()->forTenant($this->tenant)->expense()->create([
            'code' => DefaultLedgerAccounts::CODE_COGS,
            'name_ar' => 'تكلفة البضاعة المباعة',
        ]);
        $this->fgInventoryAccount = Account::factory()->forTenant($this->tenant)->asset()->create([
            'code' => DefaultLedgerAccounts::CODE_FINISHED_GOODS_INV,
            'name_ar' => 'مخزون منتج تام',
        ]);
    }

    protected function setTenantVatPercent(float $percent): void
    {
        CompanySetting::query()->updateOrCreate(
            ['user_id' => $this->tenant->id],
            ['default_vat_percent' => $percent],
        );
    }

    protected function makePosDevice(?Warehouse $warehouse = null): PosDevice
    {
        $warehouse ??= Warehouse::factory()->forTenant($this->tenant)->create();

        return PosDevice::withoutGlobalScopes()->create([
            'user_id' => $this->tenant->id,
            'name' => 'POS Terminal',
            'mac_address' => fake()->unique()->macAddress(),
            'status' => PosDevice::STATUS_ACTIVE,
            'warehouse_id' => $warehouse->id,
        ]);
    }

    /**
     * @param  list<array{item: Item, quantity: float, unit_price: float, unit_cost: float}>  $lines
     */
    protected function makeCompletedPosSale(
        array $lines,
        string $paymentMethod = PosSale::PAYMENT_CASH,
        ?PosDevice $device = null,
    ): PosSale {
        $device ??= $this->makePosDevice();

        $total = 0.0;
        foreach ($lines as $line) {
            $total += round((float) $line['quantity'] * (float) $line['unit_price'], 4);
        }

        $sale = PosSale::withoutGlobalScopes()->create([
            'user_id' => $this->tenant->id,
            'pos_device_id' => $device->id,
            'receipt_number' => 'POS-T-'.fake()->unique()->numerify('######'),
            'total_price' => round($total, 4),
            'payment_method' => $paymentMethod,
            'status' => PosSale::STATUS_COMPLETED,
        ]);

        foreach ($lines as $line) {
            $qty = (float) $line['quantity'];
            $unitPrice = (float) $line['unit_price'];
            $unitCost = (float) $line['unit_cost'];

            PosSaleLine::query()->create([
                'pos_sale_id' => $sale->id,
                'item_id' => $line['item']->id,
                'quantity' => $qty,
                'unit_price' => $unitPrice,
                'line_total' => round($qty * $unitPrice, 4),
                'unit_cost' => $unitCost,
            ]);
        }

        return $sale->fresh()->load('lines');
    }

    protected function assertJournalIsBalanced(JournalEntry $entry): void
    {
        $debit = (float) JournalItem::withoutGlobalScopes()
            ->where('journal_entry_id', $entry->id)
            ->sum('debit');
        $credit = (float) JournalItem::withoutGlobalScopes()
            ->where('journal_entry_id', $entry->id)
            ->sum('credit');

        $this->assertEqualsWithDelta($debit, $credit, 0.0001, 'Journal debit/credit mismatch');
        $this->assertEqualsWithDelta($debit, (float) $entry->total, 0.0001, 'Journal header total mismatch');
        $this->assertGreaterThan(0, $debit);
    }

    protected function lineGrossProfit(float $quantity, float $unitPrice, float $unitCost): float
    {
        $revenue = round($quantity * $unitPrice, 4);
        $cost = round($quantity * $unitCost, 4);

        return round($revenue - $cost, 4);
    }

    protected function inclusiveVatSplit(float $grossTotal, float $vatPercent): array
    {
        $netRev = round($grossTotal / (1 + $vatPercent / 100), 4);
        $vatAmt = round($grossTotal - $netRev, 4);

        return ['net' => $netRev, 'vat' => $vatAmt];
    }
}
