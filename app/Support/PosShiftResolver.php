<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\ProductionShift;

/**
 * يحدد وردية الإنتاج «الجارية» لهذا اليوم (إن وُجدت) لربط جلسة نقطة البيع بالعمليات.
 */
final class PosShiftResolver
{
    /**
     * أول وردية بحالة قيد التنفيذ لتاريخ اليوم (حسب توقيت التطبيق).
     */
    public static function currentOpenProductionShift(): ?ProductionShift
    {
        return ProductionShift::query()
            ->whereDate('date', now()->toDateString())
            ->where('status', ProductionShift::STATUS_IN_PROGRESS)
            ->where('is_active', true)
            ->orderByDesc('id')
            ->first();
    }
}
