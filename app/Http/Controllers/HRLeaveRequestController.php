<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Leave;
use App\Services\LeaveDayCalculator;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class HRLeaveRequestController extends Controller
{
    public function index(Request $request): View
    {
        $statusOptions = [
            ['value' => '', 'label' => 'كل الحالات'],
            ['value' => Leave::STATUS_NEW, 'label' => 'جديد'],
            ['value' => Leave::STATUS_APPROVED, 'label' => 'معتمد'],
            ['value' => Leave::STATUS_REJECTED, 'label' => 'مرفوض'],
        ];

        $departmentOptions = array_merge(
            [['value' => '', 'label' => 'كل الأقسام']],
            Department::query()->orderBy('name')->get()->map(fn (Department $d) => [
                'value' => (string) $d->id,
                'label' => $d->name,
            ])->values()->all()
        );

        $employeesForFilter = Employee::query()->orderBy('name')->limit(300)->get();
        $employeeOptions = array_merge(
            [['value' => '', 'label' => 'كل الموظفين']],
            $employeesForFilter->map(fn (Employee $e) => [
                'value' => (string) $e->id,
                'label' => trim(($e->code ? $e->code.' — ' : '').$e->name),
            ])->values()->all()
        );

        $query = Leave::query()->with(['employee.department', 'approver'])->latest('id');
        if ($request->filled('status')) {
            $st = (string) $request->string('status');
            if ($st === 'pending') {
                $st = Leave::STATUS_NEW;
            }
            $query->where('status', $st);
        }
        if ($request->filled('employee_id')) {
            $query->where('employee_id', $request->integer('employee_id'));
        }
        if ($request->filled('department_id')) {
            $did = $request->integer('department_id');
            $query->whereHas('employee', fn ($q) => $q->where('department_id', $did));
        }

        $leaves = $query->paginate(20)->withQueryString();

        return view('hr.leave-requests', compact(
            'leaves',
            'statusOptions',
            'departmentOptions',
            'employeeOptions',
        ));
    }

    public function create(Request $request): View
    {
        $uid = (int) $request->user()->id;

        $employeeOptions = Employee::query()
            ->where('user_id', $uid)
            ->where('status', 'active')
            ->orderBy('name')
            ->limit(500)
            ->get()
            ->map(fn (Employee $e) => [
                'value' => (string) $e->id,
                'label' => trim(($e->code ? $e->code.' — ' : '').$e->name),
            ])
            ->values()
            ->all();

        $leaveTypeOptions = [
            ['value' => Leave::TYPE_ANNUAL, 'label' => 'سنوي'],
            ['value' => Leave::TYPE_CASUAL, 'label' => 'عارضة'],
            ['value' => Leave::TYPE_SICK, 'label' => 'مرضي'],
            ['value' => Leave::TYPE_EXCEPTIONAL, 'label' => 'استثنائي'],
        ];

        $leaveExcludedIsoWeekdays = config('hr.leave_excluded_iso_weekdays', [5, 6]);

        return view('hr.leave-requests.create', compact(
            'employeeOptions',
            'leaveTypeOptions',
            'leaveExcludedIsoWeekdays',
        ));
    }

    public function store(Request $request): RedirectResponse
    {
        $uid = (int) $request->user()->id;
        $data = $request->validate([
            'employee_id' => ['required', 'integer', 'exists:employees,id'],
            'leave_type' => ['required', 'in:annual,casual,sick,exceptional'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'reason' => ['nullable', 'string', 'max:5000'],
            'attachments' => ['nullable', 'array', 'max:10'],
            'attachments.*' => ['file', 'max:10240', 'mimes:jpeg,jpg,png,gif,webp,pdf,doc,docx,xls,xlsx,txt,csv'],
        ]);

        $employee = Employee::query()
            ->where('user_id', $uid)
            ->whereKey((int) $data['employee_id'])
            ->firstOrFail();

        $start = Carbon::parse((string) $data['start_date'])->startOfDay();
        $end = Carbon::parse((string) $data['end_date'])->startOfDay();
        $days = LeaveDayCalculator::countWorkingDays($start, $end);
        if ($days < 1) {
            return back()->withInput()->with('error', 'عدد أيام العمل داخل المدة يجب أن يكون 1 على الأقل (تحقق من تاريخي البداية/النهاية ونهاية الأسبوع).');
        }

        if ((string) $data['leave_type'] === Leave::TYPE_ANNUAL && (float) ($employee->annual_balance ?? 0) < $days) {
            return back()->withInput()->with('error', 'رصيد الإجازة السنوية (annual balance) غير كافٍ لهذا الموظف.');
        }

        $paths = [];
        $uploads = $request->file('attachments', []) ?? [];
        if (is_array($uploads)) {
            foreach ($uploads as $file) {
                if ($file) {
                    $paths[] = $file->store('leaves', 'public');
                }
            }
        }

        Leave::query()->create([
            'user_id' => $uid,
            'employee_id' => $employee->id,
            'leave_type' => (string) $data['leave_type'],
            'start_date' => $start->toDateString(),
            'end_date' => $end->toDateString(),
            'days_count' => $days,
            'reason' => $data['reason'] ?? null,
            'status' => Leave::STATUS_NEW,
            'attachments' => $paths === [] ? null : $paths,
        ]);

        return redirect()->route('hr.leave-requests')->with('success', 'تم تقديم طلب الإجازة بنجاح.');
    }

    public function approve(Request $request, Leave $leave): RedirectResponse
    {
        if ($leave->status !== Leave::STATUS_NEW) {
            return back()->with('error', 'لا يمكن اعتماد طلب ليس في حالة «جديد».');
        }

        DB::transaction(function () use ($leave, $request): void {
            $leave->loadMissing('employee');
            $employee = $leave->employee;
            if (! $employee) {
                abort(404);
            }

            if ($leave->leave_type === Leave::TYPE_ANNUAL) {
                $remaining = (float) ($employee->annual_balance ?? 0);
                if ($remaining < (int) $leave->days_count) {
                    throw new \RuntimeException('رصيد الإجازة السنوية غير كافٍ لاعتماد الطلب.');
                }

                $employee->annual_balance = round($remaining - (int) $leave->days_count, 2);
                $employee->save();
            }

            $workingDates = LeaveDayCalculator::workingDatesBetween(
                $leave->start_date,
                $leave->end_date
            );
            foreach ($workingDates as $workDate) {
                Attendance::withoutGlobalScopes()->updateOrCreate(
                    [
                        'user_id' => (int) $leave->user_id,
                        'employee_id' => (int) $leave->employee_id,
                        'work_date' => $workDate,
                    ],
                    [
                        'status' => Attendance::STATUS_LEAVE,
                        'minutes_late' => 0,
                        'work_hours' => 0,
                        'deduction_amount' => 0,
                        'check_in_at' => null,
                        'check_out_at' => null,
                        'notes' => 'إجازة معتمدة #'.$leave->id,
                    ]
                );
            }

            $leave->status = Leave::STATUS_APPROVED;
            $leave->approved_by = (int) $request->user()->id;
            $leave->approved_at = now();
            $leave->save();
        });

        return back()->with('success', 'تم اعتماد طلب الإجازة وتحديث الحضور والرصيد.');
    }

    public function reject(Leave $leave): RedirectResponse
    {
        if ($leave->status !== Leave::STATUS_NEW) {
            return back()->with('error', 'لا يمكن رفض طلب ليس في حالة «جديد».');
        }

        $leave->update([
            'status' => Leave::STATUS_REJECTED,
            'approved_by' => null,
            'approved_at' => null,
        ]);

        return back()->with('success', 'تم رفض طلب الإجازة.');
    }
}
