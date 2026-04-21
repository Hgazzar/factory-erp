<?php

namespace App\Services;

use App\Models\Account;
use App\Models\Item;
use App\Models\JournalEntry;
use App\Models\JournalItem;
use App\Models\PurchaseOrder;
use App\Models\SalesOrder;
use InvalidArgumentException;

class FinancialRecordingService
{
    public function __construct(
        private readonly AccountingService $accounting,
    ) {
    }

    /**
     * ترحيل قيد متوازن (مدين = دائن).
     *
     * @param  array<int, array{account_id: int, debit: float|int|string, credit: float|int|string, description?: string|null, cost_center?: string|null}>  $lines
     */
    public function recordBalancedJournal(
        int $userId,
        string $date,
        ?string $reference,
        ?string $description,
        array $lines,
        ?int $createdByUserId = null,
    ): JournalEntry {
        $totalDebit = 0.0;
        $totalCredit = 0.0;
        foreach ($lines as $line) {
            $totalDebit += (float) ($line['debit'] ?? 0);
            $totalCredit += (float) ($line['credit'] ?? 0);
        }
        $totalDebit = round($totalDebit, 4);
        $totalCredit = round($totalCredit, 4);

        if ($totalDebit <= 0 || abs($totalDebit - $totalCredit) > 0.0001) {
            throw new InvalidArgumentException('يجب أن يكون إجمالي المدين مساوياً لإجمالي الدائن وأكبر من صفر.');
        }

        $entry = JournalEntry::query()->create([
            'user_id' => $userId,
            'created_by' => $createdByUserId ?? $userId,
            'reference' => $reference,
            'date' => $date,
            'description' => $description,
            'total' => $totalDebit,
        ]);

        foreach ($lines as $line) {
            $debit = round((float) ($line['debit'] ?? 0), 4);
            $credit = round((float) ($line['credit'] ?? 0), 4);
            if ($debit < 0 || $credit < 0 || ($debit > 0 && $credit > 0)) {
                continue;
            }
            if ($debit <= 0 && $credit <= 0) {
                continue;
            }

            JournalItem::query()->create([
                'journal_entry_id' => $entry->id,
                'user_id' => $userId,
                'account_id' => (int) $line['account_id'],
                'description' => $line['description'] ?? null,
                'cost_center' => $line['cost_center'] ?? null,
                'debit' => $debit,
                'credit' => $credit,
            ]);
        }

        $this->accounting->syncJournalEntryBalances((int) $entry->id);

        return $entry->fresh();
    }

    /**
     * قيد استلام أمر شراء: مدين مخزون (خامات/تام حسب البنود)، دائن ذمم دائنة بإجمالي الأمر.
     */
    public function recordPurchaseOrderReceipt(PurchaseOrder $order): JournalEntry
    {
        if ($order->journal_entry_id) {
            throw new InvalidArgumentException('تم ترحيل هذا الأمر مسبقاً إلى دفتر اليومية.');
        }

        $order->loadMissing(['items.item']);

        $uid = (int) $order->user_id;
        $apId = $this->accountIdForUser($uid, (string) config('accounting.accounts_payable_code'));
        if (! $apId) {
            throw new InvalidArgumentException('لم يُعثر على حساب الذمم الدائنة (كود '.config('accounting.accounts_payable_code').').');
        }

        $rmId = $this->accountIdForUser($uid, (string) config('accounting.raw_materials_inventory_code'));
        $fgId = $this->accountIdForUser($uid, (string) config('accounting.finished_goods_inventory_code'));

        $total = round((float) $order->total, 4);
        if ($total <= 0) {
            throw new InvalidArgumentException('إجمالي أمر الشراء يجب أن يكون أكبر من صفر لترحيل الاستلام.');
        }

        $rmWeight = 0.0;
        $fgWeight = 0.0;
        foreach ($order->items as $line) {
            $w = (float) ($line->line_total ?? 0);
            if ($w <= 0) {
                continue;
            }
            $item = $line->item;
            if ($item && $item->type === Item::TYPE_FINISHED_GOOD) {
                $fgWeight += $w;
            } else {
                $rmWeight += $w;
            }
        }

        $sumWeights = $rmWeight + $fgWeight;
        if ($sumWeights <= 0) {
            throw new InvalidArgumentException('لا توجد بنود بمبالغ صالحة لتوزيع قيد المخزون.');
        }

        $rmAmount = round($total * ($rmWeight / $sumWeights), 4);
        $fgAmount = round($total - $rmAmount, 4);

        $lines = [];
        if ($rmAmount > 0.0001) {
            if (! $rmId) {
                throw new InvalidArgumentException('لم يُعثر على حساب مخزون الخامات (كود '.config('accounting.raw_materials_inventory_code').').');
            }
            $lines[] = [
                'account_id' => $rmId,
                'debit' => $rmAmount,
                'credit' => 0,
                'description' => 'استلام مشتريات — مخزون خامات ومدخلات',
            ];
        }
        if ($fgAmount > 0.0001) {
            if (! $fgId) {
                throw new InvalidArgumentException('لم يُعثر على حساب مخزون المنتج التام (كود '.config('accounting.finished_goods_inventory_code').').');
            }
            $lines[] = [
                'account_id' => $fgId,
                'debit' => $fgAmount,
                'credit' => 0,
                'description' => 'استلام مشتريات — مخزون منتج تام',
            ];
        }

        if ($lines === []) {
            throw new InvalidArgumentException('تعذر بناء بنود المدين لقيد الاستلام.');
        }

        $ref = $order->order_number ?: ('PO-'.$order->id);
        $desc = 'استلام أمر شراء — '.$ref;

        $lines[] = [
            'account_id' => $apId,
            'debit' => 0,
            'credit' => $total,
            'description' => 'ذمة المورد — '.$ref,
        ];

        return $this->recordBalancedJournal(
            $uid,
            $order->order_date?->format('Y-m-d') ?? now()->toDateString(),
            $ref,
            $desc,
            $lines,
            (int) (auth()->id() ?? $uid)
        );
    }

