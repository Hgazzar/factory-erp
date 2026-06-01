<?php

declare(strict_types=1);

namespace App\Http\Controllers\Clinic;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\ResolvesOperationsTenant;
use App\Models\Clinic\BlockedSlot;
use App\Services\Clinic\ClinicDoctorScheduleService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class ClinicDoctorScheduleWebController extends Controller
{
    use ResolvesOperationsTenant;

    public function index(Request $request, ClinicDoctorScheduleService $schedules): View
    {
        $tenantUserId = $this->resolveOperationsTenantUserId();
        $doctorId = (int) $request->query('doctor_id', 0);

        $doctorSchedules = $doctorId > 0
            ? $schedules->forDoctor($tenantUserId, $doctorId)
            : collect();

        $blockedSlots = BlockedSlot::query()
            ->with('doctor:id,name')
            ->where('user_id', $tenantUserId)
            ->orderByDesc('blocked_date')
            ->limit(50)
            ->get();

        return view('clinic.doctor-schedules.index', [
            'doctors' => $schedules->doctorOptions($tenantUserId),
            'selectedDoctorId' => $doctorId > 0 ? $doctorId : null,
            'doctorSchedules' => $doctorSchedules,
            'blockedSlots' => $blockedSlots,
            'dayLabels' => ClinicDoctorScheduleService::dayLabels(),
        ]);
    }

    public function storeSchedule(Request $request, ClinicDoctorScheduleService $schedules): RedirectResponse
    {
        $data = $request->validate([
            'doctor_employee_id' => ['required', 'integer'],
            'day_of_week' => ['required', 'integer', 'min:0', 'max:6'],
            'start_time' => ['required', 'string', 'max:8'],
            'end_time' => ['required', 'string', 'max:8'],
            'slot_duration_minutes' => ['nullable', 'integer', 'min:5', 'max:240'],
        ]);

        $schedules->upsertSchedule($this->resolveOperationsTenantUserId(), $data);

        return back()->with('success', 'تم حفظ جدول الطبيب.');
    }

    public function destroySchedule(int $schedule, ClinicDoctorScheduleService $schedules): RedirectResponse
    {
        $schedules->deleteSchedule($this->resolveOperationsTenantUserId(), $schedule);

        return back()->with('success', 'تم حذف الفترة.');
    }

    public function storeBlocked(Request $request, ClinicDoctorScheduleService $schedules): RedirectResponse
    {
        $data = $request->validate([
            'doctor_employee_id' => ['nullable', 'integer'],
            'blocked_date' => ['required', 'date'],
            'is_full_day' => ['nullable', 'boolean'],
            'start_time' => ['nullable', 'string', 'max:8'],
            'end_time' => ['nullable', 'string', 'max:8'],
            'reason' => ['nullable', 'string', 'max:255'],
        ]);

        $data['is_full_day'] = $request->boolean('is_full_day');

        $schedules->createBlockedSlot($this->resolveOperationsTenantUserId(), $data);

        return back()->with('success', 'تم تسجيل الإغلاق.');
    }

    public function destroyBlocked(int $blocked, ClinicDoctorScheduleService $schedules): RedirectResponse
    {
        $schedules->deleteBlockedSlot($this->resolveOperationsTenantUserId(), $blocked);

        return back()->with('success', 'تم حذف الإغلاق.');
    }
}
