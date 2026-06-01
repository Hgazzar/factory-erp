<?php

declare(strict_types=1);

namespace App\Services\Sales;

use App\Models\Account;
use App\Models\Customer;
use App\Models\Item;
use App\Models\SalesInvoice;
use App\Services\FinancialRecordingService;
use App\Services\InvoicePaymentRecordingService;
use App\Support\DefaultLedgerAccounts;
use InvalidArgumentException;
use RuntimeException;

/**
 * قيود محاسبية لفواتير المبيعات: إيراد + ضريبة + COGS.
 */
final class SalesAccountingService
{
    public function __construct(
        private readonly FinancialRecordingService $financial,
        private readonly InvoicePaymentRecordingService $payments,
    ) {}

    /**
     * مدين نقد/ذمم / دائن مبيعات + ضريبة.
     */
    public function postRevenueJournal(SalesInvoice $invoice): int
    {
        if ($invoice->journal_entry_id) {
            throw new RuntimeException('تم ترحيل الفاتورة محاسبياً مسبقاً.');
        }

        $invoice->loadMissing(['items', 'customer']);

        $tenantUserId = (int) $invoice->user_id;
        $grandTotal = round((float) $invoice->total, 4);
        $vatAmount = round((float) ($invoice->vat_amount ?? 0), 4);
        $netTotal = round($grandTotal - $vatAmount, 4);

        if ($grandTotal <= 0) {
            throw new InvalidArgumentException('إجمالي الفاتورة يجب أن يكون أكبر من صفر.');
        }

        $debitAccountId = $invoice->isCashSale()
            ? (int) DefaultLedgerAccounts::cashOnHand()->id
            : $this->resolveReceivableAccountId($tenantUserId, $invoice->customer);

        $salesAccountId = (int) DefaultLedgerAccounts::salesRevenue()->id;

        $journalLines = [
            [
                'account_id' => $debitAccountId,
                'debit' => $grandTotal,
                'credit' => 0,
                'description' => $invoice->isCashSale()
                    ? 'تحصيل نقدي من المبيعات'
                    : 'مبيعات آجل — '.($invoice->reference ?: 'SINV-'.$invoice->id),
            ],
            [
                'account_id' => $salesAccountId,
                'debit' => 0,
                'credit' => $netTotal,
                'description' => 'إيراد مبيعات',
            ],
        ];

        if ($vatAmount > 0.0001) {
            $journalLines[] = [
                'account_id' => (int) DefaultLedgerAccounts::vatPayable()->id,
                'debit' => 0,
                'credit' => $vatAmount,
                'description' => 'ضريبة قيمة مضافة على المبيعات',
            ];
        }

        $ref = $invoice->reference ?: ('SINV-'.$invoice->id);
        $desc = 'فاتورة مبيعات — '.$ref;

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

    /**
     * مدين COGS / دائن مخزون خامات و/أو منتج تام.
     */
    public function postCogsJournal(SalesInvoice $invoice): ?int
    {
        if ($invoice->cogs_journal_entry_id) {
            throw new RuntimeException('تم ترحيل قيد COGS مسبقاً.');
        }

        $invoice->loadMissing(['items.item']);
        $split = $this->summarizeCogsSplit($invoice);
        $total = round((float) ($split['cogs_total'] ?? 0), 4);

        if ($total <= 0.0001) {
            return null;
        }

        $tenantUserId = (int) $invoice->user_id;
        $creditFg = round((float) ($split['credit_finished_goods'] ?? 0), 4);
        $creditRm = round((float) ($split['credit_raw_materials'] ?? 0), 4);

        $cogsId = $this->accountIdForUser($tenantUserId, (string) config('accounting.cogs_code'));
        $fgId = $this->accountIdForUser($tenantUserId, (string) config('accounting.finished_goods_inventory_code'));
        $rmId = $this->accountIdForUser($tenantUserId, (string) config('accounting.raw_materials_inventory_code'));

        if (! $cogsId) {
            throw new InvalidArgumentException('لم يُعثر على حساب تكلفة المبيعات (كود '.config('accounting.cogs_code').').');
        }

        if (($creditFg > 0.0001 && ! $fgId) || ($creditRm > 0.0001 && ! $rmId)) {
            throw new InvalidArgumentException('تعذر تحديد حسابات مخزون خامات/منتج تام لقيد COGS.');
        }

        $journalLines = [
            [
                'account_id' => $cogsId,
                'debit' => $total,
                'credit' => 0,
                'description' => 'تكلفة البضاعة المباعة',
            ],
        ];

        if ($creditFg > 0.0001) {
            $journalLines[] = [
                'account_id' => $fgId,
                'debit' => 0,
                'credit' => $creditFg,
                'description' => 'خصم من مخزن المنتج التام',
            ];
        }

        if ($creditRm > 0.0001) {
            $journalLines[] = [
                'account_id' => $rmId,
                'debit' => 0,
                'credit' => $creditRm,
                'description' => 'خصم من مخزن الخامات',
            ];
        }

        $ref = ($invoice->reference ?: ('SINV-'.$invoice->id)).'-COGS';
        $desc = 'تكلفة بضاعة مباعة — '.($invoice->reference ?: 'SINV-'.$invoice->id);

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

    public function resolveReceivableAccountId(int $tenantUserId, ?Customer $customer = null): int
    {
        if ($customer?->receivable_ledger_account_id) {
            return (int) $customer->receivable_ledger_account_id;
        }

        return (int) $this->payments->resolveDefaultReceivableAccount($tenantUserId)->id;
    }

    /**
     * @return array{cogs_total: float, credit_finished_goods: float, credit_raw_materials: float}
     */
    public function summarizeCogsSplit(SalesInvoice $invoice): array
    {
        $cogsTotal = 0.0;
        $creditFg = 0.0;
        $creditRm = 0.0;

        foreach ($invoice->items as $line) {
            $item = $line->relationLoaded('item') ? $line->item : Item::query()->find($line->item_id);
            if ($item === null || $item->type === Item::TYPE_SERVICE) {
                continue;
            }

            if (! in_array($item->type, [Item::TYPE_RAW_MATERIAL, Item::TYPE_FINISHED_GOOD], true)) {
                continue;
            }

            $lineCost = $line->cogsLineAmount();
            if ($lineCost <= 0.0001) {
                continue;
            }

            $cogsTotal += $lineCost;
            if ($item->type === Item::TYPE_FINISHED_GOOD) {
                $creditFg += $lineCost;
            } else {
                $creditRm += $lineCost;
            }
        }

        return [
            'cogs_total' => round($cogsTotal, 4),
            'credit_finished_goods' => round($creditFg, 4),
            'credit_raw_materials' => round($creditRm, 4),
        ];
    }

    private function accountIdForUser(int $userId, string $code): ?int
    {
        return Account::withoutGlobalScopes()
            ->where('user_id', $userId)
            ->where('code', $code)
            ->value('id');
    }
}
