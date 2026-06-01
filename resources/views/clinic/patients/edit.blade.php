@extends('layouts.clinic')

@section('title', 'تعديل '.$patient->name.' — '.config('app.name'))

@section('content')
<div class="max-w-2xl mx-auto space-y-6" dir="rtl">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <h1 class="text-2xl font-bold text-teal-950"><x-info field="clinic.edit_patient" /> تعديل بيانات المريض</h1>
            <p class="text-sm text-gray-500 font-mono mt-1">{{ $patient->code }}</p>
        </div>
        <a href="{{ route('clinic.patients.show', $patient) }}" class="rounded-lg border border-gray-200 bg-white px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">رجوع للملف</a>
    </div>

    @if($errors->any())
        <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
            <ul class="list-disc pr-4">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
        </div>
    @endif

    <form method="POST" action="{{ route('clinic.patients.update', $patient) }}"
          class="rounded-2xl border border-teal-100 bg-white p-6 shadow-sm space-y-5">
        @csrf
        @method('PUT')

        <section class="space-y-4">
            <h2 class="text-sm font-semibold text-teal-900 border-b border-teal-50 pb-2">البيانات الأساسية</h2>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1"><x-info field="clinic.patient_name" /> الاسم</label>
                <input type="text" name="name" value="{{ old('name', $patient->name) }}" required
                       class="w-full rounded-lg border-gray-300 focus:border-teal-500 focus:ring-teal-500">
            </div>
            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1"><x-info field="clinic.patient_phone" /> الهاتف</label>
                    <input type="text" name="phone" value="{{ old('phone', $patient->phone) }}" dir="ltr"
                           class="w-full rounded-lg border-gray-300 focus:border-teal-500 focus:ring-teal-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1"><x-info field="clinic.national_id" /> الرقم القومي</label>
                    <input type="text" name="national_id" value="{{ old('national_id', $patient->national_id) }}" dir="ltr"
                           class="w-full rounded-lg border-gray-300 focus:border-teal-500 focus:ring-teal-500">
                </div>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1"><x-info field="clinic.blood_type" /> فصيلة الدم</label>
                <x-searchable-select name="blood_type" id="patient_blood_type" :options="$bloodTypeOptions"
                    :value="old('blood_type', $patient->blood_type ?? '')" :searchable="false" empty-label="—" />
            </div>
        </section>

        @if($canClinical ?? false)
        <section class="space-y-4">
            <h2 class="text-sm font-semibold text-teal-900 border-b border-teal-50 pb-2"><x-info field="clinic.clinical_profile" /> الملف الطبي</h2>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1"><x-info field="clinic.allergies" /> الحساسية</label>
                <textarea name="allergies" rows="2" class="w-full rounded-lg border-gray-300 text-sm">{{ old('allergies', $patient->allergies) }}</textarea>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1"><x-info field="clinic.chronic_conditions" /> الأمراض المزمنة</label>
                <textarea name="chronic_conditions" rows="2" class="w-full rounded-lg border-gray-300 text-sm">{{ old('chronic_conditions', $patient->chronic_conditions) }}</textarea>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1"><x-info field="clinic.medical_history" /> التاريخ الطبي</label>
                <textarea name="medical_history_summary" rows="3" class="w-full rounded-lg border-gray-300 text-sm">{{ old('medical_history_summary', $patient->medical_history_summary) }}</textarea>
            </div>
        </section>
        @endif

        <div class="flex justify-end gap-2 pt-2 border-t border-gray-100">
            <a href="{{ route('clinic.patients.show', $patient) }}" class="px-4 py-2 text-sm text-gray-600">إلغاء</a>
            <button type="submit" class="rounded-lg bg-teal-600 px-6 py-2.5 text-sm font-semibold text-white hover:bg-teal-700">حفظ التعديلات</button>
        </div>
    </form>
</div>
@endsection
