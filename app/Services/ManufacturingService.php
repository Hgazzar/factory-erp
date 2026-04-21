<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Account;
use App\Models\Item;
use App\Models\JournalEntry;
use App\Models\ManufacturingRun;
use App\Models\ManufacturingRunLine;
use App\Support\DefaultLedgerAccounts;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use RuntimeException;

/**
 * أمر تصنيع: مسودة ثم ترحيل (مخزون عبر InventoryService، قيد عبر FinancialRecordingService → AccountingService).
 */
final class ManufacturingService
{
    public function __construct(
        private readonly FinancialRecordingService $financialRecording,
        private readonly InventoryService $inventory,
    ) {
    }

    /** كمية صرف من سطر BOM = (كمية/وحدة تام) × كمية الأمر × (1 + هدر٪). */
    public static function plannedConsumptionFromBomLine(float $qtyPerFgUnit, float $scrapPercent, float $workOrderQty): float
    {
        return round($qtyPerFgUnit * $workOrderQty * (1 + $scrapPercent / 100), 4);
    }

    /**
     * كمية الصرف من المخزن عند الترحيل: أساس صافٍ من الكمية المخططة (بعكس هدر المخطط)
     * ثم تطبيق هدر فعلي: نفس فكرة (مخطط + ما ينتج عن الهدر الفعلي٪) = صافي × (1 + هدر فعلي٪).
     */
    public static function inventoryWithdrawalQuantityForLine(ManufacturingRunLine $line): float
    {
        $planned = $line->planned_quantity;
        $pScrap = (float) ($line->planned_scrap_percent ?? 0);
        $aRaw = $line->actual_scrap_percent;
        $aScrap = $aRaw === null ? $pScrap : (float) $aRaw;

        if ($planned !== null && (float) $planned >= 0) {
            $divisor = 1 + ($pScrap / 100);
            $netBase = $divisor > 0.0000001
                ? (float) $planned / $divisor
                : (float) $planned;

            return round($netBase * (1 + $aScrap / 100), 4);
        }

        return round(max(0.0, (float) $line->quantity_consumed), 4);
    }

    /**
     * كمية صرف مخططة/فعلية من سطر BOM: (كمية لكل وحدة تام) × كمية أمر العمل × (1 + هدر٪).
     *
     * @param  array{
     *   production_date: string,
     *   start_date?: string|null,
     *   due_date?: string|null,
     *   warehouse_id: int,
     *   finished_item_id: int,
     *   bom_list_id?: int|null,
     *   machine_id?: int|null,
     *   quantity_produced: float|int|string,
     *   notes?: ?string,
     *   lines: list<array{
     *     ingredient_item_id: int,
     *     quantity: float|int|string,
     *     bom_list_line_id?: int|null,
     *     planned_quantity?: float|int|string|null,
     *     planned_scrap_percent?: float|int|string|null,
     *     actual_scrap_percent?: float|int|string|null,
     *   }>
     * }  $data
     */
    public function storeDraft(int $userId, array $data): ManufacturingRun
    {
        return DB::transaction(function () use ($userId, $data) {
            $run = ManufacturingRun::query()->create([
                'user_id' => $userId,
                'bom_list_id' => isset($data['bom_list_id']) ? (int) $data['bom_list_id'] : null,
                'reference' => null,
                'status' => ManufacturingRun::STATUS_DRAFT,
                'production_date' => $data['production_date'],
                'start_date' => $data['start_date'] ?? $data['production_date'],
                'due_date' => $data['due_date'] ?? null,
                'warehouse_id' => (int) $data['warehouse_id'],
                'machine_id' => isset($data['machine_id']) && $data['machine_id'] !== '' ? (int) $data['machine_id'] : null,
                'finished_item_id' => (int) $data['finished_item_id'],
                'quantity_produced' => (float) $data['quantity_produced'],
                'notes' => $data['notes'] ?? null,
            ]);
            $run->reference = 'MFG-'.$run->id;
            $run->save();

            foreach ($data['lines'] as $row) {
                $qty = (float) $row['quantity'];
                if ($qty <= 0) {
                    continue;
                }
                $planned = isset($row['planned_quantity']) && $row['planned_quantity'] !== '' && $row['planned_quantity'] !== null
                    ? (float) $row['planned_quantity']
                    : null;
                $plannedScrap = isset($row['planned_scrap_percent']) && $row['planned_scrap_percent'] !== '' && $row['planned_scrap_percent'] !== null
                    ? (float) $row['planned_scrap_percent']
                    : null;
                $actualScrap = array_key_exists('actual_scrap_percent', $row) && $row['actual_scrap_percent'] !== '' && $row['actual_scrap_percent'] !== null
                    ? (float) $row['actual_scrap_percent']
                    : null;
                ManufacturingRunLine::query()->create([
                    'manufacturing_run_id' => $run->id,
                    'bom_list_line_id' => isset($row['bom_list_line_id']) ? (int) $row['bom_list_line_id'] : null,
                    'ingredient_item_id' => (int) $row['ingredient_item_id'],
                    'warehouse_id' => (int) $data['warehouse_id'],
                    'planned_quantity' => $planned,
                    'planned_scrap_percent' => $plannedScrap,
                    'actual_scrap_percent' => $actualScrap,
                    'quantity_consumed' => $qty,
                ]);
            }

            $run->load('lines');
            if ($run->lines->isEmpty()) {
                throw new InvalidArgumentException('يجب إدخال مكوّن واحد على الأقل بكمية أكبر من صفر.');
            }

            return $run;
        });
    }

