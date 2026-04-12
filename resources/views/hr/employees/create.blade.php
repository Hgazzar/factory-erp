@extends('layouts.app')

@section('title', 'إضافة موظف - MIRADA ERP')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h4 mb-1">إضافة موظف جديد</h1>
        <p class="text-muted mb-0 small">بيانات الموظف، القسم، والربط الاختياري بحساب الدخول والصلاحيات.</p>
    </div>
    <a href="{{ route('hr.employees.index') }}" class="btn btn-outline-secondary rounded-lg">
        الرجوع لقائمة الموظفين
    </a>
</div>

<div class="card shadow-sm border-0 rounded-lg">
    <div class="card-body p-4">
        <form method="POST" action="{{ route('hr.employees.store') }}">
            @csrf

            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label fw-semibold">كود الموظف <span class="text-danger">*</span></label>
                    <input type="text" name="code" class="form-control @error('code') is-invalid @enderror"
                           value="{{ old('code') }}" required maxlength="30">
                    @error('code')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-semibold">اسم الموظف <span class="text-danger">*</span></label>
                    <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
                           value="{{ old('name') }}" required>
                    @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-4">
                    <label class="form-label">البريد الإلكتروني <x-info field="hr.employee_email" /></label>
                    <input type="email" name="email" class="form-control @error('email') is-invalid @enderror"
                           value="{{ old('email') }}">
                    @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-md-4">
                    <label class="form-label fw-semibold">المستخدم (User)</label>
                    <select name="linked_user_id" class="form-select @error('linked_user_id') is-invalid @enderror">
                        <option value="">-- بدون ربط مستخدم --</option>
                        @foreach($users as $user)
                            <option value="{{ $user->id }}" @selected(old('linked_user_id') == $user->id)>
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
                            <option value="{{ $dept->id }}" @selected(old('department_id') == $dept->id)>{{ $dept->name }}</option>
                        @endforeach
                    </select>
                    @error('department_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-4">
                    <label class="form-label">المنصب / المسمى الوظيفي <x-info field="hr.employee_position" /></label>
                    <input type="text" name="position" class="form-control @error('position') is-invalid @enderror"
                           value="{{ old('position') }}">
                    @error('position')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-md-4">
                    <label class="form-label">الجنس <x-info field="hr.employee_gender" /></label>
                    <select name="gender" class="form-select @error('gender') is-invalid @enderror">
                        <option value="">— غير محدد —</option>
                        <option value="male" @selected(old('gender') === 'male')>ذكر</option>
                        <option value="female" @selected(old('gender') === 'female')>أنثى</option>
                        <option value="other" @selected(old('gender') === 'other')>آخر</option>
                    </select>
                    @error('gender')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-semibold">الحالة <span class="text-danger">*</span> <x-info field="hr.employee_status" /></label>
                    <select name="status" class="form-select @error('status') is-invalid @enderror" required>
                        <option value="active" @selected(old('status', 'active') === 'active')>نشط</option>
                        <option value="inactive" @selected(old('status') === 'inactive')>غير نشط</option>
                        <option value="on_leave" @selected(old('status') === 'on_leave')>في إجازة</option>
                    </select>
                    @error('status')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-4">
                    <label class="form-label">الراتب الأساسي</label>
                    <input type="number" inputmode="decimal" name="base_salary" class="form-control @error('base_salary') is-invalid @enderror"
                           value="{{ old('base_salary', 0) }}" min="0" step="any">
                    @error('base_salary')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-md-4">
                    <label class="form-label">تاريخ التعيين <x-info field="hr.employee_hire_date" /></label>
                    <input type="date" name="hire_date" class="form-control @error('hire_date') is-invalid @enderror"
                           value="{{ old('hire_date') }}">
                    @error('hire_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-semibold">الدور (Role) <span class="text-danger">*</span></label>
                    <select name="role" class="form-select @error('role') is-invalid @enderror" required>
                        <option value="">-- اختر الدور --</option>
                        <option value="admin" @selected(old('role') === 'admin')>Admin</option>
                        <option value="supervisor" @selected(old('role') === 'supervisor')>Supervisor</option>
                        <option value="worker" @selected(old('role') === 'worker')>Worker</option>
                    </select>
                    @error('role')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
            </div>

            <hr class="my-4">

            <div class="d-flex gap-3">
                <button type="submit" class="btn btn-primary px-5 rounded-lg">حفظ الموظف</button>
                <a href="{{ route('hr.employees.index') }}" class="btn btn-light border px-4 rounded-lg">إلغاء</a>
            </div>
        </form>
    </div>
</div>
@endsection
