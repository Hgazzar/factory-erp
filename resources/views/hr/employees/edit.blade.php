@extends('layouts.app')

@section('title', 'تعديل موظف - MIRADA ERP')

@php
    $genderOptions = [
        ['value' => 'male', 'label' => 'ذكر'],
        ['value' => 'female', 'label' => 'أنثى'],
        ['value' => 'other', 'label' => 'آخر'],
    ];
    $maritalOptions = [
        ['value' => 'single', 'label' => 'أعزب'],
        ['value' => 'married', 'label' => 'متزوج'],
        ['value' => 'divorced', 'label' => 'مطلق'],
        ['value' => 'widowed', 'label' => 'أرمل'],
    ];
    $employmentTypeOptions = [
        ['value' => 'full_time', 'label' => 'دوام كامل'],
        ['value' => 'part_time', 'label' => 'دوام جزئي'],
        ['value' => 'contract', 'label' => 'عقد'],
        ['value' => 'temporary', 'label' => 'مؤقت'],
        ['value' => 'intern', 'label' => 'متدرب'],
    ];
    $statusOptions = [
        ['value' => 'active', 'label' => 'نشط'],
        ['value' => 'inactive', 'label' => 'غير نشط'],
        ['value' => 'on_leave', 'label' => 'في إجازة'],
    ];
    $roleOptions = [
        ['value' => 'admin', 'label' => 'Admin'],
        ['value' => 'supervisor', 'label' => 'Supervisor'],
        ['value' => 'worker', 'label' => 'Worker'],
    ];
    $departmentSelectOptions = $departments->map(fn ($d) => ['value' => (string) $d->id, 'label' => $d->name])->values()->all();
    $userSelectOptions = $users->map(fn ($u) => ['value' => (string) $u->id, 'label' => $u->email.' ('.$u->name.')'])->values()->all();
    $salaryTypeOptions = \App\Models\Employee::salaryTypeSelectOptions();
    $attendancePolicyOptions = \App\Models\Employee::attendancePolicySelectOptions();
@endphp

