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
                        <th style="width: 120px;">الإجراءات</th>
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
                            <td>
                                <a href="{{ route('hr.employees.edit', $employee) }}" class="btn btn-sm btn-outline-primary rounded-lg">
                                    تعديل
                                </a>
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
