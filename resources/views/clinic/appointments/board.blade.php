@extends('layouts.clinic')

@section('title', 'لوحة الحجوزات — '.config('app.name'))

@section('content')
@php
    $appointmentsByDaySlot = $appointments->groupBy(function ($a) {
        $time = substr((string) $a->start_time, 0, 5);
        return $a->appointment_date->format('Y-m-d').'|'.$time;
    });
    $paymentMethodOptions = [
        ['value' => 'cash', 'label' => 'نقدي — الخزينة'],
        ['value' => 'bank', 'label' => 'بنك / شبكة'],
    ];
@endphp

<div class="space-y-5" dir="rtl" x-data="clinicBookingBoard()">
    @if(session('success'))
        <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-900">{{ session('success') }}</div>
    @endif
    @if(session('receipt_appointment_id'))
        <div class="rounded-lg border border-teal-200 bg-teal-50 px-4 py-3 text-sm text-teal-900 flex flex-wrap items-center justify-between gap-2">
            <span>تم التحصيل بنجاح.</span>
            <a href="{{ route('clinic.appointments.receipt.pdf', session('receipt_appointment_id')) }}" target="_blank"
               class="inline-flex items-center gap-1 rounded-lg bg-teal-600 px-4 py-2 text-sm font-semibold text-white hover:bg-teal-700">
                <x-info field="clinic.print_receipt" /> طباعة الإيصال PDF
            </a>
        </div>
    @endif
    @if($errors->any())
        <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
            <ul class="list-disc pr-4">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
        </div>
    @endif

    <div class="clinic-page-header">
        <div>
            <h1 class="clinic-page-title text-2xl font-bold text-teal-950"><x-info field="clinic.appointments_board" /> لوحة الحجوزات</h1>
            <p class="clinic-page-subtitle text-sm text-gray-500 mt-1">{{ $viewMode === 'day' ? 'عرض يومي' : 'عرض أسبوعي' }} — {{ $anchor->translatedFormat('F Y') }}</p>
        </div>
        <div class="clinic-toolbar">
            <a href="{{ route('clinic.appointments.index', ['view' => 'day', 'date' => $anchor->toDateString()]) }}"
               class="clinic-btn clinic-btn-outline {{ $viewMode === 'day' ? 'is-active' : '' }} rounded-lg px-3 py-2 text-sm border {{ $viewMode === 'day' ? 'bg-teal-600 text-white border-teal-600' : 'bg-white text-gray-700 border-gray-200' }}">يوم</a>
            <a href="{{ route('clinic.appointments.index', ['view' => 'week', 'date' => $anchor->toDateString()]) }}"
               class="clinic-btn clinic-btn-outline {{ $viewMode === 'week' ? 'is-active' : '' }} rounded-lg px-3 py-2 text-sm border {{ $viewMode === 'week' ? 'bg-teal-600 text-white border-teal-600' : 'bg-white text-gray-700 border-gray-200' }}">أسبوع</a>
            <button type="button" @click="quickOpen = true"
                    class="clinic-btn clinic-btn-primary inline-flex items-center gap-2 rounded-lg bg-teal-600 px-4 py-2 text-sm font-semibold text-white hover:bg-teal-700 shadow-sm">
                + حجز سريع
            </button>
        </div>
    </div>

    <div class="clinic-card rounded-xl border border-teal-100 bg-white shadow-sm clinic-board-wrap overflow-x-auto">
        <table class="clinic-board-table min-w-full text-sm border-collapse">
            <thead>
                <tr class="bg-teal-50/80">
                    <th class="time-col sticky right-0 z-10 bg-teal-50 px-3 py-2 text-right font-semibold text-teal-900 border-b border-teal-100 w-20">الوقت</th>
                    @foreach($days as $day)
                        <th class="px-2 py-2 text-center font-semibold text-teal-900 border-b border-teal-100 min-w-[10rem]">
                            <div>{{ $day->translatedFormat('D') }}</div>
                            <div class="text-xs font-normal text-gray-500">{{ $day->format('d/m') }}</div>
                        </th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @foreach($timeSlots as $slot)
                    <tr class="hover:bg-gray-50/50">
                        <td class="time-col sticky right-0 z-10 bg-white px-3 py-2 text-gray-600 font-mono text-xs border-b border-gray-100">{{ $slot }}</td>
                        @foreach($days as $day)
                            @php
                                $key = $day->format('Y-m-d').'|'.$slot;
                                $cellAppts = $appointmentsByDaySlot->get($key, collect());
                            @endphp
                            <td class="align-top px-1 py-1 border-b border-gray-50 min-h-[3rem]">
                                @foreach($cellAppts as $appt)
                                    @php
                                        $rxUrl = route('clinic.prescriptions.create', [
                                            'patient_id' => $appt->patient_id,
                                            'appointment_id' => $appt->id,
                                            'doctor_employee_id' => $appt->doctor_employee_id,
                                            'return_to' => 'appointments',
                                        ]);
                                        $collectUrl = route('clinic.appointments.status', $appt);
                                        $rescheduleUrl = route('clinic.appointments.status', $appt);
                                        $cancelUrl = route('clinic.appointments.status', $appt);
                                        $receiptUrl = $appt->isPaid() ? route('clinic.appointments.receipt.pdf', $appt) : null;
                                        $apptDate = $appt->appointment_date?->format('Y-m-d');
                                        $apptTime = substr((string) $appt->start_time, 0, 5);
                                    @endphp
                                    <div class="mb-1 rounded-lg border p-2 text-xs shadow-sm
                                        @if($appt->status === 'completed') border-emerald-200 bg-emerald-50
                                        @elseif($appt->status === 'cancelled') border-gray-200 bg-gray-50 opacity-70
                                        @else border-amber-200 bg-amber-50 @endif">
                                        <div class="font-semibold text-gray-900 truncate">{{ $appt->patient?->name }}</div>
                                        <div class="text-gray-500 truncate">{{ $appt->doctor?->name ?? '—' }}</div>
                                        <div class="text-[10px] text-gray-400 font-mono mt-0.5">{{ $appt->appointment_number }}</div>
                                        <div class="mt-1.5 flex flex-wrap items-center justify-between gap-1">
                                            <x-clinic-status-badge :status="$appt->status" />
                                            @if(! $appt->isCancelled())
                                                <div class="flex items-center gap-1 shrink-0 flex-wrap justify-end">
                                                    @if($appt->isPending() && ($canCollect ?? true))
                                                        <button type="button"
                                                                @click="openCollect(@js($collectUrl), @js($appt->patient?->name ?? ''))"
                                                                class="inline-flex h-6 w-6 items-center justify-center rounded-md bg-teal-600 text-white hover:bg-teal-700 font-bold"
                                                                title="اكتمال + تحصيل">
                                                            ✓
                                                        </button>
                                                    @elseif($appt->isPaid())
                                                        <span class="text-[10px] text-emerald-700 tabular-nums">{{ erp_money($appt->fee_amount) }}</span>
                                                        @if($receiptUrl)
                                                            <a href="{{ $receiptUrl }}" target="_blank"
                                                               class="inline-flex h-6 w-6 items-center justify-center rounded-md border border-emerald-300 bg-white text-emerald-800 hover:bg-emerald-50 text-[10px]"
                                                               title="طباعة إيصال">
                                                                🧾
                                                            </a>
                                                        @endif
                                                    @endif
                                                    @if($appt->isPending())
                                                        <button type="button"
                                                                @click="openReschedule(@js($rescheduleUrl), @js($appt->patient?->name ?? ''), @js($apptDate), @js($apptTime))"
                                                                class="inline-flex h-6 px-1.5 items-center justify-center rounded-md border border-amber-300 bg-white text-amber-800 hover:bg-amber-50 text-[10px]"
                                                                title="إعادة جدولة">
                                                            ↻
                                                        </button>
                                                        <button type="button"
                                                                @click="openCancel(@js($cancelUrl), @js($appt->patient?->name ?? ''))"
                                                                class="inline-flex h-6 w-6 items-center justify-center rounded-md border border-red-200 bg-white text-red-700 hover:bg-red-50 text-[10px]"
                                                                title="إلغاء الموعد">
                                                            ✕
                                                        </button>
                                                    @endif
                                                    @if(! $appt->isCancelled() && ($canClinical ?? true))
                                                        <a href="{{ $rxUrl }}"
                                                           class="inline-flex items-center gap-0.5 rounded-md border border-teal-300 bg-white px-1.5 py-0.5 text-[10px] font-semibold text-teal-800 hover:bg-teal-50"
                                                           title="اكتب روشتة">
                                                            ℞
                                                        </a>
                                                    @endif
                                                    @if(! $appt->isCancelled())
                                                        <x-clinic-manual-prescription-capture
                                                            :patient-id="$appt->patient_id"
                                                            :appointment-id="$appt->id"
                                                            size="sm" />
                                                    @endif
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                @endforeach
                            </td>
                        @endforeach
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    {{-- Quick Add Modal --}}
    <div x-show="quickOpen" x-cloak class="fixed inset-0 z-[1060] flex items-center justify-center p-4" role="dialog" aria-modal="true">
        <div class="absolute inset-0 bg-gray-900/40" @click="quickOpen = false"></div>
        <div class="relative w-full max-w-lg rounded-2xl bg-white shadow-xl border border-teal-100 p-6" @click.stop>
            <h2 class="text-lg font-bold text-teal-950 mb-1"><x-info field="clinic.quick_add" /> حجز سريع</h2>
            <p class="text-sm text-gray-500 mb-4">مريض جديد + موعد في خطوة واحدة</p>
            <form method="POST" action="{{ route('clinic.appointments.quick-store') }}" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1"><x-info field="clinic.patient_name" /> اسم المريض</label>
                    <input type="text" name="patient_name" required class="w-full rounded-lg border-gray-300 focus:border-teal-500 focus:ring-teal-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1"><x-info field="clinic.patient_phone" /> الهاتف</label>
                    <input type="text" name="patient_phone" dir="ltr" class="w-full rounded-lg border-gray-300 focus:border-teal-500 focus:ring-teal-500">
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1"><x-info field="clinic.appointment_date" /> التاريخ</label>
                        <input type="date" name="appointment_date" value="{{ now()->toDateString() }}" required class="w-full rounded-lg border-gray-300 focus:border-teal-500 focus:ring-teal-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1"><x-info field="clinic.start_time" /> الوقت</label>
                        <input type="time" name="start_time" required class="w-full rounded-lg border-gray-300 focus:border-teal-500 focus:ring-teal-500">
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1"><x-info field="clinic.doctor" /> الطبيب</label>
                    <x-searchable-select name="doctor_employee_id" :options="$doctors" :searchable="true" :in-modal="true" empty-label="— اختياري —" />
                </div>
                <div class="flex justify-end gap-2 pt-2">
                    <button type="button" @click="quickOpen = false" class="rounded-lg px-4 py-2 text-sm text-gray-600 hover:bg-gray-100">إلغاء</button>
                    <button type="submit" class="rounded-lg bg-teal-600 px-5 py-2 text-sm font-medium text-white hover:bg-teal-700">حفظ الحجز</button>
                </div>
            </form>
        </div>
    </div>

    {{-- Collection Modal --}}
    <div x-show="collectOpen" x-cloak class="fixed inset-0 z-[1060] flex items-center justify-center p-4" role="dialog" aria-modal="true">
        <div class="absolute inset-0 bg-gray-900/40" @click="collectOpen = false"></div>
        <div class="relative w-full max-w-md rounded-2xl bg-white shadow-xl border border-emerald-100 p-6" @click.stop>
            <h2 class="text-lg font-bold text-teal-950 mb-1"><x-info field="clinic.collect_modal" /> تحصيل الكشف</h2>
            <p class="text-sm text-gray-500 mb-4">
                <span x-text="collectPatientName"></span>
                — <x-info field="clinic.collect_modal_hint" />
            </p>
            <form method="POST" :action="collectActionUrl" class="space-y-4" @submit="if(selectedServices.length) { $refs.manualFee.disabled = true; }">
                @csrf
                @method('PATCH')
                <input type="hidden" name="action" value="complete_paid">
                <template x-for="sid in selectedServices" :key="sid">
                    <input type="hidden" name="service_ids[]" :value="sid">
                </template>

                @if(($clinicServices ?? collect())->isNotEmpty())
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2"><x-info field="clinic.services_select" /> الخدمات</label>
                    <div class="max-h-40 overflow-y-auto space-y-2 rounded-lg border border-gray-200 p-2">
                        @foreach($clinicServices as $svc)
                            <label class="flex items-center gap-2 text-sm cursor-pointer">
                                <input type="checkbox" value="{{ $svc->id }}"
                                       @change="toggleService({{ $svc->id }}, $event.target.checked)"
                                       class="rounded border-gray-300 text-teal-600">
                                <span>{{ $svc->name }}</span>
                                <span class="mr-auto tabular-nums text-gray-500">{{ erp_money($svc->price) }}</span>
                            </label>
                        @endforeach
                    </div>
                    <div x-show="quoteLoading" class="text-xs text-gray-500 mt-1">جاري الحساب...</div>
                    <div x-show="quote" class="mt-2 rounded-lg bg-teal-50 p-3 text-sm space-y-1">
                        <div class="flex justify-between"><span>صافي</span><span x-text="quote?.subtotal?.toFixed(2)" class="tabular-nums"></span></div>
                        <div class="flex justify-between"><span>ض.ق.م</span><span x-text="quote?.vat_total?.toFixed(2)" class="tabular-nums"></span></div>
                        <div class="flex justify-between font-bold text-teal-900"><span>الإجمالي</span><span x-text="quote?.grand_total?.toFixed(2)" class="tabular-nums"></span></div>
                    </div>
                </div>
                @endif

                <div>
                    <label for="collect_fee_amount" class="block text-sm font-medium text-gray-700 mb-1">
                        <x-info field="clinic.fee_amount" /> المبلغ (يدوي إن لم تختر خدمات)
                    </label>
                    <input type="number" name="fee_amount" id="collect_fee_amount" x-model="collectFeeAmount" x-ref="manualFee"
                           step="0.01" min="0.01" :required="selectedServices.length === 0" dir="ltr"
                           class="w-full rounded-lg border-gray-300 focus:border-teal-500 focus:ring-teal-500 text-lg font-semibold tabular-nums">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        <x-info field="clinic.payment_method" /> طريقة الدفع
                    </label>
                    <x-searchable-select
                        name="payment_method"
                        id="collect_payment_method"
                        :options="$paymentMethodOptions"
                        value="cash"
                        :searchable="false"
                        :in-modal="true"
                    />
                </div>
                <div class="flex justify-end gap-2 pt-2">
                    <button type="button" @click="collectOpen = false" class="rounded-lg px-4 py-2 text-sm text-gray-600 hover:bg-gray-100">إلغاء</button>
                    <button type="submit" class="rounded-lg bg-emerald-600 px-5 py-2 text-sm font-semibold text-white hover:bg-emerald-700">
                        تأكيد التحصيل والاكتمال
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- Reschedule Modal --}}
    <div x-show="rescheduleOpen" x-cloak class="fixed inset-0 z-[1060] flex items-center justify-center p-4" role="dialog" aria-modal="true">
        <div class="absolute inset-0 bg-gray-900/40" @click="rescheduleOpen = false"></div>
        <div class="relative w-full max-w-md rounded-2xl bg-white shadow-xl border border-amber-100 p-6" @click.stop>
            <h2 class="text-lg font-bold text-teal-950 mb-1"><x-info field="clinic.reschedule" /> إعادة جدولة</h2>
            <p class="text-sm text-gray-500 mb-4"><span x-text="reschedulePatientName"></span></p>
            <form method="POST" :action="rescheduleActionUrl" class="space-y-4">
                @csrf
                @method('PATCH')
                <input type="hidden" name="action" value="reschedule">
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1"><x-info field="clinic.appointment_date" /> التاريخ</label>
                        <input type="date" name="appointment_date" x-model="rescheduleDate" required
                               class="w-full rounded-lg border-gray-300 focus:border-teal-500 focus:ring-teal-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1"><x-info field="clinic.start_time" /> الوقت</label>
                        <input type="time" name="start_time" x-model="rescheduleTime" required
                               class="w-full rounded-lg border-gray-300 focus:border-teal-500 focus:ring-teal-500">
                    </div>
                </div>
                <div class="flex justify-end gap-2 pt-2">
                    <button type="button" @click="rescheduleOpen = false" class="rounded-lg px-4 py-2 text-sm text-gray-600 hover:bg-gray-100">إلغاء</button>
                    <button type="submit" class="rounded-lg bg-amber-600 px-5 py-2 text-sm font-semibold text-white hover:bg-amber-700">حفظ الموعد الجديد</button>
                </div>
            </form>
        </div>
    </div>

    {{-- Cancel Modal --}}
    <div x-show="cancelOpen" x-cloak class="fixed inset-0 z-[1060] flex items-center justify-center p-4" role="dialog" aria-modal="true">
        <div class="absolute inset-0 bg-gray-900/40" @click="cancelOpen = false"></div>
        <div class="relative w-full max-w-sm rounded-2xl bg-white shadow-xl border border-red-100 p-6" @click.stop>
            <h2 class="text-lg font-bold text-red-900 mb-1"><x-info field="clinic.cancel_appointment" /> إلغاء الموعد</h2>
            <p class="text-sm text-gray-600 mb-4">هل تريد إلغاء موعد <strong x-text="cancelPatientName"></strong>؟</p>
            <form method="POST" :action="cancelActionUrl" class="flex justify-end gap-2">
                @csrf
                @method('PATCH')
                <input type="hidden" name="action" value="cancel">
                <button type="button" @click="cancelOpen = false" class="rounded-lg px-4 py-2 text-sm text-gray-600 hover:bg-gray-100">تراجع</button>
                <button type="submit" class="rounded-lg bg-red-600 px-5 py-2 text-sm font-semibold text-white hover:bg-red-700">تأكيد الإلغاء</button>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function clinicBookingBoard() {
    return {
        quickOpen: false,
        collectOpen: false,
        rescheduleOpen: false,
        cancelOpen: false,
        collectActionUrl: '',
        rescheduleActionUrl: '',
        cancelActionUrl: '',
        collectPatientName: '',
        reschedulePatientName: '',
        cancelPatientName: '',
        rescheduleDate: '',
        rescheduleTime: '',
        collectFeeAmount: '',
        selectedServices: [],
        quote: null,
        quoteLoading: false,
        openCollect(url, patientName) {
            this.collectActionUrl = url;
            this.collectPatientName = patientName || '';
            this.collectFeeAmount = '';
            this.selectedServices = [];
            this.quote = null;
            this.collectOpen = true;
        },
        openReschedule(url, patientName, date, time) {
            this.rescheduleActionUrl = url;
            this.reschedulePatientName = patientName || '';
            this.rescheduleDate = date || '';
            this.rescheduleTime = time || '';
            this.rescheduleOpen = true;
        },
        openCancel(url, patientName) {
            this.cancelActionUrl = url;
            this.cancelPatientName = patientName || '';
            this.cancelOpen = true;
        },
        toggleService(id, checked) {
            id = parseInt(id, 10);
            if (checked) {
                if (!this.selectedServices.includes(id)) this.selectedServices.push(id);
            } else {
                this.selectedServices = this.selectedServices.filter(x => x !== id);
            }
            this.refreshQuote();
        },
        async refreshQuote() {
            if (this.selectedServices.length === 0) {
                this.quote = null;
                return;
            }
            this.quoteLoading = true;
            try {
                const res = await fetch(@js(route('clinic.api.quote-services')), {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({ service_ids: this.selectedServices }),
                });
                const data = await res.json();
                if (res.ok) {
                    this.quote = data;
                    this.collectFeeAmount = String(data.grand_total);
                }
            } catch (e) { /* ignore */ }
            this.quoteLoading = false;
        },
    };
}
</script>
@endpush