@section('content')
<div class="max-w-full space-y-6" dir="rtl">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">تعديل بيانات الموظف</h1>
            <p class="mt-1 text-sm text-gray-500">{{ $employee->name }} — تحديث المعلومات الشخصية والوظيفية والمستندات.</p>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <a href="{{ route('hr.employees.show', $employee) }}" class="inline-flex items-center rounded-lg border border-gray-200 bg-white px-4 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50">عرض الموظف</a>
            <a href="{{ route('hr.employees.index') }}" class="inline-flex items-center rounded-lg border border-gray-200 bg-white px-4 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50">الرجوع للقائمة</a>
        </div>
    </div>

    <form method="POST" action="{{ route('hr.employees.update', $employee) }}" enctype="multipart/form-data" class="space-y-6">
        @csrf
        @method('PUT')

        <div x-data="{ tab: 'personal' }" class="space-y-6">
            <nav class="flex flex-wrap gap-2 rounded-lg border border-gray-200 bg-white p-2 shadow-sm" aria-label="أقسام النموذج">
                <button type="button" @click="tab = 'personal'" :class="tab === 'personal' ? 'bg-indigo-600 text-white shadow-sm' : 'bg-gray-50 text-gray-700 hover:bg-gray-100'" class="rounded-lg px-4 py-2.5 text-sm font-semibold transition">البيانات الشخصية والتواصل</button>
                <button type="button" @click="tab = 'job'" :class="tab === 'job' ? 'bg-indigo-600 text-white shadow-sm' : 'bg-gray-50 text-gray-700 hover:bg-gray-100'" class="rounded-lg px-4 py-2.5 text-sm font-semibold transition">الوظيفة والعمل</button>
                <button type="button" @click="tab = 'finance'" :class="tab === 'finance' ? 'bg-indigo-600 text-white shadow-sm' : 'bg-gray-50 text-gray-700 hover:bg-gray-100'" class="rounded-lg px-4 py-2.5 text-sm font-semibold transition">المالية والبنك</button>
                <button type="button" @click="tab = 'files'" :class="tab === 'files' ? 'bg-indigo-600 text-white shadow-sm' : 'bg-gray-50 text-gray-700 hover:bg-gray-100'" class="rounded-lg px-4 py-2.5 text-sm font-semibold transition">المرفقات</button>
            </nav>

            <div x-show="tab === 'personal'" x-cloak class="space-y-6">
        <section class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm sm:p-6">
            <h2 class="mb-5 border-b border-gray-100 pb-3 text-lg font-bold text-gray-900">المعلومات الشخصية</h2>
            <div class="grid grid-cols-1 gap-6 md:grid-cols-3">
                <div>
                    <label class="mb-1.5 block text-sm font-semibold text-gray-800">الاسم الأول <span class="text-red-600">*</span> <x-info field="hr.employee_first_name" /></label>
                    <input type="text" name="first_name" value="{{ old('first_name', $employee->first_name) }}" required class="w-full rounded-lg border border-gray-200 bg-white px-3 py-2.5 text-sm @error('first_name') border-red-500 ring-1 ring-red-200 @enderror">
                    @error('first_name')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-semibold text-gray-800">الاسم الأوسط <span class="text-red-600">*</span> <x-info field="hr.employee_middle_name" /></label>
                    <input type="text" name="middle_name" value="{{ old('middle_name', $employee->middle_name) }}" required class="w-full rounded-lg border border-gray-200 bg-white px-3 py-2.5 text-sm @error('middle_name') border-red-500 ring-1 ring-red-200 @enderror">
                    @error('middle_name')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-semibold text-gray-800">اسم العائلة <span class="text-red-600">*</span> <x-info field="hr.employee_last_name" /></label>
                    <input type="text" name="last_name" value="{{ old('last_name', $employee->last_name) }}" required class="w-full rounded-lg border border-gray-200 bg-white px-3 py-2.5 text-sm @error('last_name') border-red-500 ring-1 ring-red-200 @enderror">
                    @error('last_name')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label class="mb-1.5 block text-sm font-semibold text-gray-800">كود الموظف <span class="text-red-600">*</span></label>
                    <input type="text" name="code" value="{{ old('code', $employee->code) }}" required maxlength="30" class="w-full rounded-lg border border-gray-200 bg-white px-3 py-2.5 font-mono text-sm @error('code') border-red-500 ring-1 ring-red-200 @enderror">
                    @error('code')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-semibold text-gray-800">نفس كود الموظف (للبصمة) <x-info field="hr.employee_attendance_device_id" /></label>
                    <input type="text" name="attendance_device_id" value="{{ old('attendance_device_id', $employee->attendance_device_id) }}" maxlength="64" class="w-full rounded-lg border border-gray-200 bg-white px-3 py-2.5 font-mono text-sm @error('attendance_device_id') border-red-500 ring-1 ring-red-200 @enderror" placeholder="فارغ = نسخ تلقائي من كود الموظف">
                    @error('attendance_device_id')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label class="mb-1.5 block text-sm font-semibold text-gray-800">الجنس <x-info field="hr.employee_gender" /></label>
                    <x-custom-select name="gender" :options="$genderOptions" :value="old('gender', $employee->gender ?? '')" placeholder="اختر" empty-label="اختر" :empty-option="false" :error="$errors->has('gender')" />
                    @error('gender')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-semibold text-gray-800">تاريخ الميلاد <x-info field="hr.employee_birth_date" /></label>
                    <input type="date" name="birth_date" value="{{ old('birth_date', optional($employee->birth_date)->format('Y-m-d')) }}" class="w-full rounded-lg border border-gray-200 bg-white px-3 py-2.5 text-sm @error('birth_date') border-red-500 ring-1 ring-red-200 @enderror">
                    @error('birth_date')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-semibold text-gray-800">الحالة الاجتماعية <x-info field="hr.employee_marital_status" /></label>
                    <x-custom-select name="marital_status" :options="$maritalOptions" :value="old('marital_status', $employee->marital_status ?? '')" placeholder="اختر" empty-label="اختر" :empty-option="false" :error="$errors->has('marital_status')" />
                    @error('marital_status')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label class="mb-1.5 block text-sm font-semibold text-gray-800">الجنسية <x-info field="hr.employee_nationality" /></label>
                    <input type="text" name="nationality" value="{{ old('nationality', $employee->nationality) }}" class="w-full rounded-lg border border-gray-200 bg-white px-3 py-2.5 text-sm @error('nationality') border-red-500 ring-1 ring-red-200 @enderror">
                    @error('nationality')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-semibold text-gray-800">رقم الهوية <x-info field="hr.employee_id_number" /></label>
                    <input type="text" name="id_number" value="{{ old('id_number', $employee->id_number) }}" class="w-full rounded-lg border border-gray-200 bg-white px-3 py-2.5 text-sm @error('id_number') border-red-500 ring-1 ring-red-200 @enderror">
                    @error('id_number')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-semibold text-gray-800">رقم جواز السفر <x-info field="hr.employee_passport_number" /></label>
                    <input type="text" name="passport_number" value="{{ old('passport_number', $employee->passport_number) }}" class="w-full rounded-lg border border-gray-200 bg-white px-3 py-2.5 text-sm @error('passport_number') border-red-500 ring-1 ring-red-200 @enderror">
                    @error('passport_number')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
            </div>
        </section>

        <section class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm sm:p-6">
            <h2 class="mb-5 border-b border-gray-100 pb-3 text-lg font-bold text-gray-900">معلومات الاتصال والعنوان</h2>
            <div class="grid grid-cols-1 gap-6 md:grid-cols-3">
                <div>
                    <label class="mb-1.5 block text-sm font-semibold text-gray-800">البريد الوظيفي <x-info field="hr.employee_email" /></label>
                    <input type="email" name="email" value="{{ old('email', $employee->email) }}" class="w-full rounded-lg border border-gray-200 bg-white px-3 py-2.5 text-sm @error('email') border-red-500 ring-1 ring-red-200 @enderror">
                    @error('email')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-semibold text-gray-800">البريد الشخصي <x-info field="hr.employee_personal_email" /></label>
                    <input type="email" name="personal_email" value="{{ old('personal_email', $employee->personal_email) }}" class="w-full rounded-lg border border-gray-200 bg-white px-3 py-2.5 text-sm @error('personal_email') border-red-500 ring-1 ring-red-200 @enderror">
                    @error('personal_email')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-semibold text-gray-800">الجوال <x-info field="hr.employee_mobile" /></label>
                    <input type="text" name="mobile" value="{{ old('mobile', $employee->mobile) }}" class="w-full rounded-lg border border-gray-200 bg-white px-3 py-2.5 text-sm @error('mobile') border-red-500 ring-1 ring-red-200 @enderror">
                    @error('mobile')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-semibold text-gray-800">الهاتف</label>
                    <input type="text" name="phone" value="{{ old('phone', $employee->phone) }}" class="w-full rounded-lg border border-gray-200 bg-white px-3 py-2.5 text-sm @error('phone') border-red-500 ring-1 ring-red-200 @enderror">
                    @error('phone')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
                <div class="md:col-span-2">
                    <label class="mb-1.5 block text-sm font-semibold text-gray-800">العنوان</label>
                    <input type="text" name="address" value="{{ old('address', $employee->address) }}" class="w-full rounded-lg border border-gray-200 bg-white px-3 py-2.5 text-sm @error('address') border-red-500 ring-1 ring-red-200 @enderror">
                    @error('address')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-semibold text-gray-800">المدينة</label>
                    <input type="text" name="city" value="{{ old('city', $employee->city) }}" class="w-full rounded-lg border border-gray-200 bg-white px-3 py-2.5 text-sm @error('city') border-red-500 ring-1 ring-red-200 @enderror">
                    @error('city')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-semibold text-gray-800">المنطقة</label>
                    <input type="text" name="region" value="{{ old('region', $employee->region) }}" class="w-full rounded-lg border border-gray-200 bg-white px-3 py-2.5 text-sm @error('region') border-red-500 ring-1 ring-red-200 @enderror">
                    @error('region')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-semibold text-gray-800">الرمز البريدي</label>
                    <input type="text" name="postal_code" value="{{ old('postal_code', $employee->postal_code) }}" class="w-full rounded-lg border border-gray-200 bg-white px-3 py-2.5 text-sm @error('postal_code') border-red-500 ring-1 ring-red-200 @enderror">
                    @error('postal_code')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-semibold text-gray-800">الدولة</label>
                    <input type="text" name="country" value="{{ old('country', $employee->country) }}" class="w-full rounded-lg border border-gray-200 bg-white px-3 py-2.5 text-sm @error('country') border-red-500 ring-1 ring-red-200 @enderror">
                    @error('country')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-semibold text-gray-800">جهة اتصال الطوارئ</label>
                    <input type="text" name="emergency_contact_name" value="{{ old('emergency_contact_name', $employee->emergency_contact_name) }}" class="w-full rounded-lg border border-gray-200 bg-white px-3 py-2.5 text-sm @error('emergency_contact_name') border-red-500 ring-1 ring-red-200 @enderror">
                    @error('emergency_contact_name')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-semibold text-gray-800">هاتف الطوارئ</label>
                    <input type="text" name="emergency_contact_phone" value="{{ old('emergency_contact_phone', $employee->emergency_contact_phone) }}" class="w-full rounded-lg border border-gray-200 bg-white px-3 py-2.5 text-sm @error('emergency_contact_phone') border-red-500 ring-1 ring-red-200 @enderror">
                    @error('emergency_contact_phone')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-semibold text-gray-800">صلة القرابة</label>
                    <input type="text" name="emergency_contact_relation" value="{{ old('emergency_contact_relation', $employee->emergency_contact_relation) }}" class="w-full rounded-lg border border-gray-200 bg-white px-3 py-2.5 text-sm @error('emergency_contact_relation') border-red-500 ring-1 ring-red-200 @enderror">
                    @error('emergency_contact_relation')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
            </div>
        </section>
            </div>

            <div x-show="tab === 'job'" x-cloak class="space-y-6">
        <section class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm sm:p-6">
            <h2 class="mb-5 border-b border-gray-100 pb-3 text-lg font-bold text-gray-900">معلومات التوظيف</h2>
            <div class="grid grid-cols-1 gap-6 md:grid-cols-3">
                <div>
                    <label class="mb-1.5 block text-sm font-semibold text-gray-800">نوع التوظيف <x-info field="hr.employee_employment_type" /></label>
                    <x-custom-select name="employment_type" :options="$employmentTypeOptions" :value="old('employment_type', $employee->employment_type ?? 'full_time')" placeholder="اختر" empty-label="اختر" :empty-option="false" :error="$errors->has('employment_type')" />
                    @error('employment_type')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-semibold text-gray-800">القسم <x-info field="hr.employee_department" /></label>
                    <x-custom-select name="department_id" :options="$departmentSelectOptions" :value="old('department_id', (string) ($employee->department_id ?? ''))" placeholder="اختر القسم..." empty-label="بدون قسم" :error="$errors->has('department_id')" />
                    @error('department_id')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-semibold text-gray-800">المسمى الوظيفي <x-info field="hr.employee_position" /></label>
                    <input type="text" name="position" value="{{ old('position', $employee->position ?? $employee->job_title) }}" class="w-full rounded-lg border border-gray-200 bg-white px-3 py-2.5 text-sm @error('position') border-red-500 ring-1 ring-red-200 @enderror">
                    @error('position')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-semibold text-gray-800">تاريخ التعيين <x-info field="hr.employee_hire_date" /></label>
                    <input type="date" name="hire_date" value="{{ old('hire_date', optional($employee->hire_date ?? $employee->hired_at)->format('Y-m-d')) }}" class="w-full rounded-lg border border-gray-200 bg-white px-3 py-2.5 text-sm @error('hire_date') border-red-500 ring-1 ring-red-200 @enderror">
                    @error('hire_date')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-semibold text-gray-800">حالة الموظف <x-info field="hr.employee_status" /></label>
                    <x-custom-select name="status" :options="$statusOptions" :value="old('status', $employee->status ?? 'active')" placeholder="اختر الحالة..." :empty-option="false" :error="$errors->has('status')" />
                    @error('status')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-semibold text-gray-800">المستخدم المرتبط</label>
                    <x-custom-select name="linked_user_id" :options="$userSelectOptions" :value="old('linked_user_id', (string) ($employee->linked_user_id ?? ''))" placeholder="اختياري..." empty-label="بدون ربط مستخدم" :error="$errors->has('linked_user_id')" />
                    @error('linked_user_id')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-semibold text-gray-800">الدور (Role)</label>
                    <x-custom-select name="role" :options="$roleOptions" :value="old('role', $employee->linkedUser?->role)" placeholder="اختر الدور..." :empty-option="false" :error="$errors->has('role')" />
                    @error('role')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
            </div>
        </section>
            </div>

            <div x-show="tab === 'finance'" x-cloak class="space-y-6">
        <section class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm sm:p-6">
            <h2 class="mb-5 border-b border-gray-100 pb-3 text-lg font-bold text-gray-900">الراتب والتعويضات والسياسات</h2>
            <div class="grid grid-cols-1 gap-6 md:grid-cols-3">
                <div>
                    <label class="mb-1.5 block text-sm font-semibold text-gray-800">الراتب الأساسي <x-info field="hr.employee_basic_salary" /></label>
                    <input type="number" step="any" min="0" name="base_salary" value="{{ old('base_salary', $employee->base_salary) }}" class="w-full rounded-lg border border-gray-200 bg-white px-3 py-2.5 text-sm @error('base_salary') border-red-500 ring-1 ring-red-200 @enderror">
                    @error('base_salary')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-semibold text-gray-800">نوع الراتب <x-info field="hr.employee_salary_type" /></label>
                    <x-custom-select name="salary_type" :options="$salaryTypeOptions" :value="old('salary_type', $employee->salary_type ?? 'monthly')" placeholder="اختر..." :empty-option="false" :searchable="false" :error="$errors->has('salary_type')" />
                    @error('salary_type')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-semibold text-gray-800">سياسة الحضور (الخصم) <x-info field="hr.employee_attendance_policy" /></label>
                    <x-custom-select name="attendance_policy" :options="$attendancePolicyOptions" :value="old('attendance_policy', $employee->attendance_policy ?? 'none')" placeholder="اختر..." :empty-option="false" :searchable="false" :error="$errors->has('attendance_policy')" />
                    @error('attendance_policy')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-semibold text-gray-800">رصيد الإجازة السنوية (أيام) <x-info field="hr.employee_annual_balance" /></label>
                    <input type="number" step="0.5" min="0" name="annual_balance" value="{{ old('annual_balance', $employee->annual_balance ?? 21) }}" class="w-full rounded-lg border border-gray-200 bg-white px-3 py-2.5 text-sm @error('annual_balance') border-red-500 ring-1 ring-red-200 @enderror">
                    @error('annual_balance')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-semibold text-gray-800">خصم تأمينات ثابت (شهري/دورة) <x-info field="hr.employee_fixed_insurance_deduction" /></label>
                    <input type="number" step="any" min="0" name="fixed_insurance_deduction" value="{{ old('fixed_insurance_deduction', $employee->fixed_insurance_deduction) }}" class="w-full rounded-lg border border-gray-200 bg-white px-3 py-2.5 text-sm @error('fixed_insurance_deduction') border-red-500 ring-1 ring-red-200 @enderror" placeholder="اختياري">
                    @error('fixed_insurance_deduction')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-semibold text-gray-800">خصم ضريبة ثابت <x-info field="hr.employee_fixed_tax_deduction" /></label>
                    <input type="number" step="any" min="0" name="fixed_tax_deduction" value="{{ old('fixed_tax_deduction', $employee->fixed_tax_deduction) }}" class="w-full rounded-lg border border-gray-200 bg-white px-3 py-2.5 text-sm @error('fixed_tax_deduction') border-red-500 ring-1 ring-red-200 @enderror" placeholder="اختياري">
                    @error('fixed_tax_deduction')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-semibold text-gray-800">بدل سكن</label>
                    <input type="number" step="any" min="0" name="housing_allowance" value="{{ old('housing_allowance', $employee->housing_allowance) }}" class="w-full rounded-lg border border-gray-200 bg-white px-3 py-2.5 text-sm @error('housing_allowance') border-red-500 ring-1 ring-red-200 @enderror">
                    @error('housing_allowance')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-semibold text-gray-800">بدل مواصلات</label>
                    <input type="number" step="any" min="0" name="transport_allowance" value="{{ old('transport_allowance', $employee->transport_allowance) }}" class="w-full rounded-lg border border-gray-200 bg-white px-3 py-2.5 text-sm @error('transport_allowance') border-red-500 ring-1 ring-red-200 @enderror">
                    @error('transport_allowance')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-semibold text-gray-800">بدلات أخرى</label>
                    <input type="number" step="any" min="0" name="other_allowance" value="{{ old('other_allowance', $employee->other_allowance) }}" class="w-full rounded-lg border border-gray-200 bg-white px-3 py-2.5 text-sm @error('other_allowance') border-red-500 ring-1 ring-red-200 @enderror">
                    @error('other_allowance')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-semibold text-gray-800">مركز التكلفة <x-info field="hr.employee_cost_center" /></label>
                    <x-custom-select name="cost_center_id" :options="$costCenterOptions" :value="old('cost_center_id', (string) ($employee->cost_center_id ?? ''))" placeholder="ابحث برمز أو الاسم..." empty-label="بدون مركز تكلفة" :error="$errors->has('cost_center_id')" />
                    @error('cost_center_id')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-semibold text-gray-800">حساب الأجور <x-info field="hr.employee_wage_account" /></label>
                    <x-custom-select name="ledger_account_id" :options="$wageAccountOptions" :value="old('ledger_account_id', (string) ($employee->ledger_account_id ?? ''))" placeholder="ابحث برمز الحساب..." empty-label="بدون حساب أجور" :error="$errors->has('ledger_account_id')" />
                    @error('ledger_account_id')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
            </div>
        </section>

        <section class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm sm:p-6">
            <h2 class="mb-5 border-b border-gray-100 pb-3 text-lg font-bold text-gray-900">المعلومات البنكية والحكومية</h2>
            <div class="grid grid-cols-1 gap-6 md:grid-cols-3">
                <div>
                    <label class="mb-1.5 block text-sm font-semibold text-gray-800">اسم البنك</label>
                    <input type="text" name="bank_name" value="{{ old('bank_name', $employee->bank_name) }}" class="w-full rounded-lg border border-gray-200 bg-white px-3 py-2.5 text-sm @error('bank_name') border-red-500 ring-1 ring-red-200 @enderror">
                    @error('bank_name')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-semibold text-gray-800">رقم الحساب</label>
                    <input type="text" name="bank_account_number" value="{{ old('bank_account_number', $employee->bank_account_number) }}" class="w-full rounded-lg border border-gray-200 bg-white px-3 py-2.5 text-sm @error('bank_account_number') border-red-500 ring-1 ring-red-200 @enderror">
                    @error('bank_account_number')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-semibold text-gray-800">الآيبان</label>
                    <input type="text" name="iban" value="{{ old('iban', $employee->iban) }}" class="w-full rounded-lg border border-gray-200 bg-white px-3 py-2.5 text-sm @error('iban') border-red-500 ring-1 ring-red-200 @enderror">
                    @error('iban')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-semibold text-gray-800">رقم التأمينات الاجتماعية</label>
                    <input type="text" name="social_insurance_number" value="{{ old('social_insurance_number', $employee->social_insurance_number) }}" class="w-full rounded-lg border border-gray-200 bg-white px-3 py-2.5 text-sm @error('social_insurance_number') border-red-500 ring-1 ring-red-200 @enderror">
                    @error('social_insurance_number')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-semibold text-gray-800">الرقم الضريبي</label>
                    <input type="text" name="tax_number" value="{{ old('tax_number', $employee->tax_number) }}" class="w-full rounded-lg border border-gray-200 bg-white px-3 py-2.5 text-sm @error('tax_number') border-red-500 ring-1 ring-red-200 @enderror">
                    @error('tax_number')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-semibold text-gray-800">رقم التأمين الصحي</label>
                    <input type="text" name="insurance_number" value="{{ old('insurance_number', $employee->insurance_number) }}" class="w-full rounded-lg border border-gray-200 bg-white px-3 py-2.5 text-sm @error('insurance_number') border-red-500 ring-1 ring-red-200 @enderror">
                    @error('insurance_number')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
                <div class="md:col-span-3">
                    <label class="mb-1.5 block text-sm font-semibold text-gray-800">ملاحظات</label>
                    <textarea name="notes" rows="3" class="w-full rounded-lg border border-gray-200 bg-white px-3 py-2.5 text-sm @error('notes') border-red-500 ring-1 ring-red-200 @enderror" placeholder="أضف ملاحظات حول هذا الموظف...">{{ old('notes', $employee->notes) }}</textarea>
                    @error('notes')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
            </div>
        </section>
            </div>

            <div x-show="tab === 'files'" x-cloak class="space-y-6">
        <section class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm sm:p-6">
            <x-attachment-handler
                hint-field="hr.employee_attachments"
                title="أوراق التعيين والمستندات"
                :existing="$employee->attachments"
                :show-existing="true"
                :allow-delete="true"
                help-text="يمكن رفع مستندات متعددة مرة واحدة (حتى 20 ملفاً، 10 ميجابايت لكل ملف)."
            />
        </section>
            </div>
        </div>

        <div class="flex flex-wrap items-center justify-end gap-3 border-t border-gray-100 pt-6">
            <a href="{{ route('hr.employees.index') }}" class="inline-flex items-center rounded-lg border border-gray-200 bg-white px-5 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50">إلغاء</a>
            <button type="submit" class="inline-flex items-center rounded-lg bg-blue-600 px-6 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-blue-700">تحديث</button>
        </div>
    </form>
</div>
@endsection
