<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\Employee;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class DepartmentWebController extends Controller
{
    public function index(Request $request): View
    {
        $filterStatus = $request->query('status', 'active');
        if (! in_array($filterStatus, ['active', 'inactive', 'all'], true)) {
            $filterStatus = 'active';
        }

        $query = Department::query()
            ->with(['manager', 'parent'])
            ->withCount('employees')
            ->orderBy('name');

        if ($request->filled('search')) {
            $s = trim((string) $request->input('search'));
            $query->where(function ($q) use ($s) {
                $q->where('name', 'like', '%'.$s.'%')
                    ->orWhere('name_en', 'like', '%'.$s.'%')
                    ->orWhere('code', 'like', '%'.$s.'%');
            });
        }

        if ($filterStatus === 'inactive') {
            $query->where('is_active', false);
        } elseif ($filterStatus === 'active') {
            $query->where('is_active', true);
        }

        $departments = $query->paginate(20)->withQueryString();

        return view('hr.departments.index', compact('departments', 'filterStatus'));
    }

    public function create(): View
    {
        $employees = Employee::query()->departmentManagerCandidates()->orderBy('name')->get();
        $departments = Department::query()->orderBy('name')->get();
        $parentSelectOptions = $this->departmentSelectOptions($departments);
        $employeeSelectOptions = $this->employeeSelectOptions($employees);

        return view('hr.departments.create', compact('employees', 'departments', 'parentSelectOptions', 'employeeSelectOptions'));
    }

    public function store(Request $request): RedirectResponse
    {
        $uid = (int) auth()->id();
        $data = $request->validate($this->departmentRules($uid));
        $data['code'] = isset($data['code']) && $data['code'] !== '' ? trim((string) $data['code']) : null;
        $data['name_en'] = isset($data['name_en']) && trim((string) $data['name_en']) !== '' ? trim((string) $data['name_en']) : null;
        $data['description'] = isset($data['description']) && trim((string) $data['description']) !== '' ? trim((string) $data['description']) : null;
        $data['user_id'] = $uid;
        $data['is_active'] = $request->boolean('is_active', true);

        Department::query()->create($data);

        return redirect()
            ->route('hr.departments.index')
            ->with('success', 'تم إنشاء القسم.');
    }

    public function edit(Department $department): View
    {
        $employees = Employee::query()->departmentManagerCandidates()->orderBy('name')->get();
        if ($department->manager_id) {
            $current = $department->manager;
            if ($current && ! $employees->contains('id', $current->id)) {
                $employees = $employees->prepend($current);
            }
        }
        $departments = Department::query()
            ->where('id', '!=', $department->id)
            ->orderBy('name')
            ->get();

        $parentSelectOptions = $this->departmentSelectOptions($departments);
        $employeeSelectOptions = $this->employeeSelectOptions($employees);

        return view('hr.departments.edit', compact('department', 'employees', 'departments', 'parentSelectOptions', 'employeeSelectOptions'));
    }

    public function update(Request $request, Department $department): RedirectResponse
    {
        $uid = (int) auth()->id();
        $data = $request->validate($this->departmentRules($uid, $department));
        $data['code'] = isset($data['code']) && $data['code'] !== '' ? trim((string) $data['code']) : null;
        $data['name_en'] = isset($data['name_en']) && trim((string) $data['name_en']) !== '' ? trim((string) $data['name_en']) : null;
        $data['description'] = isset($data['description']) && trim((string) $data['description']) !== '' ? trim((string) $data['description']) : null;
        $data['is_active'] = $request->boolean('is_active');

        if (! empty($data['parent_id']) && $this->parentSelectionCreatesCycle($department, (int) $data['parent_id'])) {
            return back()
                ->withInput()
                ->withErrors(['parent_id' => 'لا يمكن اختيار قسم فرعي من هذا القسم كقسم أعلى (تسلسل دائري).']);
        }

        $department->update($data);

        return redirect()
            ->route('hr.departments.index')
            ->with('success', 'تم تحديث القسم.');
    }

    public function destroy(Department $department): RedirectResponse
    {
        if ($department->children()->exists()) {
            return back()->with('error', 'لا يمكن حذف قسم يحتوي على أقسام فرعية.');
        }

        if ($department->employees()->exists()) {
            return back()->with('error', 'لا يمكن حذف قسم مرتبط بموظفين. انقل الموظفين أولاً.');
        }

        $department->delete();

        return redirect()
            ->route('hr.departments.index')
            ->with('success', 'تم حذف القسم.');
    }

    /**
     * @return array<string, mixed>
     */
    private function departmentRules(int $userId, ?Department $department = null): array
    {
        $uniqueCode = Rule::unique('departments', 'code')
            ->where('user_id', $userId)
            ->whereNotNull('code');

        if ($department) {
            $uniqueCode->ignore($department->id);
        }

        $parentRules = [
            'nullable',
            Rule::exists('departments', 'id')->where('user_id', $userId),
        ];
        if ($department) {
            $parentRules[] = Rule::notIn([$department->id]);
        }

        return [
            'name' => ['required', 'string', 'max:255'],
            'name_en' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:10000'],
            'code' => ['nullable', 'string', 'max:64', $uniqueCode],
            'parent_id' => $parentRules,
            'manager_id' => ['nullable', Rule::exists('employees', 'id')->where('user_id', $userId)],
        ];
    }

    /**
     * @param  \Illuminate\Support\Collection<int, Department>  $departments
     * @return list<array{value: string, label: string}>
     */
    private function departmentSelectOptions($departments): array
    {
        return $departments->map(fn (Department $d) => [
            'value' => (string) $d->id,
            'label' => $d->name,
        ])->values()->all();
    }

    /**
     * @param  \Illuminate\Support\Collection<int, Employee>  $employees
     * @return list<array{value: string, label: string}>
     */
    private function employeeSelectOptions($employees): array
    {
        return $employees->map(fn (Employee $e) => [
            'value' => (string) $e->id,
            'label' => $e->name.' ('.$e->code.')',
        ])->values()->all();
    }

    /**
     * يتحقق من أن القسم المختار كأعلى ليس القسم نفسه أو أحد أسلافه (منع الدائرة).
     */
    private function parentSelectionCreatesCycle(Department $department, int $parentId): bool
    {
        $walker = Department::query()->find($parentId);
        while ($walker !== null) {
            if ((int) $walker->id === (int) $department->id) {
                return true;
            }
            $walker = $walker->parent;
        }

        return false;
    }
}
