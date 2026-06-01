@extends('layouts.clinic')

@section('title', niche_label('entities.customer', 'المرضى').' — '.config('app.name'))

@section('content')
<div class="space-y-6" dir="rtl" x-data="{ addOpen: false }">
    @if(session('success'))
        <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-900">{{ session('success') }}</div>
    @endif

    <div class="flex flex-wrap items-center justify-between gap-4">
        <h1 class="text-2xl font-bold text-teal-950"><x-info field="clinic.patients_list" /> {{ niche_label('entities.customer', 'المرضى') }}</h1>
        <button type="button" @click="addOpen = true" class="rounded-lg bg-teal-600 px-4 py-2 text-sm font-medium text-white hover:bg-teal-700">+ مريض جديد</button>
    </div>

    <form method="GET" class="flex gap-2 max-w-md">
        <input type="search" name="q" value="{{ request('q') }}" placeholder="بحث بالاسم أو الهاتف..."
               class="flex-1 rounded-lg border-gray-300 focus:border-teal-500 focus:ring-teal-500">
        <button type="submit" class="rounded-lg border border-gray-200 bg-white px-4 py-2 text-sm">بحث</button>
    </form>

    <div class="rounded-xl border border-teal-100 bg-white shadow-sm overflow-hidden">
        <table class="min-w-full text-sm">
            <thead class="bg-teal-50">
                <tr>
                    <th class="px-4 py-3 text-right font-semibold"><x-info field="clinic.patient_code" /> الكود</th>
                    <th class="px-4 py-3 text-right font-semibold"><x-info field="clinic.patient_name" /> الاسم</th>
                    <th class="px-4 py-3 text-right font-semibold"><x-info field="clinic.patient_phone" /> الهاتف</th>
                    <th class="px-4 py-3 text-right font-semibold"><x-info field="clinic.blood_type" /> فصيلة الدم</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                @forelse($patients as $patient)
                    <tr class="hover:bg-teal-50/30">
                        <td class="px-4 py-3 font-mono text-xs">{{ $patient->code }}</td>
                        <td class="px-4 py-3 font-medium">{{ $patient->name }}</td>
                        <td class="px-4 py-3" dir="ltr">{{ $patient->phone ?? '—' }}</td>
                        <td class="px-4 py-3">{{ $patient->blood_type ?? '—' }}</td>
                        <td class="px-4 py-3 text-left space-x-reverse space-x-2">
                            <a href="{{ route('clinic.patients.show', $patient) }}" class="text-teal-700 text-xs font-medium hover:underline">الملف</a>
                            <a href="{{ route('clinic.patients.edit', $patient) }}" class="text-gray-600 text-xs font-medium hover:underline">تعديل</a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="px-4 py-10 text-center text-gray-500">لا يوجد مرضى بعد.</td></tr>
                @endforelse
            </tbody>
        </table>
        @if($patients->hasPages())
            <div class="border-t px-4 py-3">{{ $patients->links() }}</div>
        @endif
    </div>

    <div x-show="addOpen" x-cloak class="fixed inset-0 z-[1060] flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-gray-900/40" @click="addOpen = false"></div>
        <div class="relative w-full max-w-md rounded-2xl bg-white p-6 shadow-xl border border-teal-100">
            <h2 class="text-lg font-bold text-teal-950 mb-4">إضافة مريض</h2>
            <form method="POST" action="{{ route('clinic.patients.store') }}" class="space-y-3">
                @csrf
                <div>
                    <label class="block text-sm font-medium mb-1"><x-info field="clinic.patient_name" /> الاسم</label>
                    <input type="text" name="name" required class="w-full rounded-lg border-gray-300">
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1"><x-info field="clinic.patient_phone" /> الهاتف</label>
                    <input type="text" name="phone" dir="ltr" class="w-full rounded-lg border-gray-300">
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1"><x-info field="clinic.national_id" /> الرقم القومي</label>
                    <input type="text" name="national_id" dir="ltr" class="w-full rounded-lg border-gray-300">
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1"><x-info field="clinic.blood_type" /> فصيلة الدم</label>
                    <x-searchable-select name="blood_type" :options="$bloodTypeOptions" :searchable="false" :in-modal="true" empty-label="—" />
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1"><x-info field="clinic.medical_history" /> التاريخ الطبي</label>
                    <textarea name="medical_history_summary" rows="3" class="w-full rounded-lg border-gray-300"></textarea>
                </div>
                <div class="flex justify-end gap-2 pt-2">
                    <button type="button" @click="addOpen = false" class="px-4 py-2 text-sm text-gray-600">إلغاء</button>
                    <button type="submit" class="rounded-lg bg-teal-600 px-5 py-2 text-sm text-white">حفظ</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
