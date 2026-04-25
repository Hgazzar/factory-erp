<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\Department;
use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\View\View;

class HRAttendanceWebController extends Controller
{
    public function index(Request $request): View
    {
        $dateOptions = collect(range(0, 13))
            ->map(function (int $offset) {
                $d = now()->subDays($offset);

                return [
                    'value' => $d->toDateString(),
                    'label' => $d->locale('ar')->translatedFormat('Y/m/d - l'),
                ];
            })->values()->all();

        $selectedDate = (string) $request->query('attendance_date', now()->toDateString());
        if (! collect($dateOptions)->contains(fn ($r) => $r['value'] === $selectedDate)) {
            $selectedDate = now()->toDateString();
        }

        $departmentSelectOptions = array_merge(
            [['value' => '', 'label' => 'جميع الأقسام']],
            Department::query()
                ->orderBy('name')
                ->get()
                ->map(fn (Department $d) => ['value' => (string) $d->id, 'label' => $d->name])
                ->values()
                ->all()
        );

        $query = Employee::query()->with('department')->orderBy('name');
        if ($request->filled('search')) {
            $s = trim((string) $request->query('search'));
            $query->where(function ($q) use ($s) {
                $q->where('name', 'like', '%'.$s.'%')
                    ->orWhere('code', 'like', '%'.$s.'%');
            });
        }
        if ($request->filled('department_id')) {
            $query->where('department_id', $request->integer('department_id'));
        }

        $employees = $query->limit(200)->get();

        $attendanceByEmployeeId = Attendance::query()
            ->whereIn('employee_id', $employees->pluck('id'))
            ->whereDate('work_date', $selectedDate)
            ->get()
            ->keyBy('employee_id');

        $attendanceRows = $employees->map(
            fn (Employee $employee) => Attendance::buildViewRow($employee, $attendanceByEmployeeId->get($employee->id))
        );

        $summary = [
            'total' => $attendanceRows->count(),
            'present' => $attendanceRows->where('status_key', 'present')->count(),
            'late' => $attendanceRows->where('status_key', 'late')->count(),
            'leave' => $attendanceRows->where('status_key', 'leave')->count(),
            'absent' => $attendanceRows->where('status_key', 'absent')->count(),
        ];

        return view('hr.attendance', compact(
            'attendanceRows',
            'summary',
            'dateOptions',
            'selectedDate',
            'departmentSelectOptions'
        ));
    }
}
