@extends('layouts.clinic')

@section('title', $patient->name.' — '.config('app.name'))

@section('content')
<div class="max-w-lg mx-auto text-center space-y-4 py-16" dir="rtl">
    <div class="rounded-2xl border border-amber-200 bg-amber-50 p-8">
        <h1 class="text-xl font-bold text-gray-900">{{ $patient->name }}</h1>
        <p class="text-sm text-gray-500 mt-1 font-mono">{{ $patient->code }}</p>
        <p class="mt-4 text-sm text-amber-900"><x-info field="clinic.receptionist_limited" /> ملف المريض الطبي محمي — صلاحية الاستقبال تقتصر على الحجوزات والتحصيل.</p>
        <div class="mt-6 flex flex-wrap items-center justify-center gap-3">
            <x-clinic-manual-prescription-capture :patient-id="$patient->id" size="md" />
        </div>
        <a href="{{ route('clinic.appointments.index') }}" class="inline-block mt-6 text-teal-700 text-sm font-medium">← العودة للحجوزات</a>
    </div>
</div>
@endsection
