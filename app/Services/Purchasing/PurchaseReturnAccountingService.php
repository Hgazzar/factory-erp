<?php

declare(strict_types=1);

namespace App\Services\Purchasing;

use App\Models\Account;
use App\Models\Item;
use App\Models\PurchaseReturn;
use App\Models\Supplier;
use App\Services\FinancialRecordingService;
use App\Support\DefaultLedgerAccounts;
use InvalidArgumentException;
use RuntimeException;

/**
 * قيد عكسي لمرتجع المشتريات: مدين AP / دائن مخزون + ضريبة.
 */
final class PurchaseReturnAccountingService
{
    public function __construct(
        private readonly FinancialRecordingService $financial,
        private readonly PurchaseAccountingService $purchaseAccounting,
    ) {}

    public function postPurchaseReturn(PurchaseReturn $purchaseReturn): int
    {
        if ($purchaseReturn->journal_entry_id) {
            throw new RuntimeException('تم ترحيل المرتجع محاسبياً مسبقاً.');
        }

        $purchaseReturn->loadMissing(['items.item', 'supplier']);

        $tenantUserId = (int) $purchaseReturn->user_id;
        $grandTotal = round((float) $purchaseReturn->total, 4);
        $vatAmount = round((float) ($purchaseReturn->vat_amount ?? 0), 4);
        $netTotal = round($grandTotal - $vatAmount, 4);

        if ($grandTotal <= 0) {
            throw new InvalidArgumentException('إجمالي المرتجع يجب أن يكون أكبر من صفر.');
        }

        $rmWeight = 0.0;
        $fgWeight = 0.0;

        foreach ($purchaseReturn->items as $line) {
            $netLine = $line->netLineAmount();
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

        $payableId = $this->purchaseAccounting->resolvePayableAccountId($tenantUserId, $purchaseReturn->supplier);

        $journalLines = [
            [
                'account_id' => $payableId,
                'debit' => $grandTotal,
                'credit' => 0,
                'description' => 'تخفيض ذمة المورد — مرتجع '.($purchaseReturn->code ?: '#'.$purchaseReturn->id),
            ],
        ];

        if ($rmAmount > 0.0001) {
            $journalLines[] = [
                'account_id' => $rmId ?: $fallbackInventoryId,
                'debit' => 0,
                'credit' => $rmAmount,
                'description' => 'مرتجع مشتريات — مخزون خامات',
            ];
        }

        if ($fgAmount > 0.0001) {
            $journalLines[] = [
                'account_id' => $fgId ?: $fallbackInventoryId,
                'debit' => 0,
                'credit' => $fgAmount,
                'description' => 'مرتجع مشتريات — مخزون منتج تام',
            ];
        }

        if ($vatAmount > 0.0001) {
            $journalLines[] = [
                'account_id' => (int) DefaultLedgerAccounts::vatPayable()->id,
                'debit' => 0,
                'credit' => $vatAmount,
                'description' => 'عكس ضريبة قيمة مضافة — مرتجع مشتريات',
            ];
        }

        $ref = $purchaseReturn->code ?: ('PRET-'.$purchaseReturn->id);
        $desc = 'مرتجع مشتريات — '.$ref;

        $entry = $this->financial->recordBalancedJournal(
            $tenantUserId,
            $purchaseReturn->date?->format('Y-m-d') ?? now()->toDateString(),
            $ref,
            $desc,
            $journalLines,
            (int) (auth()->id() ?? $tenantUserId),
        );

        return (int) $entry->id;
    }

    private function accountIdForUser(int $userId, string $code): ?int
    {
        return Account::withoutGlobalScopes()
            ->where('user_id', $userId)
            ->where('code', $code)
            ->value('id');
    }
}