    /**
     * إكمال أمر بيع محاسبياً: مدين ذمم مدينة أو نقدية بإجمالي الأمر، دائن إيراد مبيعات؛
     * وعند وجود تكلفة للبنود: مدين تكلفة مبيعات، دائن مخزون منتج تام.
     *
     * @param  'receivable'|'cash'  $settlement
     */
    public function recordSalesOrderCompletion(SalesOrder $order, string $settlement = 'receivable'): JournalEntry
    {
        if ($order->journal_entry_id) {
            throw new InvalidArgumentException('تم ترحيل هذا الأمر مسبقاً إلى دفتر اليومية.');
        }

        $settlement = $settlement === 'cash' ? 'cash' : 'receivable';
        $order->loadMissing(['items.item']);

        $uid = (int) $order->user_id;
        $total = round((float) $order->total, 4);
        if ($total <= 0) {
            throw new InvalidArgumentException('إجمالي أمر البيع يجب أن يكون أكبر من صفر لترحيل الإكمال.');
        }

        $revenueId = $this->accountIdForUser($uid, (string) config('accounting.sales_revenue_code'));
        if (! $revenueId) {
            throw new InvalidArgumentException('لم يُعثر على حساب إيراد المبيعات (كود '.config('accounting.sales_revenue_code').').');
        }

        if ($settlement === 'cash') {
            $assetId = $this->accountIdForUser($uid, (string) config('accounting.treasury_account_code'));
            if (! $assetId) {
                throw new InvalidArgumentException('لم يُعثر على حساب النقدية/الخزينة (كود '.config('accounting.treasury_account_code').').');
            }
        } else {
            $assetId = $this->accountIdForUser($uid, (string) config('accounting.accounts_receivable_code'));
            if (! $assetId) {
                throw new InvalidArgumentException('لم يُعثر على حساب الذمم المدينة (كود '.config('accounting.accounts_receivable_code').').');
            }
        }

        $cogsId = $this->accountIdForUser($uid, (string) config('accounting.cogs_code'));
        $fgId = $this->accountIdForUser($uid, (string) config('accounting.finished_goods_inventory_code'));

        $cogsTotal = 0.0;
        foreach ($order->items as $line) {
            $qty = (float) $line->quantity;
            $unitCost = (float) ($line->item?->cost ?? 0);
            if ($qty > 0 && $unitCost > 0) {
                $cogsTotal += $qty * $unitCost;
            }
        }
        $cogsTotal = round($cogsTotal, 4);

        $ref = $order->order_number ?: ('SO-'.$order->id);
        $desc = 'إكمال أمر بيع — '.$ref;

        $lines = [
            [
                'account_id' => $assetId,
                'debit' => $total,
                'credit' => 0,
                'description' => $settlement === 'cash' ? 'تحصيل نقدي — '.$ref : 'ذمة عميل — '.$ref,
            ],
            [
                'account_id' => $revenueId,
                'debit' => 0,
                'credit' => $total,
                'description' => 'إيراد مبيعات — '.$ref,
            ],
        ];

        if ($cogsTotal > 0.0001) {
            if (! $cogsId) {
                throw new InvalidArgumentException('لم يُعثر على حساب تكلفة المبيعات (كود '.config('accounting.cogs_code').').');
            }
            if (! $fgId) {
                throw new InvalidArgumentException('لم يُعثر على حساب مخزون المنتج التام (كود '.config('accounting.finished_goods_inventory_code').').');
            }
            $lines[] = [
                'account_id' => $cogsId,
                'debit' => $cogsTotal,
                'credit' => 0,
                'description' => 'تكلفة البضاعة المباعة — '.$ref,
            ];
            $lines[] = [
                'account_id' => $fgId,
                'debit' => 0,
                'credit' => $cogsTotal,
                'description' => 'تخفيض مخزون تام — '.$ref,
            ];
        }

        return $this->recordBalancedJournal(
            $uid,
            $order->order_date?->format('Y-m-d') ?? now()->toDateString(),
            $ref,
            $desc,
            $lines,
            (int) (auth()->id() ?? $uid)
        );
    }

    private function accountIdForUser(int $userId, string $code): ?int
    {
        return Account::withoutGlobalScopes()
            ->where('user_id', $userId)
            ->where('code', $code)
            ->value('id');
    }
}
