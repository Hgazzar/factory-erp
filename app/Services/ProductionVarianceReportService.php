<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\BomListLine;
use App\Models\ManufacturingRun;
use App\Models\ManufacturingRunLine;

/**
 * تجميع مؤشرات تقرير انحرافات الإنتاج لكل أمر عمل (ManufacturingRun).
 */
final class ProductionVarianceReportService
{
    private const SCRAP_ALERT_PP = 5.0;

    /**
     * @return array{
     *   run: ManufacturingRun,
     *   qty_planned_fg: float,
     *   qty_actual_fg: float|null,
     *   planned_material_qty_sum: float,
     *   actual_material_qty_sum: float|null,
     *   expected_scrap_weighted_pct: float|null,
     *   actual_scrap_weighted_pct: float|null,
     *   standard_cost: float|null,
     *   actual_cost: float|null,
     *   variance: float|null,
     *   scrap_alert: bool,
     * }
     */
    public static function summarize(ManufacturingRun $run): array
    {
        $run->loadMissing([
            'lines.ingredientItem',
            'bomList.lines',
            'machine',
        ]);

        $qtyFg = (float) $run->quantity_produced;
        $posted = $run->isPosted();

        $plannedMaterialSum = 0.0;
        $actualMaterialSum = 0.0;
        $weightScrapExpected = 0.0;
        $weightScrapActual = 0.0;
        $weightTotal = 0.0;

        foreach ($run->lines as $line) {
            $pq = self::resolvePlannedQuantity($run, $line, $qtyFg);
            $plannedMaterialSum += $pq;

            $plannedScrap = self::resolvePlannedScrapPercent($run, $line);
            $actualScrap = $line->actual_scrap_percent !== null && $line->actual_scrap_percent !== ''
                ? (float) $line->actual_scrap_percent
                : $plannedScrap;

            $w = max($pq, 0.0000001);
            $weightTotal += $w;
            $weightScrapExpected += $plannedScrap * $w;
            $weightScrapActual += $actualScrap * $w;

            if ($posted && $line->quantity_consumed !== null) {
                $actualMaterialSum += (float) $line->quantity_consumed;
            }
        }

        $expScrapPct = $weightTotal > 0.0000001 ? $weightScrapExpected / $weightTotal : null;
        $actScrapPct = $weightTotal > 0.0000001 ? $weightScrapActual / $weightTotal : null;

        $scrapAlert = $expScrapPct !== null && $actScrapPct !== null
            && $actScrapPct > $expScrapPct + self::SCRAP_ALERT_PP + 1e-9;

        $standardMat = self::standardMaterialsCost($run, $qtyFg);
        $standardLh = self::standardLaborOverhead($run, $qtyFg);

        $depr = self::depreciationAmount($run);
        $actualMat = $posted ? (float) ($run->total_materials_cost ?? 0) : 0.0;
        $actualTotal = $posted ? round($actualMat + $depr, 4) : null;
        /** معياري قابل للمقارنة مع المرحّل: مواد معيارية + إهلاك ماكينة (كما في القيد) */
        $standardComparable = $posted ? round($standardMat + $depr, 4) : null;

        $variance = ($posted && $standardComparable !== null && $actualTotal !== null)
            ? round($actualTotal - $standardComparable, 4)
            : null;

        return [
            'run' => $run,
            'qty_planned_fg' => $qtyFg,
            'qty_actual_fg' => $posted ? $qtyFg : null,
            'planned_material_qty_sum' => round($plannedMaterialSum, 4),
            'actual_material_qty_sum' => $posted ? round($actualMaterialSum, 4) : null,
            'expected_scrap_weighted_pct' => $expScrapPct !== null ? round($expScrapPct, 2) : null,
            'actual_scrap_weighted_pct' => $actScrapPct !== null ? round($actScrapPct, 2) : null,
            'standard_bom_labor_overhead' => round($standardLh, 4),
            'standard_cost' => $posted ? $standardComparable : null,
            'actual_cost' => $actualTotal,
            'variance' => $variance,
            'scrap_alert' => $scrapAlert,
        ];
    }

    private static function resolveBomLine(ManufacturingRun $run, ManufacturingRunLine $line): ?BomListLine
    {
        $bom = $run->bomList;
        if (! $bom) {
            return null;
        }
        if ($line->bom_list_line_id) {
            return $bom->lines->firstWhere('id', (int) $line->bom_list_line_id);
        }

        return $bom->lines->firstWhere('component_item_id', (int) $line->ingredient_item_id);
    }

    private static function resolvePlannedQuantity(ManufacturingRun $run, ManufacturingRunLine $line, float $qtyFg): float
    {
        if ($line->planned_quantity !== null && $line->planned_quantity !== '') {
            return (float) $line->planned_quantity;
        }
        $bl = self::resolveBomLine($run, $line);
        if ($bl) {
            return ManufacturingService::plannedConsumptionFromBomLine(
                (float) $bl->quantity,
                (float) $bl->scrap_percent,
                $qtyFg
            );
        }

        return 0.0;
    }

    private static function resolvePlannedScrapPercent(ManufacturingRun $run, ManufacturingRunLine $line): float
    {
        if ($line->planned_scrap_percent !== null && $line->planned_scrap_percent !== '') {
            return (float) $line->planned_scrap_percent;
        }
        $bl = self::resolveBomLine($run, $line);

        return $bl ? (float) $bl->scrap_percent : 0.0;
    }

    private static function standardMaterialsCost(ManufacturingRun $run, float $qtyFg): float
    {
        $sum = 0.0;
        foreach ($run->lines as $line) {
            $ing = $line->ingredientItem;
            if (! $ing) {
                continue;
            }
            $pq = self::resolvePlannedQuantity($run, $line, $qtyFg);
            $cost = (float) ($ing->cost ?? 0);
            $sum += $pq * $cost;
        }

        return round($sum, 4);
    }

    private static function standardLaborOverhead(ManufacturingRun $run, float $qtyFg): float
    {
        $bom = $run->bomList;
        if (! $bom) {
            return 0.0;
        }
        $labor = (float) ($bom->labor_cost ?? 0);
        $overhead = (float) ($bom->overhead_cost ?? 0);

        return round(($labor + $overhead) * $qtyFg, 4);
    }

    private static function depreciationAmount(ManufacturingRun $run): float
    {
        $run->loadMissing('machine');
        if (! $run->machine_id || ! $run->machine) {
            return 0.0;
        }
        $rate = (float) ($run->machine->depreciation_rate_per_unit ?? 0);
        if ($rate <= 0) {
            return 0.0;
        }

        return round($rate * (float) $run->quantity_produced, 4);
    }
}
