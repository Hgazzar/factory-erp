@extends('layouts.clinic')

@section('title', 'روشتة — '.config('app.name'))

@section('content')
<div class="max-w-2xl mx-auto" dir="rtl">
    @if(session('success'))
        <div class="mb-4 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-900">{{ session('success') }}</div>
    @endif

    <div class="rounded-2xl border-2 border-teal-200 bg-white shadow-lg overflow-hidden print:shadow-none">
        <div class="bg-gradient-to-l from-teal-600 to-teal-500 px-6 py-4 text-white flex justify-between items-center">
            <div>
                <p class="text-teal-100 text-xs">℞ Prescription</p>
                <p class="font-bold text-lg">{{ $prescription->patient?->name }}</p>
            </div>
            <p class="text-sm">{{ $prescription->prescribed_at?->translatedFormat('d M Y') }}</p>
        </div>
        <div class="p-6 space-y-4">
            @if($prescription->doctor)
                <p class="text-sm text-gray-600"><x-info field="clinic.doctor" /> {{ $prescription->doctor->name }}</p>
            @endif
            @if($prescription->diagnosis)
                <div class="rounded-lg bg-teal-50 p-4">
                    <p class="text-xs text-teal-800 font-semibold mb-1"><x-info field="clinic.diagnosis" /> التشخيص</p>
                    <p class="text-sm text-gray-800 whitespace-pre-line">{{ $prescription->diagnosis }}</p>
                </div>
            @endif
            <div>
                <p class="text-sm font-semibold text-gray-900 mb-2"><x-info field="clinic.medications" /> الأدوية</p>
                <ul class="space-y-3">
                    @foreach($prescription->medications ?? [] as $i => $med)
                        <li class="rounded-xl border border-teal-100 p-4">
                            <p class="font-semibold text-teal-900">{{ $i + 1 }}. {{ $med['name'] ?? '' }}</p>
                            <p class="text-sm text-gray-600 mt-1">
                                {{ $med['dosage'] ?? '' }}
                                @if(!empty($med['frequency'])) · {{ $med['frequency'] }} @endif
                                @if(!empty($med['duration'])) · {{ $med['duration'] }} @endif
                            </p>
                        </li>
                    @endforeach
                </ul>
            </div>
            <div class="flex gap-2 pt-4 no-print">
                <a href="{{ route('clinic.prescriptions.pdf', $prescription) }}" target="_blank"
                   class="rounded-lg bg-teal-600 px-4 py-2 text-sm font-semibold text-white hover:bg-teal-700">
                    <x-info field="clinic.print_prescription" /> PDF
                </a>
                <button type="button" onclick="window.print()" class="rounded-lg border border-gray-200 px-4 py-2 text-sm">طباعة</button>
                <a href="{{ route('clinic.prescriptions.index') }}" class="rounded-lg border border-gray-200 px-4 py-2 text-sm text-gray-700">رجوع</a>
            </div>
        </div>
    </div>
</div>
@endsection
