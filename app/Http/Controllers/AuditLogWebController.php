<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Support\ErpRoles;
use Illuminate\View\View;

class AuditLogWebController extends Controller
{
    public function index(): View
    {
        $uid = (int) auth()->id();
        $viewer = auth()->user();
        $systemWideAudit = $uid === 1 || ErpRoles::isSuperAdmin($viewer);

        // لا نحمّل subject (morph) — غير مستخدم في الواجهة وقد يتعارض مع نطاق الحسابات المحذوفة
        $logs = AuditLog::with(['actor', 'targetUser'])
            ->when(! $systemWideAudit, function ($q) use ($uid): void {
                // إظهار ما قام به المستخدم أو ما مسّ حسابه (مثل حذف مصروف من قبل سوبر أدمن حيث target_user_id = مالك السند)
                $q->where(function ($sub) use ($uid): void {
                    $sub->where('actor_id', $uid)->orWhere('target_user_id', $uid);
                });
            })
            ->orderByDesc('logged_at')
            ->orderByDesc('id')
            ->paginate(30);

        return view('system.audit.index', compact('logs'));
    }
}

