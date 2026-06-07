<?php

declare(strict_types=1);

namespace App\Http\Controllers\Nursery;

use App\Http\Controllers\Concerns\ResolvesOperationsTenant;
use App\Http\Controllers\Controller;
use App\Models\Nursery\AttendanceWeekdaySetting;
use App\Models\Nursery\Child;
use App\Models\Nursery\Classroom;
use App\Models\Nursery\LeaveRecord;
use App\Models\Employee;
use App\Services\Nursery\NurseryAttendanceListService;
use App\Services\Nursery\NurseryAttendanceService;
use App\Support\NurseryAccess;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use InvalidArgumentException;
use RuntimeException;

final class NurseryAttendanceWebController extends Controller
{
    use ResolvesOperationsTenant;

    public function index(
        NurseryAttendanceService $attendance,
        NurseryAttendanceListService $list,
        Request $request,
    ): View {
        $tenantUserId = $this->resolveOperationsTenantUserId();
        $tab = in_array($request->query('tab'), ['register', 'children', 'staff'], true)
            ? $request->query('tab')
            : 'register';
        $q = trim((string) $request->query('q', ''));
        $week = $request->query('week');
        $weekStart = $week ? Carbon::parse((string) $week)->startOfWeek(Carbon::SUNDAY) : now()->startOfWeek(Carbon::SUNDAY);
        $classroomId = $request->query('classroom_id') !== null && $request->query('classroom_id') !== ''
            ? (int) $request->query('classroom_id')
            : null;

        $access = app(NurseryAccess::class);
        $canManageChildren = $access->allows(NurseryAccess::CAP_MANAGE_CHILD_ATTENDANCE);
        $canManageStaff = $access->allows(NurseryAccess::CAP_MANAGE_STAFF_ATTENDANCE);

        $board = $attendance->todayBoard($tenantUserId);
        $staffBoard = $canManageStaff ? $attendance->staffTodayBoard($tenantUserId) : null;
        $childSearchResults = $tab === 'register' && $q !== '' ? $attendance->findChildForQuickSearch($tenantUserId, $q) : collect();
        $staffSearchResults = $tab === 'register' && $q !== '' ? $attendance->findStaffForQuickSearch($tenantUserId, $q) : collect();

        $childrenGrid = $tab === 'children'
            ? $list->childrenWeeklyGrid($tenantUserId, $weekStart, $classroomId, $q)
            : null;

        $staffGrid = $tab === 'staff'
            ? $list->staffWeeklyGrid($tenantUserId, $weekStart, $q)
            : null;

        $classroomOptions = [['value' => '', 'label' => 'الجميع']];
        foreach (
            Classroom::query()
                ->where('user_id', $tenantUserId)
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'name']) as $room
        ) {
            $classroomOptions[] = ['value' => (string) $room->id, 'label' => $room->name];
        }

        $childOptions = Child::query()
            ->where('user_id', $tenantUserId)
            ->where('status', Child::STATUS_ACTIVE)
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn (Child $c) => ['value' => (string) $c->id, 'label' => $c->name])
            ->all();

        $staffOptions = Employee::query()
            ->where('user_id', $tenantUserId)
            ->where('status', 'active')
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn (Employee $e) => ['value' => (string) $e->id, 'label' => $e->name])
            ->all();

        $childrenWeekdays = $list->weekdaysFor($tenantUserId, AttendanceWeekdaySetting::SCOPE_CHILDREN);
        $staffWeekdays = $list->weekdaysFor($tenantUserId, AttendanceWeekdaySetting::SCOPE_STAFF);

        return view('nursery.attendance.index', compact(
            'tab',
            'q',
            'weekStart',
            'classroomId',
            'board',
            'staffBoard',
            'childSearchResults',
            'staffSearchResults',
            'childrenGrid',
            'staffGrid',
            'classroomOptions',
            'childOptions',
            'staffOptions',
            'childrenWeekdays',
            'staffWeekdays',
            'canManageChildren',
            'canManageStaff',
        ));
    }

    public function checkIn(Request $request, NurseryAttendanceService $attendance): RedirectResponse
    {
        $tenantUserId = $this->resolveOperationsTenantUserId();
        $childId = (int) $request->input('child_id', 0);

        try {
            $attendance->checkIn($tenantUserId, $childId, (int) auth()->id());
        } catch (RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'تم تسجيل الحضور بنجاح.');
    }

    public function checkOut(Request $request, NurseryAttendanceService $attendance): RedirectResponse
    {
        $tenantUserId = $this->resolveOperationsTenantUserId();
        $childId = (int) $request->input('child_id', 0);

        try {
            $attendance->checkOut($tenantUserId, $childId, (int) auth()->id());
        } catch (RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'تم تسجيل الانصراف بنجاح.');
    }

    public function staffCheckIn(Request $request, NurseryAttendanceService $attendance): RedirectResponse
    {
        $tenantUserId = $this->resolveOperationsTenantUserId();

        try {
            $attendance->staffCheckIn($tenantUserId, (int) $request->input('employee_id'), (int) auth()->id());
        } catch (RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'تم تسجيل حضور الموظف.');
    }

    public function staffCheckOut(Request $request, NurseryAttendanceService $attendance): RedirectResponse
    {
        $tenantUserId = $this->resolveOperationsTenantUserId();

        try {
            $attendance->staffCheckOut($tenantUserId, (int) $request->input('employee_id'), (int) auth()->id());
        } catch (RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'تم تسجيل انصراف الموظف.');
    }

    public function storeWeekdays(Request $request, NurseryAttendanceListService $list): RedirectResponse
    {
        $tenantUserId = $this->resolveOperationsTenantUserId();
        $data = $request->validate([
            'scope' => ['required', 'in:children,staff'],
            'weekdays' => ['required', 'array', 'min:1'],
            'weekdays.*' => ['integer', 'between:0,6'],
            'tab' => ['nullable', 'string'],
        ]);

        try {
            $list->saveWeekdays($tenantUserId, $data['scope'], $data['weekdays']);
        } catch (InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'تم حفظ أيام الحضور.');
    }

    public function storeLeave(Request $request, NurseryAttendanceListService $list): RedirectResponse
    {
        $tenantUserId = $this->resolveOperationsTenantUserId();
        $data = $request->validate([
            'scope' => ['required', 'in:children,staff'],
            'name' => ['required', 'string', 'max:120'],
            'starts_on' => ['required', 'date'],
            'ends_on' => ['required', 'date', 'after_or_equal:starts_on'],
            'child_ids' => ['nullable', 'array'],
            'child_ids.*' => ['integer'],
            'child_id' => ['nullable', 'integer'],
            'employee_ids' => ['nullable', 'array'],
            'employee_ids.*' => ['integer'],
            'employee_id' => ['nullable', 'integer'],
        ]);

        if (! empty($data['child_id'])) {
            $data['child_ids'] = [(int) $data['child_id']];
        }
        if (! empty($data['employee_id'])) {
            $data['employee_ids'] = [(int) $data['employee_id']];
        }

        try {
            $list->storeLeave($tenantUserId, $data, (int) auth()->id());
        } catch (InvalidArgumentException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return back()->with('success', 'تم تسجيل الإجازة.');
    }

    public function destroyLeave(LeaveRecord $leave, NurseryAttendanceListService $list): RedirectResponse
    {
        $list->deleteLeave($leave, $this->resolveOperationsTenantUserId());

        return back()->with('success', 'تم حذف الإجازة.');
    }

    public function report(Request $request, NurseryAttendanceListService $list): View
    {
        $tenantUserId = $this->resolveOperationsTenantUserId();
        $data = $request->validate([
            'scope' => ['required', 'in:children,staff'],
            'starts_on' => ['required', 'date'],
            'ends_on' => ['required', 'date', 'after_or_equal:starts_on'],
            'child_ids' => ['nullable', 'array'],
            'child_ids.*' => ['integer'],
            'child_id' => ['nullable', 'integer'],
            'employee_ids' => ['nullable', 'array'],
            'employee_ids.*' => ['integer'],
            'employee_id' => ['nullable', 'integer'],
            'include_absence_reason' => ['nullable'],
        ]);

        $subjectIds = [];
        if ($data['scope'] === LeaveRecord::SCOPE_CHILDREN) {
            if (! empty($data['child_id'])) {
                $subjectIds = [(int) $data['child_id']];
            } elseif (! empty($data['child_ids'])) {
                $subjectIds = array_map('intval', $data['child_ids']);
            }
        } elseif (! empty($data['employee_id'])) {
            $subjectIds = [(int) $data['employee_id']];
        } elseif (! empty($data['employee_ids'])) {
            $subjectIds = array_map('intval', $data['employee_ids']);
        }

        $report = $list->buildReport(
            $tenantUserId,
            $data['scope'],
            $data['starts_on'],
            $data['ends_on'],
            $subjectIds,
            filter_var($data['include_absence_reason'] ?? false, FILTER_VALIDATE_BOOL),
        );

        return view('nursery.attendance.report', compact('report'));
    }
}
