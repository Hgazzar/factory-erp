@extends('layouts.nursery')

@section('title', 'تعديل — '.$employee->name)

@section('content')
<div class="w-full space-y-4" dir="rtl">
    @include('nursery.partials.staff-login-credentials')

    <div class="flex flex-wrap items-start justify-between gap-3">
        <div>
            <h1 class="text-2xl font-extrabold text-teal-950">تعديل الموظف</h1>
            <p class="text-sm text-teal-800/80">{{ $employee->code }}</p>
        </div>
        @if($employee->linked_user_id)
            <div class="flex items-center gap-2">
                <form method="post" action="{{ route('nursery.staff.reset-login-password', $employee) }}"
                      onsubmit="return confirm('سيتم إنشاء كلمة مرور مؤقتة جديدة وإبطال القديمة. متابعة؟')">
                    @csrf
                    <button type="submit" class="nursery-btn nursery-btn-soft text-sm">إعادة كلمة مرور الدخول</button>
                </form>
                <x-info field="nursery.staff_reset_login_password" />
            </div>
        @endif
    </div>
    @include('nursery.partials.staff-form', [
        'employee' => $employee,
        'formAction' => route('nursery.staff.update', $employee),
        'formMethod' => 'PUT',
        'submitLabel' => 'حفظ التعديلات',
        'jobRoleOptions' => $jobRoleOptions,
        'genderOptions' => $genderOptions,
        'systemRoleOptions' => $systemRoleOptions,
        'permissionGroups' => $permissionGroups,
        'grantableKeys' => $grantableKeys,
        'canGrantAll' => $canGrantAll,
    ])
</div>
@endsection
