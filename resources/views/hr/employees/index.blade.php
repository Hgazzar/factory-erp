@extends('layouts.app')

@section('title', 'الموظفون - MIRADA ERP')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h4 mb-1">الموظفون</h1>
        <p class="text-muted mb-0 small">إدارة بيانات الموظفين والأقسام وربطهم بحسابات الدخول.</p>
    </div>
    <div class="d-flex gap-2 flex-wrap">
        <a href="{{ route('hr.dashboard') }}" class="btn btn-outline-secondary rounded-lg">لوحة الموارد البشرية</a>
        <a href="{{ route('hr.employees.create') }}" class="btn btn-primary rounded-lg">
            إضافة موظف جديد
        </a>
    </div>
</div>

<div class="card shadow-sm border-0 rounded-lg">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th style="width: 120px;">الكود</th>
                        <th>الاسم</th>
                        <th style="width: 200px;">البريد</th>
                        <th style="width: 140px;">القسم</th>
                        <th style="width: 140px;">المنصب</th>
                        <th style="width: 100px;">الحالة</th>
                        <th style="width: 110px;" class="text-end">الراتب</th>
                        <th style="width: 120px;">الدور</th>
                        <th style="width: 120px;">التعيين</th>
                        <th scope="col" class="text-center" style="width: 1%; white-space: nowrap;">الإجراءات</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($employees as $employee)
                        <tr>
                            <td><span class="badge bg-secondary">{{ $employee->code }}</span></td>
                            <td>{{ $employee->name }}</td>
                            <td class="small">{{ $employee->email ?? ($employee->linkedUser?->email ?? '—') }}</td>
                            <td>{{ $employee->department?->name ?? $employee->department ?? '—' }}</td>
                            <td>{{ $employee->position ?? $employee->job_title ?? '—' }}</td>
                            <td>
                                @if(($employee->status ?? 'active') === 'active')
                                    <span class="badge bg-success">نشط</span>
                                @elseif($employee->status === 'on_leave')
                                    <span class="badge bg-warning text-dark">إجازة</span>
                                @else
                                    <span class="badge bg-secondary">غير نشط</span>
                                @endif
                            </td>
                            <td class="text-end">{{ number_format((float) $employee->base_salary, 2) }}</td>
                            <td>
                                @php $role = $employee->linkedUser?->role; @endphp
                                @if($role === 'admin')
                                    <span class="badge bg-primary">Admin</span>
                                @elseif($role === 'supervisor')
                                    <span class="badge bg-info text-dark">Supervisor</span>
                                @elseif($role === 'worker')
                                    <span class="badge bg-secondary">Worker</span>
                                @else
                                    <span class="badge bg-light text-muted">غير محدد</span>
                                @endif
                            </td>
                            <td>{{ ($employee->hire_date ?? $employee->hired_at)?->format('Y-m-d') ?? '—' }}</td>
                            <td class="text-center align-middle">
                                @php $empMenuId = 'hr-emp-actions-'.$employee->id; @endphp
                                <x-erp-actions-dropdown :menu-id="$empMenuId">
                                    <a href="{{ route('hr.employees.edit', $employee) }}"
                                       class="erp-menu-item flex items-center gap-3 px-3 py-2.5 text-sm text-gray-800 transition hover:bg-gray-50 text-decoration-none"
                                       role="menuitem">
                                        <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-gray-100 text-gray-600">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16"><path d="M12.146.146a.5.5 0 0 1 .708 0l3 3a.5.5 0 0 1 0 .708l-9.5 9.5a.5.5 0 0 1-.168.11l-5 2a.5.5 0 0 1-.65-.65l2-5a.5.5 0 0 1 .11-.168zM11.207 2L3 10.207V12h1.793L13 3.793z"/></svg>
                                        </span>
                                        <span class="flex-1 text-right font-medium leading-snug">تعديل الموظف</span>
                                    </a>
                                </x-erp-actions-dropdown>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="text-center text-muted py-4">
                                لا توجد بيانات موظفين حتى الآن.
                                <a href="{{ route('hr.employees.create') }}">أضف أول موظف</a>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($employees->hasPages())
        <div class="card-footer">
            {{ $employees->links() }}
        </div>
    @endif
</div>
@endsection
