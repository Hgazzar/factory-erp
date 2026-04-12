<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Department;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class EmployeeWebController extends Controller
{
    public function index(): View
    {
        $employees = Employee::with(['linkedUser', 'department'])->orderBy('code')->paginate(20);

        return view('hr.employees.index', compact('employees'));
    }

    public function create(): View
    {
        $users = User::whereDoesntHave('employee')
            ->orderBy('email')
            ->get();

        $departments = Department::orderBy('name')->get();

        return view('hr.employees.create', compact('users', 'departments'));
    }

    public function store(Request $request): RedirectResponse
    {
        $uid = (int) auth()->id();
        $data = $request->validate([
            'linked_user_id' => [
                'nullable',
                Rule::exists('users', 'id'),
                Rule::unique('employees', 'linked_user_id'),
            ],
            'department_id' => ['nullable', Rule::exists('departments', 'id')->where('user_id', $uid)],
            'code' => ['required', 'string', 'max:30', Rule::unique('employees', 'code')->where('user_id', $uid)],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255', Rule::unique('employees', 'email')->where('user_id', $uid)],
            'position' => ['nullable', 'string', 'max:255'],
            'gender' => ['nullable', 'in:male,female,other'],
            'status' => ['required', 'in:active,inactive,on_leave'],
            'base_salary' => ['nullable', 'numeric', 'min:0'],
            'hire_date' => ['nullable', 'date'],
            'hired_at' => ['nullable', 'date'],
            'role' => ['required', 'in:admin,supervisor,worker'],
        ]);

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
        $users = User::whereDoesntHave('employee')
            ->orWhereHas('employee', function ($q) use ($employee) {
                $q->where('id', $employee->id);
            })
            ->orderBy('email')
            ->get();

        $departments = Department::orderBy('name')->get();

        return view('hr.employees.edit', compact('employee', 'users', 'departments'));
    }

    public function update(Request $request, Employee $employee): RedirectResponse
    {
        $uid = (int) auth()->id();
        $data = $request->validate([
            'linked_user_id' => [
                'nullable',
                Rule::exists('users', 'id'),
                Rule::unique('employees', 'linked_user_id')->ignore($employee->id),
            ],
            'department_id' => ['nullable', Rule::exists('departments', 'id')->where('user_id', $uid)],
            'code' => ['required', 'string', 'max:30', Rule::unique('employees', 'code')->where('user_id', $uid)->ignore($employee->id)],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255', Rule::unique('employees', 'email')->where('user_id', $uid)->ignore($employee->id)],
            'position' => ['nullable', 'string', 'max:255'],
            'gender' => ['nullable', 'in:male,female,other'],
            'status' => ['required', 'in:active,inactive,on_leave'],
            'base_salary' => ['nullable', 'numeric', 'min:0'],
            'hire_date' => ['nullable', 'date'],
            'hired_at' => ['nullable', 'date'],
            'role' => ['required', 'in:admin,supervisor,worker'],
        ]);

        if (! empty($data['linked_user_id']) && $request->user() && (int) $data['linked_user_id'] === (int) $request->user()->id && $data['role'] !== $request->user()->role) {
            return back()
                ->withInput()
                ->with('error', 'لا يمكن تعديل صلاحياتك الخاصة من شاشة الموظفين. استخدم لوحة الإدارة المخصصة لتغيير الصلاحيات.');
        }

        $role = $data['role'];
        unset($data['role']);

        $data = $this->normalizeEmployeePayload($data, $uid);
        $data['user_id'] = $employee->user_id;

        $employee->update($data);

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
    protected function normalizeEmployeePayload(array $data, int $tenantUserId): array
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

        return $data;
    }

    public function destroy(Employee $employee): RedirectResponse
    {
        $employee->delete();

        return redirect()
            ->route('hr.employees.index')
            ->with('success', 'تم حذف الموظف.');
    }
}
