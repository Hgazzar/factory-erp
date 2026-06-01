@extends('layouts.app')

@section('title', 'تعديل وردية - '.config('app.name'))

@section('breadcrumb')
    <a href="{{ route('hr.shifts.index') }}" class="text-gray-500 hover:text-indigo-600">ورديات العمل</a>
    <span>›</span>
    <span class="text-indigo-900 font-semibold">{{ $shift->code }}</span>
@endsection

@section('content')
<div class="max-w-3xl space-y-6" dir="rtl">
    <div class="flex items-center justify-between">
        <h1 class="text-2xl font-bold text-gray-900">تعديل الوردية</h1>
        <a href="{{ route('hr.shifts.show', $shift) }}" class="rounded-lg border border-gray-200 px-4 py-2 text-sm hover:bg-gray-50">عرض</a>
    </div>

    @include('hr.shifts._form', ['shift' => $shift, 'action' => route('hr.shifts.update', $shift), 'method' => 'PUT'])
</div>
@endsection
