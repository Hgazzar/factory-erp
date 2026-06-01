@extends('layouts.clinic')

@section('title', 'الروشتات — '.config('app.name'))

@section('content')
<div class="space-y-6" dir="rtl">
    <div class="flex flex-wrap items-center justify-between gap-4">
        <h1 class="text-2xl font-bold text-teal-950"><x-info field="clinic.prescriptions_list" /> الروشتات</h1>
        <a href="{{ route('clinic.prescriptions.create') }}" class="rounded-lg bg-teal-600 px-4 py-2 text-sm text-white hover:bg-teal-700">+ روشتة</a>
    </div>
    <div class="rounded-xl border border-teal-100 bg-white shadow-sm overflow-hidden">
        <table class="min-w-full text-sm">
            <thead class="bg-teal-50">
                <tr>
                    <th class="px-4 py-3 text-right font-semibold"><x-info field="clinic.patient_name" /> المريض</th>
                    <th class="px-4 py-3 text-right font-semibold"><x-info field="clinic.doctor" /> الطبيب</th>
                    <th class="px-4 py-3 text-right font-semibold">التاريخ</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                @forelse($prescriptions as $rx)
                    <tr class="hover:bg-teal-50/30">
                        <td class="px-4 py-3">{{ $rx->patient?->name }}</td>
                        <td class="px-4 py-3">{{ $rx->doctor?->name ?? '—' }}</td>
                        <td class="px-4 py-3">{{ $rx->prescribed_at?->translatedFormat('d M Y H:i') }}</td>
                        <td class="px-4 py-3 text-left">
                            <a href="{{ route('clinic.prescriptions.show', $rx) }}" class="text-teal-700 text-xs font-medium">عرض</a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="px-4 py-10 text-center text-gray-500">لا روشتات بعد.</td></tr>
                @endforelse
            </tbody>
        </table>
        @if($prescriptions->hasPages())<div class="border-t px-4 py-3">{{ $prescriptions->links() }}</div>@endif
    </div>
</div>
@endsection
