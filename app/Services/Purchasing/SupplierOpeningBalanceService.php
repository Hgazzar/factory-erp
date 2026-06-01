<?php

declare(strict_types=1);

namespace App\Services\Purchasing;

use App\Models\Supplier;

/**
 * ترحيل الرصيد الافتتاحي للمورد (مرة واحدة).
 */
final class SupplierOpeningBalanceService
{
    public function __construct(
        private readonly PurchaseAccountingService $accounting,
    ) {}

    public function syncIfNeeded(Supplier $supplier): Supplier
    {
        $balance = (float) ($supplier->opening_balance ?? 0);

        if ($balance === 0.0 || $supplier->opening_balance_journal_entry_id) {
            return $supplier;
        }

        $journalId = $this->accounting->postSupplierOpeningBalance($supplier);

        if ($journalId !== null) {
            $supplier->forceFill(['opening_balance_journal_entry_id' => $journalId])->save();
        }

        return $supplier->fresh();
    }

    /**
     * الرصيد المستحق الحالي = افتتاحي + فواتير − مدفوعات − مرتجعات.
     */
    public function currentPayableBalance(Supplier $supplier): float
    {
        $opening = (float) ($supplier->opening_balance ?? 0);

        $invoices = (float) $supplier->purchaseInvoices()
            ->whereNotIn('status', ['draft'])
            ->sum('total');

        $paid = (float) $supplier->purchaseInvoices()
            ->whereNotIn('status', ['draft'])
            ->sum('paid_amount');

        return round($opening + $invoices - $paid, 4);
    }
}
