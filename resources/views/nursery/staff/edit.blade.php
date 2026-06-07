@extends('layouts.nursery')

@section('title', 'تعديل — '.$employee->name)

@section('content')
<div class="w-full space-y-4" dir="rtl">
    <h1 class="text-2xl font-extrabold text-orange-950">تعديل الموظف</h1>
    <p class="text-sm text-orange-800/80">{{ $employee->code }}</p>
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
