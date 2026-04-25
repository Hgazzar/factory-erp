<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\PersistsMorphAttachments;
use App\Models\Account;
use App\Models\Attendance;
use App\Models\AuditLog;
use App\Models\CostCenter;
use App\Models\Department;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class EmployeeWebController extends Controller
{
    use PersistsMorphAttachments;

    /**
     * استيراد الموظفين (مسار مؤقت حتى تفعيل المعالجة).
     */
    public function import(): RedirectResponse
    {
        return redirect()
            ->route('hr.employees.index')
            ->with('info', 'استيراد الموظفين سيتوفر قريباً.');
    }

    /**
     * تصدير قائمة الموظفين (مسار مؤقت حتى تفعيل الملف).
     */
    public function export(): RedirectResponse
    {
        return redirect()
            ->route('hr.employees.index')
            ->with('info', 'تصدير الموظفين سيتوفر قريباً.');
    }

    public function index(Request $request): View
    {
        $filterStatus = $request->query('status', 'active');
        if (! in_array($filterStatus, ['active', 'inactive', 'all'], true)) {
            $filterStatus = 'active';
        }

        $query = Employee::query()
            ->with(['linkedUser', 'department', 'costCenter'])
            ->orderBy('code');

        if ($request->filled('search')) {
            $s = trim((string) $request->input('search'));
            $query->where(function ($q) use ($s) {
                $q->where('name', 'like', '%'.$s.'%')
                    ->orWhere('code', 'like', '%'.$s.'%')
                    ->orWhere('position', 'like', '%'.$s.'%')
                    ->orWhere('job_title', 'like', '%'.$s.'%');
            });
        }

        if ($filterStatus === 'active') {
            $query->where('status', 'active');
        } elseif ($filterStatus === 'inactive') {
            $query->where('status', 'inactive');
        }

        if ($request->filled('department_id')) {
            $query->where('department_id', $request->integer('department_id'));
        }

        $employees = $query->paginate(20)->withQueryString();
        $departmentSelectOptions = $this->departmentFilterSelectOptions();
        $statusSelectOptions = $this->statusFilterSelectOptions();

        return view('hr.employees.index', compact('employees', 'filterStatus', 'departmentSelectOptions', 'statusSelectOptions'));
    }

    public function show(Employee $employee): View
    {
        $employee->load(['department', 'costCenter', 'ledgerAccount', 'linkedUser', 'attachments']);

        $historyStart = now()->copy()->subDays(29)->startOfDay();
        $historyEnd = now()->copy()->startOfDay();
        $attendanceByDate = Attendance::query()
            ->where('employee_id', $employee->id)
            ->whereBetween('work_date', [$historyStart->toDateString(), $historyEnd->toDateString()])
            ->get()
            ->keyBy(fn (Attendance $a) => $a->work_date->format('Y-m-d'));

        $attendanceHistory = collect(range(0, 29))
            ->map(function (int $offset) use ($employee, $attendanceByDate) {
                $date = now()->subDays($offset)->toDateString();

                return Attendance::buildHistoryRowForDate($date, $employee, $attendanceByDate->get($date));
            });

        return view('hr.employees.show', compact('employee', 'attendanceHistory'));
    }

    public function create(): View
    {
        $uid = (int) auth()->id();
        $users = User::whereDoesntHave('employee')
            ->orderBy('email')
            ->get();

        $departments = Department::orderBy('name')->get();
        $costCenterOptions = $this->costCenterSelectOptions($uid);
        $wageAccountOptions = $this->wageAccountSelectOptions($uid);

        return view('hr.employees.create', compact(
            'users',
            'departments',
            'costCenterOptions',
            'wageAccountOptions',
        ));
    }

    public function store(Request $request): RedirectResponse
    {
        $uid = (int) auth()->id();
        $data = $request->validate($this->employeeRules($uid));

        if (! empty($data['linked_user_id']) && $request->user() && (int) $data['linked_user_id'] === (int) $request->user()->id && $data['role'] !== $request->user()->role) {
            return back()
                ->withInput()
                ->with('error', 'لا يمكن تعديل صلاحياتك الخاصة من شاشة الموظفين. استخدم لوحة الإدارة المخصصة لتغيير الصلاحيات.');
        }

        $role = $data['role'];
        unset($data['role']);

        $data = $this->normalizeEmployeePayload($data, $uid);
        $data['user_id'] = $uid;

        $employee = Employee::create($data);
        $uploads = $request->file('attachments', []) ?? [];
        if (! is_array($uploads)) {
            $uploads = [];
        }
        $this->persistMorphAttachments($employee, $uploads, $uid, 'employees');

        if (! empty($data['linked_user_id']) && $employee->linkedUser) {
            $user = $employee->linkedUser;
            $oldRole = $user->role;
            $user->role = $role;
            $user->save();

            if ($oldRole !== $user->role && $request->user()) {
                AuditLog::create([
                    'actor_id' => $request->user()->id,
                    'target_user_id' => $user->id,
                    'action' => 'role_changed',
                    'old_role' => $oldRole,
                    'new_role' => $user->role,
                    'logged_at' => now(),
                ]);
            }
        }

        return redirect()
            ->route('hr.employees.index')
            ->with('success', 'تم إضافة الموظف وربطه بالمستخدم والصلاحيات بنجاح.');
    }

    public function edit(Employee $employee): View
    {
        $employee->load('attachments');
        $users = User::whereDoesntHave('employee')
            ->orWhereHas('employee', function ($q) use ($employee) {
                $q->where('id', $employee->id);
            })
            ->orderBy('email')
            ->get();

        $departments = Department::orderBy('name')->get();
        $uid = (int) auth()->id();
        $costCenterOptions = $this->costCenterSelectOptions($uid);
        $wageAccountOptions = $this->wageAccountSelectOptions($uid);

        return view('hr.employees.edit', compact('employee', 'users', 'departments', 'costCenterOptions', 'wageAccountOptions'));
    }

    public function update(Request $request, Employee $employee): RedirectResponse
    {
        $uid = (int) auth()->id();
        $data = $request->validate($this->employeeRules($uid, $employee));

        if (! empty($data['linked_user_id']) && $request->user() && (int) $data['linked_user_id'] === (int) $request->user()->id && $data['role'] !== $request->user()->role) {
            return back()
                ->withInput()
                ->with('error', 'لا يمكن تعديل صلاحياتك الخاصة من شاشة الموظفين. استخدم لوحة الإدارة المخصصة لتغيير الصلاحيات.');
        }

        $role = $data['role'];
        unset($data['role']);

        $data = $this->normalizeEmployeePayload($data, $uid, $employee);
        $data['user_id'] = $employee->user_id;

        $employee->update($data);
        $uploads = $request->file('attachments', []) ?? [];
        if (! is_array($uploads)) {
            $uploads = [];
        }
        $this->persistMorphAttachments($employee, $uploads, $uid, 'employees');

        if (! empty($data['linked_user_id'])) {
            $user = User::find($data['linked_user_id']);
            if ($user) {
                $oldRole = $user->role;
                $user->role = $role;
                $user->save();

                if ($oldRole !== $user->role && $request->user()) {
                    AuditLog::create([
                        'actor_id' => $request->user()->id,
                        'target_user_id' => $user->id,
                        'action' => 'role_changed',
                        'old_role' => $oldRole,
                        'new_role' => $user->role,
                        'logged_at' => now(),
                    ]);
                }
            }
        }

        return redirect()
            ->route('hr.employees.index')
            ->with('success', 'تم تحديث بيانات الموظف والصلاحيات بنجاح.');
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function normalizeEmployeePayload(array $data, int $tenantUserId, ?Employee $employee = null): array
    {
        $position = $data['position'] ?? null;
        if ($position !== null && $position !== '' && empty($data['job_title'])) {
            $data['job_title'] = $position;
        }

        $hireDate = $data['hire_date'] ?? null;
        if ($hireDate && empty($data['hired_at'])) {
            $data['hired_at'] = $hireDate;
        }

        if (! empty($data['department_id'])) {
            $dept = Department::query()->where('user_id', $tenantUserId)->find($data['department_id']);
            $data['department'] = $dept?->name;
        } elseif (array_key_exists('department_id', $data) && $data['department_id'] === null) {
            $data['department'] = null;
        }

        if (array_key_exists('linked_user_id', $data) && $data['linked_user_id'] === '') {
            $data['linked_user_id'] = null;
        }

        $nameParts = array_values(array_filter([
            trim((string) ($data['first_name'] ?? '')),
            trim((string) ($data['middle_name'] ?? '')),
            trim((string) ($data['last_name'] ?? '')),
        ], fn (string $v) => $v !== ''));
        if ($nameParts !== []) {
            $data['name'] = implode(' ', $nameParts);
        } elseif (empty($data['name'])) {
            $data['name'] = $employee?->name ?: ('موظف '.$data['code']);
        }

        $codeTrim = trim((string) ($data['code'] ?? ''));
        if ($codeTrim !== '') {
            $rawDev = $data['attendance_device_id'] ?? '';
            $devTrim = trim((string) $rawDev);
            $data['attendance_device_id'] = $devTrim === '' ? $codeTrim : $devTrim;
        }

        foreach ([
            'cost_center_id',
            'ledger_account_id',
            'attendance_device_id',
            'fixed_insurance_deduction',
            'fixed_tax_deduction',
            'annual_balance',
            'first_name',
            'middle_name',
            'last_name',
            'personal_email',
            'marital_status',
            'nationality',
            'id_number',
            'passport_number',
            'mobile',
            'phone',
            'address',
            'city',
            'region',
            'postal_code',
            'country',
            'emergency_contact_name',
            'emergency_contact_phone',
            'emergency_contact_relation',
            'employment_type',
            'bank_name',
            'bank_account_number',
            'iban',
            'social_insurance_number',
            'tax_number',
            'insurance_number',
            'notes',
        ] as $k) {
            if (array_key_exists($k, $data) && ($data[$k] === '' || $data[$k] === null)) {
                $data[$k] = null;
            }
        }

        return $data;
    }

    /**
     * @return array<string, mixed>
     */
    private function employeeRules(int $uid, ?Employee $employee = null): array
    {
        $uniqueLinked = Rule::unique('employees', 'linked_user_id');
        $uniqueCode = Rule::unique('employees', 'code')->where('user_id', $uid);
        $uniqueEmail = Rule::unique('employees', 'email')->where('user_id', $uid);
        $uniqueDevice = Rule::unique('employees', 'attendance_device_id')->where('user_id', $uid);
        if ($employee) {
            $uniqueLinked->ignore($employee->id);
            $uniqueCode->ignore($employee->id);
            $uniqueEmail->ignore($employee->id);
            $uniqueDevice->ignore($employee->id);
        }

        return [
            'linked_user_id' => ['nullable', Rule::exists('users', 'id'), $uniqueLinked],
            'department_id' => ['nullable', Rule::exists('departments', 'id')->where('user_id', $uid)],
            'code' => ['required', 'string', 'max:30', $uniqueCode],
            'attendance_device_id' => ['nullable', 'string', 'max:64', $uniqueDevice],
            'name' => ['nullable', 'string', 'max:255'],
            'first_name' => ['required', 'string', 'max:255'],
            'middle_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255', $uniqueEmail],
            'personal_email' => ['nullable', 'email', 'max:255'],
            'position' => ['nullable', 'string', 'max:255'],
            'employment_type' => ['nullable', 'in:full_time,part_time,contract,temporary,intern'],
            'gender' => ['nullable', 'in:male,female,other'],
            'birth_date' => ['nullable', 'date'],
            'marital_status' => ['nullable', 'in:single,married,divorced,widowed'],
            'nationality' => ['nullable', 'string', 'max:100'],
            'id_number' => ['nullable', 'string', 'max:100'],
            'passport_number' => ['nullable', 'string', 'max:100'],
            'mobile' => ['nullable', 'string', 'max:50'],
            'phone' => ['nullable', 'string', 'max:50'],
            'address' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:100'],
            'region' => ['nullable', 'string', 'max:100'],
            'postal_code' => ['nullable', 'string', 'max:30'],
            'country' => ['nullable', 'string', 'max:100'],
            'emergency_contact_name' => ['nullable', 'string', 'max:255'],
            'emergency_contact_phone' => ['nullable', 'string', 'max:50'],
            'emergency_contact_relation' => ['nullable', 'string', 'max:80'],
            'status' => ['required', 'in:active,inactive,on_leave'],
            'base_salary' => ['nullable', 'numeric', 'min:0'],
            'salary_type' => ['required', 'in:monthly,weekly,daily'],
            'fixed_insurance_deduction' => ['nullable', 'numeric', 'min:0'],
            'fixed_tax_deduction' => ['nullable', 'numeric', 'min:0'],
            'attendance_policy' => ['required', 'in:none,day_for_day,hour_for_hour'],
            'annual_balance' => ['nullable', 'numeric', 'min:0'],
            'housing_allowance' => ['nullable', 'numeric', 'min:0'],
            'transport_allowance' => ['nullable', 'numeric', 'min:0'],
            'other_allowance' => ['nullable', 'numeric', 'min:0'],
            'hire_date' => ['nullable', 'date'],
            'hired_at' => ['nullable', 'date'],
            'bank_name' => ['nullable', 'string', 'max:255'],
            'bank_account_number' => ['nullable', 'string', 'max:100'],
            'iban' => ['nullable', 'string', 'max:50'],
            'social_insurance_number' => ['nullable', 'string', 'max:100'],
            'tax_number' => ['nullable', 'string', 'max:100'],
            'insurance_number' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string', 'max:5000'],
            'role' => ['required', 'in:admin,supervisor,worker'],
            'cost_center_id' => ['nullable', Rule::exists('cost_centers', 'id')->where('user_id', $uid)],
            'ledger_account_id' => ['nullable', Rule::exists('accounts', 'id')->where('user_id', $uid)],
            'attachments' => ['nullable', 'array', 'max:20'],
            'attachments.*' => ['file', 'max:10240', 'mimes:jpeg,jpg,png,gif,webp,pdf,doc,docx,xls,xlsx,txt,csv'],
        ];
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    private function departmentFilterSelectOptions(): array
    {
        $rows = Department::query()->orderBy('name')->get();

        $opts = $rows->map(fn (Department $d) => [
            'value' => (string) $d->id,
            'label' => $d->name,
        ])->values()->all();

        return array_merge(
            [['value' => '', 'label' => 'جميع الأقسام']],
            $opts
        );
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    private function statusFilterSelectOptions(): array
    {
        return [
            ['value' => 'active', 'label' => 'نشط'],
            ['value' => 'inactive', 'label' => 'غير نشط'],
            ['value' => 'all', 'label' => 'الكل'],
        ];
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    private function costCenterSelectOptions(int $userId): array
    {
        return CostCenter::query()
            ->where('user_id', $userId)
            ->where('status', 'active')
            ->orderBy('code')
            ->get()
            ->map(fn (CostCenter $c) => [
                'value' => (string) $c->id,
                'label' => ($c->code ? $c->code.' — ' : '').$c->name,
            ])
            ->values()
            ->all();
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    private function wageAccountSelectOptions(int $userId): array
    {
        $base = Account::query()
            ->where('user_id', $userId)
            ->where('is_active', true)
            ->where('allow_direct_posting', true);

        $narrow = (clone $base)->whereIn('type', [Account::TYPE_EXPENSE, Account::TYPE_LIABILITY]);
        $accounts = $narrow->exists()
            ? $narrow->orderBy('code')->get()
            : (clone $base)->orderBy('code')->get();

        return $accounts
            ->map(fn (Account $a) => [
                'value' => (string) $a->id,
                'label' => $a->code.' — '.$a->name_ar,
            ])
            ->values()
            ->all();
    }

    public function destroy(Employee $employee): RedirectResponse
    {
        Department::query()
            ->where('manager_id', $employee->id)
            ->update(['manager_id' => null]);

        $employee->delete();

        return redirect()
            ->route('hr.employees.index')
            ->with('success', 'تم حذف الموظف.');
    }
}
