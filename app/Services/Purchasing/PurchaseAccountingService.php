<?php

declare(strict_types=1);

namespace App\Services\Purchasing;

use App\Models\Account;
use App\Models\Item;
use App\Models\PurchaseInvoice;
use App\Models\Supplier;
use App\Services\FinancialRecordingService;
use App\Services\InvoicePaymentRecordingService;
use App\Support\DefaultLedgerAccounts;
use InvalidArgumentException;
use RuntimeException;

/**
 * قيود محاسبية لفواتير المشتريات والرصيد الافتتاحي للموردين.
 */
final class PurchaseAccountingService
{
    public function __construct(
        private readonly FinancialRecordingService $financial,
        private readonly InvoicePaymentRecordingService $payments,
    ) {}

    /**
     * مدين مخزون (+ ضريبة) / دائن حساب المورد (AP).
     */
    public function postPurchaseInvoice(PurchaseInvoice $invoice): int
    {
        if ($invoice->journal_entry_id) {
            throw new RuntimeException('تم ترحيل الفاتورة محاسبياً مسبقاً.');
        }

        $invoice->loadMissing(['items.item', 'supplier']);

        $tenantUserId = (int) $invoice->user_id;
        $grandTotal = round((float) $invoice->total, 4);
        $vatAmount = round((float) ($invoice->vat_amount ?? 0), 4);
        $netTotal = round($grandTotal - $vatAmount, 4);

        if ($grandTotal <= 0) {
            throw new InvalidArgumentException('إجمالي الفاتورة يجب أن يكون أكبر من صفر.');
        }

        $rmWeight = 0.0;
        $fgWeight = 0.0;

        foreach ($invoice->items as $line) {
            $netLine = max(0, (float) $line->quantity * (float) $line->unit_price - (float) ($line->discount ?? 0));
            if ($netLine <= 0) {
                continue;
            }
            $item = $line->item;
            if ($item && $item->type === Item::TYPE_FINISHED_GOOD) {
                $fgWeight += $netLine;
            } else {
                $rmWeight += $netLine;
            }
        }

        $sumWeights = $rmWeight + $fgWeight;
        if ($sumWeights <= 0) {
            $rmWeight = $netTotal;
            $fgWeight = 0;
            $sumWeights = $netTotal;
        }

        $rmAmount = round($netTotal * ($rmWeight / $sumWeights), 4);
        $fgAmount = round($netTotal - $rmAmount, 4);

        $rmId = $this->accountIdForUser($tenantUserId, (string) config('accounting.raw_materials_inventory_code'));
        $fgId = $this->accountIdForUser($tenantUserId, (string) config('accounting.finished_goods_inventory_code'));
        $fallbackInventoryId = (int) DefaultLedgerAccounts::inventoryReceipts()->id;

        $journalLines = [];

        if ($rmAmount > 0.0001) {
            $journalLines[] = [
                'account_id' => $rmId ?: $fallbackInventoryId,
                'debit' => $rmAmount,
                'credit' => 0,
                'description' => 'مشتريات — مخزون خامات',
            ];
        }

        if ($fgAmount > 0.0001) {
            $journalLines[] = [
                'account_id' => $fgId ?: $fallbackInventoryId,
                'debit' => $fgAmount,
                'credit' => 0,
                'description' => 'مشتريات — مخزون منتج تام',
            ];
        }

        if ($vatAmount > 0.0001) {
            $journalLines[] = [
                'account_id' => (int) DefaultLedgerAccounts::vatPayable()->id,
                'debit' => $vatAmount,
                'credit' => 0,
                'description' => 'ضريبة قيمة مضافة على المشتريات',
            ];
        }

        if ($journalLines === []) {
            throw new InvalidArgumentException('تعذر بناء بنود المدين لقيد فاتورة المشتريات.');
        }

        $payableId = $this->resolvePayableAccountId($tenantUserId, $invoice->supplier);

        $journalLines[] = [
            'account_id' => $payableId,
            'debit' => 0,
            'credit' => $grandTotal,
            'description' => 'ذمة المورد — فاتورة '.($invoice->reference ?: '#'.$invoice->id),
        ];

        $ref = $invoice->reference ?: ('PINV-'.$invoice->id);
        $desc = 'فاتورة مشتريات — '.$ref;

        $entry = $this->financial->recordBalancedJournal(
            $tenantUserId,
            $invoice->date?->format('Y-m-d') ?? now()->toDateString(),
            $ref,
            $desc,
            $journalLines,
            (int) (auth()->id() ?? $tenantUserId),
        );

        return (int) $entry->id;
    }

    public function resolvePayableAccountId(int $tenantUserId, ?Supplier $supplier = null): int
    {
        if ($supplier?->payable_ledger_account_id) {
            return (int) $supplier->payable_ledger_account_id;
        }

        return (int) $this->payments->resolveDefaultPayableAccount($tenantUserId)->id;
    }

    private function accountIdForUser(int $userId, string $code): ?int
    {
        return Account::withoutGlobalScopes()
            ->where('user_id', $userId)
            ->where('code', $code)
            ->value('id');
    }
}
