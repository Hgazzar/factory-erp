@extends('layouts.app')

@section('title', 'وردية '.$shift->code.' - '.config('app.name'))

@section('breadcrumb')
    <a href="{{ route('hr.shifts.index') }}" class="text-gray-500 hover:text-indigo-600">ورديات العمل</a>
    <span>›</span>
    <span class="text-indigo-900 font-semibold">{{ $shift->code }}</span>
@endsection

@section('content')
<div class="max-w-3xl space-y-6" dir="rtl">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">{{ $shift->name_ar }}</h1>
            <p class="mt-1 text-sm text-gray-500 font-mono">{{ $shift->code }}</p>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('hr.shifts.edit', $shift) }}" class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700">تعديل</a>
            <a href="{{ route('hr.shifts.index') }}" class="rounded-lg border border-gray-200 px-4 py-2 text-sm hover:bg-gray-50">القائمة</a>
        </div>
    </div>

    <section class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm">
        <dl class="grid grid-cols-1 gap-4 sm:grid-cols-2 text-sm">
            <div class="flex justify-between gap-4 border-b border-gray-100 pb-3"><dt class="text-gray-500">البداية</dt><dd class="font-medium">{{ optional($shift->start_time)->format('H:i') }}</dd></div>
            <div class="flex justify-between gap-4 border-b border-gray-100 pb-3"><dt class="text-gray-500">النهاية</dt><dd class="font-medium">{{ optional($shift->end_time)->format('H:i') }}</dd></div>
            <div class="flex justify-between gap-4 border-b border-gray-100 pb-3"><dt class="text-gray-500">دقائق السماح</dt><dd class="font-medium">{{ (int) ($shift->grace_minutes ?? 0) }} د</dd></div>
            <div class="flex justify-between gap-4 border-b border-gray-100 pb-3"><dt class="text-gray-500">وردية ليلية</dt><dd>{{ $shift->is_night ? 'نعم' : 'لا' }}</dd></div>
            <div class="flex justify-between gap-4 border-b border-gray-100 pb-3"><dt class="text-gray-500">الحالة</dt><dd>{{ $shift->is_active ? 'نشط' : 'غير نشط' }}</dd></div>
            <div class="flex justify-between gap-4 border-b border-gray-100 pb-3"><dt class="text-gray-500">موظفون مرتبطون</dt><dd>{{ $shift->employees_count ?? 0 }}</dd></div>
            <div class="flex justify-between gap-4 border-b border-gray-100 pb-3"><dt class="text-gray-500">ورديات إنتاج</dt><dd>{{ $shift->production_shifts_count ?? 0 }}</dd></div>
            @if($shift->name_en)
                <div class="flex justify-between gap-4 border-b border-gray-100 pb-3 sm:col-span-2"><dt class="text-gray-500">الاسم بالإنجليزية</dt><dd>{{ $shift->name_en }}</dd></div>
            @endif
        </dl>
    </section>
</div>
@endsection
