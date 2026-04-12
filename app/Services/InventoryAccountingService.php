<?php

namespace App\Services;

use App\Models\Account;
use App\Models\DeliveryOrder;
use App\Models\Item;
use App\Models\JournalEntry;
use App\Models\JournalItem;
use App\Models\ProductionOrder;

class InventoryAccountingService
{
    /**
     * إتمام الإنتاج: مدين مخزن المنتج التام، دائن مخزن الخامات (قيمة تكلفة الخامات المستهلكة).
     */
    public function createProductionCompletionEntry(ProductionOrder $order, float $materialsValue): ?JournalEntry
    {
        if ($materialsValue <= 0) {
            return null;
        }

        $rmId = $this->accountIdByCode(config('accounting.raw_materials_inventory_code'));
        $fgId = $this->accountIdByCode(config('accounting.finished_goods_inventory_code'));

        if (! $rmId || ! $fgId) {
            return null;
        }

        $amount = round($materialsValue, 4);
        $ref = $order->production_number ?? 'PO-'.$order->id;
        $desc = 'إتمام إنتاج — '.$ref;

        $journalUserId = (int) (auth()->id() ?? 1);

        $entry = JournalEntry::query()->create([
            'user_id' => $journalUserId,
            'reference' => $ref,
            'date' => now()->toDateString(),
            'description' => $desc,
            'total' => $amount,
        ]);

        JournalItem::query()->create([
            'journal_entry_id' => $entry->id,
            'account_id' => $fgId,
            'description' => 'زيادة مخزون منتج تام (إتمام إنتاج)',
            'debit' => $amount,
            'credit' => 0,
        ]);

        JournalItem::query()->create([
            'journal_entry_id' => $entry->id,
            'account_id' => $rmId,
            'description' => 'تخفيض مخزون خامات (استهلاك إنتاج)',
            'debit' => 0,
            'credit' => $amount,
        ]);

        return $entry;
    }

    /**
     * تأكيد التوريد: مدين تكلفة البضاعة المباعة، دائن مخزن المنتج التام و/أو مخزن الخامات.
     *
     * @param  array{cogs_total: float, credit_finished_goods: float, credit_raw_materials: float}  $split
     */
    public function createDeliveryCostEntry(DeliveryOrder $delivery, array $split): ?JournalEntry
    {
        $total = round((float) ($split['cogs_total'] ?? 0), 4);
        $creditFg = round((float) ($split['credit_finished_goods'] ?? 0), 4);
        $creditRm = round((float) ($split['credit_raw_materials'] ?? 0), 4);

        if ($total <= 0) {
            return null;
        }

        if (abs(($creditFg + $creditRm) - $total) > 0.0001) {
            return null;
        }

        $cogsId = $this->accountIdByCode(config('accounting.cogs_code'));
        $fgId = $this->accountIdByCode(config('accounting.finished_goods_inventory_code'));
        $rmId = $this->accountIdByCode(config('accounting.raw_materials_inventory_code'));

        if (! $cogsId) {
            return null;
        }

        if (($creditFg > 0 && ! $fgId) || ($creditRm > 0 && ! $rmId)) {
            return null;
        }

        $ref = $delivery->delivery_number ?? 'DO-'.$delivery->id;
        $desc = 'تأكيد توريد — '.$ref;

        $entry = JournalEntry::query()->create([
            'user_id' => (int) $delivery->user_id,
            'reference' => $ref,
            'date' => now()->toDateString(),
            'description' => $desc,
            'total' => $total,
        ]);

        JournalItem::query()->create([
            'journal_entry_id' => $entry->id,
            'account_id' => $cogsId,
            'description' => 'تكلفة بضاعة مباعة (توريد)',
            'debit' => $total,
            'credit' => 0,
        ]);

        if ($creditFg > 0) {
            JournalItem::query()->create([
                'journal_entry_id' => $entry->id,
                'account_id' => $fgId,
                'description' => 'خصم من مخزن المنتج التام',
                'debit' => 0,
                'credit' => $creditFg,
            ]);
        }

        if ($creditRm > 0) {
            JournalItem::query()->create([
                'journal_entry_id' => $entry->id,
                'account_id' => $rmId,
                'description' => 'خصم من مخزن الخامات',
                'debit' => 0,
                'credit' => $creditRm,
            ]);
        }

        return $entry;
    }

    /**
     * @param  \Illuminate\Support\Collection<int, \App\Models\DeliveryOrderItem>  $lines
     * @return array{cogs_total: float, credit_finished_goods: float, credit_raw_materials: float}
     */
    public function summarizeDeliveryLinesForCost($lines): array
    {
        $cogsTotal = 0.0;
        $creditFg = 0.0;
        $creditRm = 0.0;

        foreach ($lines as $line) {
            $item = $line->relationLoaded('item') ? $line->item : Item::query()->find($line->item_id);
            if (! $item) {
                continue;
            }

            if (! in_array($item->type, [Item::TYPE_RAW_MATERIAL, Item::TYPE_FINISHED_GOOD], true)) {
                continue;
            }

            $qty = (float) $line->quantity;
            $unitCost = (float) ($item->cost ?? 0);
            $lineCost = round($qty * $unitCost, 4);
            if ($lineCost <= 0) {
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

    private function accountIdByCode(string $code): ?int
    {
        return Account::query()->where('code', $code)->value('id');
    }
}