    public function post(ManufacturingRun $run, int $actingUserId): JournalEntry
    {
        return DB::transaction(function () use ($run, $actingUserId) {
            $userId = (int) $run->user_id;

            /** @var ManufacturingRun $locked */
            $locked = ManufacturingRun::withoutGlobalScopes()
                ->where('user_id', $userId)
                ->whereKey($run->id)
                ->lockForUpdate()
                ->firstOrFail();

            if (! $locked->isDraft()) {
                throw new InvalidArgumentException('تم ترحيل أمر العمل هذا مسبقاً أو أن حالته غير مسودة.');
            }

            $locked->load(['lines.ingredientItem', 'finishedItem', 'machine']);

            $finished = $locked->finishedItem;
            if (! $finished || $finished->type !== Item::TYPE_FINISHED_GOOD) {
                throw new InvalidArgumentException('يجب أن يكون المنتج النهائي من نوع منتج تام.');
            }

            $qtyOut = (float) $locked->quantity_produced;
            if ($qtyOut <= 0) {
                throw new InvalidArgumentException('كمية الإنتاج يجب أن تكون أكبر من صفر.');
            }

            $costLines = [];
            foreach ($locked->lines as $line) {
                $ing = $line->ingredientItem;
                if (! $ing) {
                    throw new InvalidArgumentException('صنف غير صالح في بنود التصنيع.');
                }
                if ((int) $ing->id === (int) $finished->id) {
                    throw new InvalidArgumentException('لا يمكن أن يكون المكوّن نفسه المنتج النهائي.');
                }
                if (! in_array($ing->type, [Item::TYPE_RAW_MATERIAL, Item::TYPE_FINISHED_GOOD], true)) {
                    throw new InvalidArgumentException('المكوّن «'.(string) ($ing->code ?? '').'» يجب أن يكون مادة خام أو منتج تام فرعي.');
                }
                $qtyInv = self::inventoryWithdrawalQuantityForLine($line);
                if ($qtyInv <= 0) {
                    continue;
                }
                $unitCost = (float) ($ing->cost ?? 0);
                $lineCost = round($qtyInv * $unitCost, 4);
                $costLines[] = [
                    'line' => $line,
                    'ingredient' => $ing,
                    'unit_cost' => $unitCost,
                    'line_cost' => $lineCost,
                    'qty_inventory' => $qtyInv,
                ];
            }

            if ($costLines === []) {
                throw new InvalidArgumentException('لا توجد كميات مواد للصرف.');
            }

            $totalMaterialsCost = round(array_sum(array_map(static fn (array $r): float => $r['line_cost'], $costLines)), 4);
            if ($totalMaterialsCost <= 0.0001) {
                throw new InvalidArgumentException('إجمالي تكلفة المواد يجب أن يكون أكبر من صفر (تحقق من تكاليف الأصناف).');
            }

            $depreciationAmount = round($this->machineDepreciationAmount($locked, $qtyOut), 4);

            foreach ($costLines as $row) {
                $this->inventory->consumeForManufacturing(
                    $row['ingredient'],
                    (int) $locked->warehouse_id,
                    (float) $row['qty_inventory'],
                    $locked
                );
            }

            $unitBatchCost = round(($totalMaterialsCost + $depreciationAmount) / $qtyOut, 4);

            $this->inventory->receiveManufacturingOutput(
                $finished,
                (int) $locked->warehouse_id,
                $qtyOut,
                $locked,
                $unitBatchCost
            );

            $rmAccountId = $this->accountIdForUser($userId, (string) config('accounting.raw_materials_inventory_code'));
            $fgAccountId = $this->accountIdForUser($userId, (string) config('accounting.finished_goods_inventory_code'));

            if (! $fgAccountId) {
                throw new InvalidArgumentException('لم يُعثر على حساب مخزون المنتج التام (كود '.config('accounting.finished_goods_inventory_code').').');
            }

            $rmCost = 0.0;
            $fgCompCost = 0.0;
            foreach ($costLines as $row) {
                $w = (float) $row['line_cost'];
                if ($w <= 0) {
                    continue;
                }
                if ($row['ingredient']->type === Item::TYPE_FINISHED_GOOD) {
                    $fgCompCost += $w;
                } else {
                    $rmCost += $w;
                }
            }
            $rmCost = round($rmCost, 4);
            $fgCompCost = round($fgCompCost, 4);

            $deprAccountId = $this->resolveMachineDepreciationAccountId($userId, $depreciationAmount);

            $fgDebitTotal = round($totalMaterialsCost + $depreciationAmount, 4);

            $journalLines = [
                [
                    'account_id' => $fgAccountId,
                    'debit' => $fgDebitTotal,
                    'credit' => 0,
                    'description' => 'تصنيع — ترصيد مخزون منتج تام ('.$locked->reference.')',
                ],
            ];

            if ($rmCost > 0.0001) {
                if (! $rmAccountId) {
                    throw new InvalidArgumentException('لم يُعثر على حساب مخزون الخامات (كود '.config('accounting.raw_materials_inventory_code').').');
                }
                $journalLines[] = [
                    'account_id' => $rmAccountId,
                    'debit' => 0,
                    'credit' => $rmCost,
                    'description' => 'تصنيع — تخفيض مخزون خامات (1041) ('.$locked->reference.')',
                ];
            }

            if ($fgCompCost > 0.0001) {
                $journalLines[] = [
                    'account_id' => $fgAccountId,
                    'debit' => 0,
                    'credit' => $fgCompCost,
                    'description' => 'تصنيع — تخفيض مخزون تام مُستهلَك كمدخل (1042) ('.$locked->reference.')',
                ];
            }

            if ($depreciationAmount > 0.0001) {
                if (! $deprAccountId) {
                    throw new InvalidArgumentException('لم يُعثر على حساب مجمع إهلاك الماكينات (كود '.config('accounting.machine_depreciation_accumulated_code').').');
                }
                $journalLines[] = [
                    'account_id' => $deprAccountId,
                    'debit' => 0,
                    'credit' => $depreciationAmount,
                    'description' => 'تصنيع — مجمع إهلاك ('.(string) config('accounting.machine_depreciation_accumulated_code').') ('.$locked->reference.')',
                ];
            }

            $creditSum = round($rmCost + $fgCompCost + $depreciationAmount, 4);
            if (abs($creditSum - $fgDebitTotal) > 0.0001) {
                throw new RuntimeException('تعذر موازنة قيد التصنيع (المدين/الدائن).');
            }

            $entry = $this->financialRecording->recordBalancedJournal(
                $userId,
                $locked->production_date->format('Y-m-d'),
                (string) $locked->reference,
                'تصنيع — '.($locked->reference ?? ''),
                $journalLines,
                $actingUserId,
            );

            foreach ($costLines as $row) {
                $row['line']->update([
                    'quantity_consumed' => $row['qty_inventory'],
                    'unit_cost_at_post' => $row['unit_cost'],
                    'line_cost' => $row['line_cost'],
                ]);
            }

            $locked->journal_entry_id = $entry->id;
            $locked->status = ManufacturingRun::STATUS_POSTED;
            $locked->total_materials_cost = $totalMaterialsCost;
            $locked->save();

            return $entry;
        });
    }

    private function machineDepreciationAmount(ManufacturingRun $run, float $quantityProduced): float
    {
        $run->loadMissing('machine');
        if (! $run->machine_id || ! $run->machine) {
            return 0.0;
        }
        $rate = (float) ($run->machine->depreciation_rate_per_unit ?? 0);
        if ($rate <= 0) {
            return 0.0;
        }

        return round($rate * $quantityProduced, 4);
    }

    private function resolveMachineDepreciationAccountId(int $userId, float $depreciationAmount): ?int
    {
        $code = (string) config('accounting.machine_depreciation_accumulated_code');
        $id = $this->accountIdForUser($userId, $code);
        if ($id) {
            return (int) $id;
        }
        if ($depreciationAmount <= 0.0001) {
            return null;
        }

        return (int) DefaultLedgerAccounts::accumulatedDepreciationAccount($userId)->id;
    }

    private function accountIdForUser(int $userId, string $code): ?int
    {
        return Account::withoutGlobalScopes()
            ->where('user_id', $userId)
            ->where('code', $code)
            ->value('id');
    }
}
