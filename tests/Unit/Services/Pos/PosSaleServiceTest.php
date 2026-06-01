<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Pos;

use App\Models\AuditLog;
use App\Models\JournalEntry;
use App\Models\JournalItem;
use App\Models\PosProduct;
use App\Models\PosProductBrand;
use App\Models\PosProductCategory;
use App\Models\PosSale;
use App\Services\Pos\PosSaleService;
use PHPUnit\Framework\Attributes\Test;
use Tests\Support\PosTestCase;

final class PosSaleServiceTest extends PosTestCase
{
    private PosSaleService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(PosSaleService::class);
    }

    #[Test]
    public function process_sale_deducts_stock_and_creates_balanced_journal(): void
    {
        $device = $this->makePosDevice();
        $category = PosProductCategory::query()->create([
            'user_id' => $this->tenant->id,
            'name' => 'مشروبات',
            'code' => 'BEV',
        ]);
        $brand = PosProductBrand::query()->create([
            'user_id' => $this->tenant->id,
            'name' => 'Akwad',
            'code' => 'AKW',
        ]);
        $product = PosProduct::query()->create([
            'user_id' => $this->tenant->id,
            'pos_product_category_id' => $category->id,
            'pos_product_brand_id' => $brand->id,
            'name' => 'مياه 600 مل',
            'sku' => 'W-600',
            'barcode' => '111222333',
            'cost_price' => 3.0,
            'sale_price' => 10.0,
            'vat_percent' => 15,
            'opening_quantity' => 50,
            'current_quantity' => 50,
            'low_stock_alert_quantity' => 5,
            'is_active' => true,
        ]);

        $sale = $this->service->processSale((int) $this->tenant->id, [
            'pos_device_id' => $device->id,
            'payment_method' => PosSale::PAYMENT_CASH,
            'lines' => [
                ['pos_product_id' => $product->id, 'quantity' => 2],
            ],
        ], (int) $this->tenant->id);

        $this->assertNotNull($sale->journal_entry_id);
        $this->assertSame('completed', $sale->status);
        $this->assertEqualsWithDelta(20.0, (float) $sale->subtotal_amount, 0.0001);
        $this->assertEqualsWithDelta(3.0, (float) $sale->vat_amount, 0.0001);
        $this->assertEqualsWithDelta(23.0, (float) $sale->total_amount, 0.0001);
        $this->assertEqualsWithDelta(6.0, (float) $sale->cogs_amount, 0.0001);

        $product->refresh();
        $this->assertEqualsWithDelta(48.0, (float) $product->current_quantity, 0.0001);

        $entry = JournalEntry::query()->findOrFail((int) $sale->journal_entry_id);
        $debit = (float) JournalItem::query()->where('journal_entry_id', $entry->id)->sum('debit');
        $credit = (float) JournalItem::query()->where('journal_entry_id', $entry->id)->sum('credit');
        $this->assertEqualsWithDelta($debit, $credit, 0.0001);
        $this->assertGreaterThan(0, $debit);
    }

    #[Test]
    public function process_sale_rejects_when_stock_is_insufficient(): void
    {
        $device = $this->makePosDevice();
        $product = PosProduct::query()->create([
            'user_id' => $this->tenant->id,
            'name' => 'عصير',
            'cost_price' => 2,
            'sale_price' => 8,
            'vat_percent' => 0,
            'opening_quantity' => 1,
            'current_quantity' => 1,
            'low_stock_alert_quantity' => 1,
            'is_active' => true,
        ]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('الكمية غير كافية');

        $this->service->processSale((int) $this->tenant->id, [
            'pos_device_id' => $device->id,
            'payment_method' => PosSale::PAYMENT_CASH,
            'lines' => [
                ['pos_product_id' => $product->id, 'quantity' => 5],
            ],
        ], (int) $this->tenant->id);
    }

    #[Test]
    public function cod_sale_debits_accounts_receivable(): void
    {
        $device = $this->makePosDevice();
        $product = PosProduct::query()->create([
            'user_id' => $this->tenant->id,
            'name' => 'Online item',
            'cost_price' => 5,
            'sale_price' => 20,
            'vat_percent' => 0,
            'opening_quantity' => 5,
            'current_quantity' => 5,
            'is_active' => true,
        ]);

        $sale = $this->service->processSale((int) $this->tenant->id, [
            'pos_device_id' => $device->id,
            'payment_method' => PosSale::PAYMENT_COD,
            'sale_channel' => PosSale::CHANNEL_ONLINE_STORE,
            'customer_name' => 'Test User',
            'lines' => [
                ['pos_product_id' => $product->id, 'quantity' => 1],
            ],
        ], (int) $this->tenant->id);

        $this->assertSame(PosSale::PAYMENT_COD, $sale->payment_method);
        $this->assertJournalIsBalanced($sale->journalEntry);
    }

    #[Test]
    public function split_payment_posts_two_asset_debits(): void
    {
        $device = $this->makePosDevice();
        $product = PosProduct::query()->create([
            'user_id' => $this->tenant->id,
            'name' => 'Snack',
            'cost_price' => 1,
            'sale_price' => 10,
            'vat_percent' => 0,
            'opening_quantity' => 10,
            'current_quantity' => 10,
            'low_stock_alert_quantity' => 2,
            'is_active' => true,
        ]);

        $sale = $this->service->processSale((int) $this->tenant->id, [
            'pos_device_id' => $device->id,
            'payment_method' => PosSale::PAYMENT_MIXED,
            'payment_splits' => [
                ['method' => PosSale::PAYMENT_CASH, 'amount' => 6.0],
                ['method' => PosSale::PAYMENT_CARD, 'amount' => 4.0],
            ],
            'lines' => [
                ['pos_product_id' => $product->id, 'quantity' => 1],
            ],
        ], (int) $this->tenant->id);

        $this->assertSame(PosSale::PAYMENT_MIXED, $sale->payment_method);
        $this->assertEqualsWithDelta(10.0, (float) $sale->total_amount, 0.0001);
    }

    #[Test]
    public function price_and_stock_adjustments_are_audited(): void
    {
        $product = PosProduct::query()->create([
            'user_id' => $this->tenant->id,
            'name' => 'Snack',
            'cost_price' => 1,
            'sale_price' => 5,
            'vat_percent' => 0,
            'opening_quantity' => 10,
            'current_quantity' => 10,
            'low_stock_alert_quantity' => 2,
            'is_active' => true,
        ]);

        $this->service->updateProductPrice($product, 6.5, 'offer update');
        $this->service->adjustStock($product->fresh(), -2, 'damaged items');

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'pos_product_price_changed',
            'subject_type' => PosProduct::class,
            'subject_id' => $product->id,
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'pos_product_stock_adjusted',
            'subject_type' => PosProduct::class,
            'subject_id' => $product->id,
        ]);
    }
}
