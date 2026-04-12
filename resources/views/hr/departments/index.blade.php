@extends('layouts.app')

@section('title', 'الأقسام - MIRADA ERP')

@section('breadcrumb')
    <a href="{{ route('dashboard') }}" class="text-gray-500 hover:text-indigo-600">الرئيسية</a>
    <span>›</span>
    <a href="{{ route('hr.dashboard') }}" class="text-gray-500 hover:text-indigo-600">الموارد البشرية</a>
    <span>›</span>
    <span class="text-indigo-900 font-semibold truncate">الأقسام</span>
@endsection

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h4 mb-1">الأقسام</h1>
        <p class="text-muted mb-0 small">تعريف الأقسام التنظيمية ومدير القسم.</p>
    </div>
    <a href="{{ route('hr.departments.create') }}" class="btn btn-primary rounded-lg">قسم جديد</a>
</div>

<div class="card shadow-sm border-0 rounded-lg">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>الاسم <x-info field="hr.dept_name" /></th>
                        <th style="width: 220px;">المدير <x-info field="hr.dept_manager" /></th>
                        <th style="width: 120px;" class="text-center">عدد الموظفين</th>
                        <th style="width: 160px;">الإجراءات</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($departments as $department)
                        <tr>
                            <td class="fw-semibold">{{ $department->name }}</td>
                            <td>{{ $department->manager?->name ?? '—' }}</td>
                            <td class="text-center">{{ $department->employees_count }}</td>
                            <td class="d-flex gap-2 flex-wrap">
                                <a href="{{ route('hr.departments.edit', $department) }}" class="btn btn-sm btn-outline-primary rounded-lg">تعديل</a>
                                <form method="POST" action="{{ route('hr.departments.destroy', $department) }}" onsubmit="return confirm('حذف القسم؟');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger rounded-lg">حذف</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="text-center text-muted py-4">
                                لا توجد أقسام.
                                <a href="{{ route('hr.departments.create') }}">أضف قسمًا</a>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($departments->hasPages())
        <div class="card-footer">{{ $departments->links() }}</div>
    @endif
</div>
@endsection
