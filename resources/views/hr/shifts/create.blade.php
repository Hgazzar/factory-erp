@extends('layouts.app')

@section('title', 'وردية جديدة - '.config('app.name'))

@section('breadcrumb')
    <a href="{{ route('hr.shifts.index') }}" class="text-gray-500 hover:text-indigo-600">ورديات العمل</a>
    <span>›</span>
    <span class="text-indigo-900 font-semibold">جديد</span>
@endsection

@section('content')
<div class="max-w-3xl space-y-6" dir="rtl">
    <div class="flex items-center justify-between">
        <h1 class="text-2xl font-bold text-gray-900">وردية جديدة</h1>
        <a href="{{ route('hr.shifts.index') }}" class="rounded-lg border border-gray-200 px-4 py-2 text-sm hover:bg-gray-50">رجوع</a>
    </div>

    @include('hr.shifts._form', ['shift' => null, 'action' => route('hr.shifts.store'), 'method' => 'POST'])
</div>
@endsection
