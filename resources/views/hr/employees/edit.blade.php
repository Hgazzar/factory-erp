@extends('layouts.app')

@section('title', 'تعديل موظف - MIRADA ERP')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h4 mb-1">تعديل بيانات الموظف</h1>
        <p class="text-muted mb-0 small">تحديث بيانات الموظف والقسم وربط الصلاحيات عند وجود مستخدم.</p>
    </div>
    <a href="{{ route('hr.employees.index') }}" class="btn btn-outline-secondary rounded-lg">
        الرجوع لقائمة الموظفين
    </a>
</div>

<div class="card shadow-sm border-0 rounded-lg">
    <div class="card-body p-4">
        <form method="POST" action="{{ route('hr.employees.update', $employee) }}">
            @csrf
            @method('PUT')

            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label fw-semibold">كود الموظف <span class="text-danger">*</span></label>
                    <input type="text" name="code" class="form-control @error('code') is-invalid @enderror"
                           value="{{ old('code', $employee->code) }}" required maxlength="30">
                    @error('code')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-semibold">اسم الموظف <span class="text-danger">*</span></label>
                    <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
                           value="{{ old('name', $employee->name) }}" required>
                    @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-4">
                    <label class="form-label">البريد الإلكتروني <x-info field="hr.employee_email" /></label>
                    <input type="email" name="email" class="form-control @error('email') is-invalid @enderror"
                           value="{{ old('email', $employee->email) }}">
                    @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-md-4">
                    <label class="form-label fw-semibold">المستخدم (User)</label>
                    <select name="linked_user_id" class="form-select @error('linked_user_id') is-invalid @enderror">
                        <option value="">-- بدون ربط مستخدم --</option>
                        @foreach($users as $user)
                            <option value="{{ $user->id }}"
                                    @selected(old('linked_user_id', $employee->linked_user_id) == $user->id)>
                                {{ $user->email }} ({{ $user->name }})
                            </option>
                        @endforeach
                    </select>
                    @error('linked_user_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-md-4">
                    <label class="form-label">القسم <x-info field="hr.employee_department" /></label>
                    <select name="department_id" class="form-select @error('department_id') is-invalid @enderror">
                        <option value="">— بدون قسم —</option>
                        @foreach($departments as $dept)
                            <option value="{{ $dept->id }}" @selected(old('department_id', $employee->department_id) == $dept->id)>{{ $dept->name }}</option>
                        @endforeach
                    </select>
                    @error('department_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-4">
                    <label class="form-label">المنصب / المسمى الوظيفي <x-info field="hr.employee_position" /></label>
                    <input type="text" name="position" class="form-control @error('position') is-invalid @enderror"
                           value="{{ old('position', $employee->position ?? $employee->job_title) }}">
                    @error('position')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-md-4">
                    <label class="form-label">الجنس <x-info field="hr.employee_gender" /></label>
                    @php $g = old('gender', $employee->gender); @endphp
                    <select name="gender" class="form-select @error('gender') is-invalid @enderror">
                        <option value="">— غير محدد —</option>
                        <option value="male" @selected($g === 'male')>ذكر</option>
                        <option value="female" @selected($g === 'female')>أنثى</option>
                        <option value="other" @selected($g === 'other')>آخر</option>
                    </select>
                    @error('gender')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-semibold">الحالة <span class="text-danger">*</span> <x-info field="hr.employee_status" /></label>
                    @php $st = old('status', $employee->status ?? 'active'); @endphp
                    <select name="status" class="form-select @error('status') is-invalid @enderror" required>
                        <option value="active" @selected($st === 'active')>نشط</option>
                        <option value="inactive" @selected($st === 'inactive')>غير نشط</option>
                        <option value="on_leave" @selected($st === 'on_leave')>في إجازة</option>
                    </select>
                    @error('status')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-4">
                    <label class="form-label">الراتب الأساسي</label>
                    <input type="number" inputmode="decimal" name="base_salary" class="form-control @error('base_salary') is-invalid @enderror"
                           value="{{ old('base_salary', $employee->base_salary) }}" min="0" step="any">
                    @error('base_salary')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-md-4">
                    <label class="form-label">تاريخ التعيين <x-info field="hr.employee_hire_date" /></label>
                    <input type="date" name="hire_date" class="form-control @error('hire_date') is-invalid @enderror"
                           value="{{ old('hire_date', optional($employee->hire_date ?? $employee->hired_at)->format('Y-m-d')) }}">
                    @error('hire_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-semibold">الدور (Role) <span class="text-danger">*</span></label>
                    @php $currentRole = old('role', $employee->linkedUser?->role); @endphp
                    <select name="role" class="form-select @error('role') is-invalid @enderror" required>
                        <option value="">-- اختر الدور --</option>
                        <option value="admin" @selected($currentRole === 'admin')>Admin</option>
                        <option value="supervisor" @selected($currentRole === 'supervisor')>Supervisor</option>
                        <option value="worker" @selected($currentRole === 'worker')>Worker</option>
                    </select>
                    @error('role')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
            </div>

            <hr class="my-4">

            <div class="d-flex gap-3">
                <button type="submit" class="btn btn-primary px-5 rounded-lg">تحديث الموظف</button>
                <a href="{{ route('hr.employees.index') }}" class="btn btn-light border px-4 rounded-lg">إلغاء</a>
            </div>
        </form>
    </div>
</div>
@endsection
