<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Services\FinancialSuperPurgeService;
use App\Support\ErpRoles;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class SystemMaintenanceController extends Controller
{
    public function superPurge(Request $request, FinancialSuperPurgeService $purgeService): RedirectResponse
    {
        if (! ErpRoles::canRunSystemFinancialMaintenance($request->user())) {
            abort(403);
        }

        $data = $request->validate([
            'target_user_id' => ['required', 'integer', 'exists:users,id'],
        ]);

        $targetId = (int) $data['target_user_id'];

        if ($targetId === 1) {
            return redirect()
                ->route('settings.company.edit')
                ->with('error', 'لا يمكن مسح البيانات المالية لمالك النظام.');
        }

        $stats = $purgeService->purge($targetId);

        AuditLog::logFinancialControl('super_purge_financial', $targetId, null, null, $stats);

        return redirect()
            ->route('settings.company.edit')
            ->with('success', 'تم تنفيذ المسح المالي للمستخدم المحدد وفق الإحصاءات المسجلة في سجل المراجعة.');
    }
}
