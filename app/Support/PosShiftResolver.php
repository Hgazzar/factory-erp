<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\ProductionShift;
use App\Services\Tenant\TenantContext;
use Illuminate\Support\Facades\Auth;

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
        $query = ProductionShift::query()
            ->whereDate('date', now()->toDateString())
            ->where('status', ProductionShift::STATUS_IN_PROGRESS)
            ->where('is_active', true);

        $tenantContext = app(TenantContext::class);
        $tenantUserId = $tenantContext->resolveTenantUserId();

        if ($tenantUserId === null && Auth::check() && $tenantContext->isPlatformOperator()) {
            $tenantUserId = (int) Auth::id();
        }

        if ($tenantUserId !== null) {
            $query->where('user_id', $tenantUserId);
        }

        return $query->orderByDesc('id')->first();
    }
}
