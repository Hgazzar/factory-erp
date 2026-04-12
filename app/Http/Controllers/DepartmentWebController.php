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
    public function index(): View
    {
        $departments = Department::query()
            ->with('manager')
            ->withCount('employees')
            ->orderBy('name')
            ->paginate(20);

        return view('hr.departments.index', compact('departments'));
    }

    public function create(): View
    {
        $employees = Employee::query()->orderBy('name')->get();

        return view('hr.departments.create', compact('employees'));
    }

    public function store(Request $request): RedirectResponse
    {
        $uid = (int) auth()->id();
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'manager_id' => ['nullable', Rule::exists('employees', 'id')->where('user_id', $uid)],
        ]);

        Department::query()->create(array_merge($data, ['user_id' => $uid]));

        return redirect()
            ->route('hr.departments.index')
            ->with('success', 'تم إنشاء القسم.');
    }

    public function edit(Department $department): View
    {
        $employees = Employee::query()->orderBy('name')->get();

        return view('hr.departments.edit', compact('department', 'employees'));
    }

    public function update(Request $request, Department $department): RedirectResponse
    {
        $uid = (int) auth()->id();
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'manager_id' => ['nullable', Rule::exists('employees', 'id')->where('user_id', $uid)],
        ]);

        $department->update($data);

        return redirect()
            ->route('hr.departments.index')
            ->with('success', 'تم تحديث القسم.');
    }

    public function destroy(Department $department): RedirectResponse
    {
        if ($department->employees()->exists()) {
            return back()->with('error', 'لا يمكن حذف قسم مرتبط بموظفين. انقل الموظفين أولاً.');
        }

        $department->delete();

        return redirect()
            ->route('hr.departments.index')
            ->with('success', 'تم حذف القسم.');
    }
}
