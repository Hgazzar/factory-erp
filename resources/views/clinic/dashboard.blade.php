@extends('layouts.clinic')

@section('title', niche_module_label('clinic').' — '.config('app.name'))

@section('content')
<div class="space-y-6" dir="rtl">
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-teal-950">لوحة {{ niche_module_label('clinic') }}</h1>
            <p class="mt-1 text-sm text-gray-600"><x-info field="clinic.dashboard_intro" /></p>
        </div>
        <a href="{{ route('clinic.appointments.index') }}"
           class="inline-flex items-center rounded-lg bg-teal-600 px-4 py-2 text-sm font-medium text-white hover:bg-teal-700">
            + حجز سريع
        </a>
    </div>

    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <div class="rounded-xl border border-teal-100 bg-white p-5 shadow-sm">
            <p class="text-sm text-gray-500"><x-info field="clinic.stat_patients" /> {{ niche_label('entities.customer', 'المرضى') }}</p>
            <p class="mt-2 text-3xl font-bold text-teal-900 tabular-nums">{{ $stats['patients_total'] }}</p>
        </div>
        <div class="rounded-xl border border-teal-100 bg-white p-5 shadow-sm">
            <p class="text-sm text-gray-500"><x-info field="clinic.stat_appointments_today" /> حجوزات اليوم</p>
            <p class="mt-2 text-3xl font-bold text-teal-900 tabular-nums">{{ $stats['appointments_today'] }}</p>
        </div>
        <div class="rounded-xl border border-amber-100 bg-white p-5 shadow-sm">
            <p class="text-sm text-gray-500"><x-info field="clinic.stat_pending_today" /> قيد الانتظار</p>
            <p class="mt-2 text-3xl font-bold text-amber-700 tabular-nums">{{ $stats['pending_today'] }}</p>
        </div>
        <div class="rounded-xl border border-teal-100 bg-white p-5 shadow-sm">
            <p class="text-sm text-gray-500"><x-info field="clinic.stat_prescriptions_week" /> روشتات الأسبوع</p>
            <p class="mt-2 text-3xl font-bold text-teal-900 tabular-nums">{{ $stats['prescriptions_week'] }}</p>
        </div>
    </div>

    @canFeature('clinic_patient_portal')
    <div class="rounded-xl border border-cyan-200 bg-gradient-to-r from-cyan-50 to-teal-50 p-5 shadow-sm" x-data="{ copied: false }">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div class="min-w-[260px] flex-1">
                <h2 class="text-lg font-bold text-teal-900">بوابة الحجز للمرضى + QR</h2>
                <p class="mt-1 text-sm text-gray-600">انسخ الرابط أو اطبع/حمّل الـ QR لتعليقه في الاستقبال.</p>
                @if($portalUrl)
                    <div class="mt-3 flex flex-wrap items-center gap-2 rounded-lg border border-teal-100 bg-white/80 px-3 py-2 text-sm text-teal-900 break-all">
                        <span class="flex-1 min-w-[220px]">{{ $portalUrl }}</span>
                        <button type="button"
                                class="relative inline-flex h-8 w-8 items-center justify-center rounded-md border border-teal-200 bg-white text-teal-700 hover:bg-teal-50"
                                title="نسخ رابط البوابة"
                                @click="navigator.clipboard.writeText(@js($portalUrl)); copied = true; setTimeout(() => copied = false, 1200)">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                                <path d="M6 2a2 2 0 0 0-2 2v9a2 2 0 0 0 2 2h2v-2H6V4h7v2h2V4a2 2 0 0 0-2-2H6Z" />
                                <path d="M10 8a2 2 0 0 0-2 2v6a2 2 0 0 0 2 2h6a2 2 0 0 0 2-2v-6a2 2 0 0 0-2-2h-6Z" />
                            </svg>
                            <span x-show="copied" x-cloak class="absolute -top-8 left-1/2 -translate-x-1/2 whitespace-nowrap rounded-md bg-teal-700 px-2 py-1 text-xs text-white">
                                ✓ تم النسخ
                            </span>
                        </button>
                    </div>
                    <div class="mt-3 flex flex-wrap gap-2">
                        <a href="{{ $portalUrl }}" target="_blank"
                           class="inline-flex items-center rounded-lg bg-teal-600 px-3 py-2 text-sm font-medium text-white hover:bg-teal-700">
                            فتح رابط البوابة
                        </a>
                        <a href="{{ route('clinic.portal.qr-download') }}"
                           class="inline-flex items-center rounded-lg border border-teal-200 bg-white px-3 py-2 text-sm font-medium text-teal-700 hover:bg-teal-50">
                            تحميل QR
                        </a>
                        <button type="button" onclick="window.print()"
                                class="inline-flex items-center rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">
                            طباعة
                        </button>
                    </div>
                @else
                    <div class="mt-3 rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-sm text-amber-900">
                        لا يوجد `tenant_slug` مفعل بعد لهذا المستأجر.
                    </div>
                @endif
            </div>
            @if($qrDataUri)
                <div class="rounded-xl border border-teal-100 bg-white p-3">
                    <img src="{{ $qrDataUri }}" alt="Clinic Portal QR" class="h-40 w-40" />
                </div>
            @endif
        </div>
    </div>
    @endcanFeature

    <div class="rounded-xl border border-teal-100 bg-white shadow-sm overflow-hidden">
        <div class="border-b border-teal-50 px-5 py-4">
            <h2 class="font-semibold text-gray-900">الحجوزات القادمة</h2>
        </div>
        <div class="divide-y divide-gray-50">
            @forelse($upcoming as $appt)
                <div class="flex flex-wrap items-center justify-between gap-3 px-5 py-3 hover:bg-teal-50/40">
                    <div>
                        <p class="font-medium text-gray-900">{{ $appt->patient?->name }}</p>
                        <p class="text-xs text-gray-500">{{ $appt->appointment_date?->translatedFormat('d M Y') }} — {{ substr((string) $appt->start_time, 0, 5) }}</p>
                    </div>
                    <x-clinic-status-badge :status="$appt->status" />
                </div>
            @empty
                <p class="px-5 py-8 text-center text-sm text-gray-500">لا توجد حجوزات قادمة.</p>
            @endforelse
        </div>
    </div>
</div>
@endsection
