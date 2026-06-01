<?php

declare(strict_types=1);

namespace App\Support\Invoicing;

use App\Models\Item;
use App\Models\PurchaseInvoice;
use App\Models\SalesInvoice;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use RuntimeException;

/**
 * يفرض ربط الفاتورة بأمر بيع/شراء عند وجود بنود مخزنية (ما لم يكن المصدر مباشراً أو معفى).
 */
final class InvoiceOrderLinkGuard
{
    public const ORDER_LINK_EXCEPTION = 'لا يمكن ترحيل الفاتورة دون ربطها بطلب مسبق';

    public const INVENTORY_ALREADY_POSTED = 'تم ترحيل مخزون هذه الفاتورة مسبقاً.';

    public static function assertSalesInvoiceMayPost(SalesInvoice $invoice): void
    {
        if ($invoice->isInventoryPosted()) {
            throw new RuntimeException(self::INVENTORY_ALREADY_POSTED);
        }

        if (! self::invoiceHasStockableLines($invoice)) {
            return;
        }

        if (self::isSalesPostingExempt($invoice)) {
            return;
        }

        if ($invoice->sales_order_id === null) {
            throw new RuntimeException(self::ORDER_LINK_EXCEPTION);
        }
    }

    public static function assertPurchaseInvoiceMayPost(PurchaseInvoice $invoice): void
    {
        if ($invoice->isInventoryPosted()) {
            throw new RuntimeException(self::INVENTORY_ALREADY_POSTED);
        }

        if (! self::invoiceHasStockableLines($invoice)) {
            return;
        }

        if (self::isPurchasePostingExempt($invoice)) {
            return;
        }

        if ($invoice->purchase_order_id === null) {
            throw new RuntimeException(self::ORDER_LINK_EXCEPTION);
        }
    }

    public static function invoiceHasStockableLines(Model $invoice): bool
    {
        $invoice->loadMissing('items.item');

        /** @var Collection<int, mixed> $items */
        $items = $invoice->items;

        foreach ($items as $line) {
            $item = $line->item ?? null;
            if ($item !== null && Item::isStockableType((string) $item->type)) {
                return true;
            }
        }

        return false;
    }

    private static function isSalesPostingExempt(SalesInvoice $invoice): bool
    {
        if ($invoice->posting_source === SalesInvoice::POSTING_SOURCE_DIRECT) {
            return true;
        }

        if ($invoice->contract_id !== null || $invoice->service_order_id !== null) {
            return true;
        }

        return false;
    }

    private static function isPurchasePostingExempt(PurchaseInvoice $invoice): bool
    {
        return $invoice->posting_source === PurchaseInvoice::POSTING_SOURCE_DIRECT;
    }
}
