<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Employee;
use App\Models\PosDevice;
use App\Models\PosSession;
use App\Models\Warehouse;
use App\Support\PosShiftResolver;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PosSessionWebController extends Controller
{
    public function index(Request $request): View
    {
        $uid = (int) auth()->id();

        $devices = PosDevice::query()
            ->where('user_id', $uid)
            ->active()
            ->orderBy('name')
            ->get(['id', 'name']);

        $employees = Employee::query()
            ->where('user_id', $uid)
            ->where('status', 'active')
            ->orderBy('name')
            ->get(['id', 'name', 'code']);

        $openSessions = PosSession::query()
            ->where('user_id', $uid)
            ->with(['posDevice:id,name', 'employee:id,name'])
            ->open()
            ->latest('opened_at')
            ->limit(25)
            ->get();

        return view('pos.sessions.index', [
            'devices' => $devices,
            'employees' => $employees,
            'openSessions' => $openSessions,
        ]);
    }

    /**
     * فتح جلسة كاشير مرتبطة بموظف ووردية إنتاج (إن وُجدت تلقائياً).
     */
    public function store(Request $request): RedirectResponse
    {
        $uid = (int) auth()->id();

        $data = $request->validate([
            'pos_device_id' => ['required', 'exists:pos_devices,id'],
            'employee_id' => ['required', 'exists:employees,id'],
            'opening_balance' => ['nullable', 'numeric'],
            'production_shift_id' => ['nullable', 'exists:production_shifts,id'],
        ]);

        $device = PosDevice::query()
            ->where('user_id', $uid)
            ->findOrFail((int) $data['pos_device_id']);

        $employee = Employee::query()
            ->where('user_id', $uid)
            ->findOrFail((int) $data['employee_id']);

        if (! $device->warehouse_id) {
            return back()->withErrors([
                'pos_device_id' => 'الجهاز غير مرتبط بمستودع؛ لا يمكن فتح جلسة نقطة بيع.',
            ])->withInput();
        }

        if (! Warehouse::query()->whereKey($device->warehouse_id)->exists()) {
            return back()->withErrors([
                'pos_device_id' => 'مستودع الجهاز غير صالح.',
            ])->withInput();
        }

        $shiftId = $data['production_shift_id'] ?? PosShiftResolver::currentOpenProductionShift()?->id;

        $session = PosSession::create([
            'user_id' => $uid,
            'pos_device_id' => $device->id,
            'employee_id' => $employee->id,
            'production_shift_id' => $shiftId,
            'opening_balance' => $data['opening_balance'] ?? 0,
            'closing_balance' => null,
            'status' => PosSession::STATUS_OPEN,
            'opened_at' => now(),
            'closed_at' => null,
        ]);

        AuditLog::logModuleEvent('pos_session_opened', [
            'pos_session_id' => $session->id,
            'pos_device_id' => $device->id,
            'employee_id' => $session->employee_id,
            'production_shift_id' => $session->production_shift_id,
            'warehouse_id' => $device->warehouse_id,
        ], $session);

        return redirect()
            ->route('pos.dashboard')
            ->with('success', 'تم فتح جلسة نقطة البيع وربطها بالموظف والوردية.');
    }
}
