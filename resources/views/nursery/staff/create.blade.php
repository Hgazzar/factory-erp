@extends('layouts.nursery')

@section('title', 'إضافة موظف')

@section('content')
<div class="w-full space-y-4" dir="rtl">
    <h1 class="text-2xl font-extrabold text-teal-950">إضافة موظف</h1>
    @include('nursery.partials.staff-form', [
        'employee' => null,
        'formAction' => route('nursery.staff.store'),
        'formMethod' => 'POST',
        'submitLabel' => 'حفظ',
        'jobRoleOptions' => $jobRoleOptions,
        'genderOptions' => $genderOptions,
        'systemRoleOptions' => $systemRoleOptions,
        'permissionGroups' => $permissionGroups,
        'grantableKeys' => $grantableKeys,
        'canGrantAll' => $canGrantAll,
    ])
</div>
@endsection
