@extends('layouts.clinic')

@section('title', 'روشتة جديدة — '.config('app.name'))

@section('content')
<div dir="rtl" x-data="prescriptionPad({{ (int) ($preselectedPatientId ?? 0) }})">
    <div class="max-w-3xl mx-auto">
        <div class="flex flex-wrap items-center justify-between gap-3 mb-6">
            <h1 class="text-2xl font-bold text-teal-950"><x-info field="clinic.prescription_pad" /> الروشتة الذكية</h1>
            @if(($returnTo ?? null) === 'appointments')
                <a href="{{ route('clinic.appointments.index') }}" class="text-sm text-teal-700 hover:underline">← العودة للحجوزات</a>
            @endif
        </div>

        @if($linkedAppointment ?? null)
            <div class="mb-4 rounded-xl border border-teal-200 bg-teal-50 px-4 py-3 text-sm text-teal-900">
                <x-info field="clinic.prescription_linked_appointment" />
                مرتبطة بالحجز
                <strong class="font-mono">{{ $linkedAppointment->appointment_number }}</strong>
                — {{ $linkedAppointment->patient?->name }}
                · {{ $linkedAppointment->appointment_date?->format('Y-m-d') }}
                {{ substr((string) $linkedAppointment->start_time, 0, 5) }}
            </div>
        @endif

        <form method="POST" action="{{ route('clinic.prescriptions.store') }}"
              class="rounded-2xl border-2 border-teal-200 bg-white shadow-lg overflow-hidden">
            @csrf
            @if(! empty($preselectedAppointmentId))
                <input type="hidden" name="clinic_appointment_id" value="{{ $preselectedAppointmentId }}">
            @endif
            @if(($returnTo ?? null) === 'appointments')
                <input type="hidden" name="return_to" value="appointments">
            @endif

            <div class="bg-gradient-to-l from-teal-600 to-teal-500 px-6 py-4 text-white">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-teal-100 text-xs uppercase tracking-wide">Prescription</p>
                        <p class="text-xl font-bold">℞</p>
                    </div>
                    <p class="text-sm text-teal-100">{{ now()->translatedFormat('d M Y') }}</p>
                </div>
            </div>

            <div class="p-6 space-y-5">
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1"><x-info field="clinic.patient_name" /> المريض</label>
                        <x-searchable-select
                            name="patient_id"
                            :options="collect($patients)->map(fn($p) => ['value' => (string)$p->id, 'label' => $p->name])->all()"
                            :value="old('patient_id', $preselectedPatientId ?? '')"
                            :required="true"
                            :searchable="true"
                        />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1"><x-info field="clinic.doctor" /> الطبيب</label>
                        <x-searchable-select
                            name="doctor_employee_id"
                            :options="$doctors"
                            :value="old('doctor_employee_id', $preselectedDoctorId ?? '')"
                            :searchable="true"
                            empty-label="— اختياري —"
                        />
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1"><x-info field="clinic.diagnosis" /> التشخيص</label>
                    <textarea name="diagnosis" rows="2" class="w-full rounded-lg border-gray-300 focus:border-teal-500 focus:ring-teal-500" placeholder="التشخيص السريري...">{{ old('diagnosis') }}</textarea>
                </div>

                <div>
                    <div class="flex items-center justify-between mb-3">
                        <h2 class="font-semibold text-gray-900"><x-info field="clinic.medications" /> الأدوية</h2>
                        <button type="button" @click="addMed()" class="text-sm text-teal-700 font-medium hover:underline">+ إضافة دواء</button>
                    </div>
                    @error('medications')
                        <div class="mb-3 rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-800">{{ $message }}</div>
                    @enderror
                    @if(session('allergy_alerts'))
                        <ul class="mb-3 space-y-1 rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-xs text-red-800">
                            @foreach(session('allergy_alerts') as $alert)
                                <li>{{ $alert['message'] ?? '' }}</li>
                            @endforeach
                        </ul>
                    @endif
                    <template x-for="(med, idx) in meds" :key="idx">
                        <div class="mb-3 rounded-xl border border-teal-100 bg-teal-50/40 p-4 space-y-2">
                            <div class="flex justify-between items-center">
                                <span class="text-xs font-semibold text-teal-800" x-text="'دواء ' + (idx + 1)"></span>
                                <button type="button" @click="removeMed(idx)" x-show="meds.length > 1" class="text-xs text-red-600">حذف</button>
                            </div>
                            <input type="text" :name="'medications['+idx+'][name]'" x-model="med.name" required placeholder="اسم الدواء"
                                   @input.debounce.400ms="checkAllergy(idx)"
                                   class="w-full rounded-lg border-gray-300 text-sm"
                                   :class="med.alert ? 'border-red-500 ring-1 ring-red-300' : ''">
                            <template x-if="med.alert">
                                <p class="text-xs font-semibold text-red-700 bg-red-50 border border-red-200 rounded-lg px-2 py-1" x-text="med.alert"></p>
                            </template>
                            <div class="grid grid-cols-3 gap-2">
                                <input type="text" :name="'medications['+idx+'][dosage]'" x-model="med.dosage" placeholder="الجرعة" class="rounded-lg border-gray-300 text-sm">
                                <input type="text" :name="'medications['+idx+'][frequency]'" x-model="med.frequency" placeholder="التكرار" class="rounded-lg border-gray-300 text-sm">
                                <input type="text" :name="'medications['+idx+'][duration]'" x-model="med.duration" placeholder="المدة" class="rounded-lg border-gray-300 text-sm">
                            </div>
                        </div>
                    </template>
                </div>

                <div class="flex flex-col items-end gap-3 pt-2 border-t border-gray-100">
                    <label x-show="hasAllergyAlerts" x-cloak class="flex items-start gap-2 text-sm text-red-800 max-w-md">
                        <input type="checkbox" name="acknowledge_allergy_risk" value="1" class="mt-1 rounded border-red-300 text-red-600 focus:ring-red-500"
                               @if(old('acknowledge_allergy_risk')) checked @endif>
                        <span>أُقرّ بوجود تحذير حساسية وأتحمل مسؤولية متابعة الوصفة رغم التعارض المحتمل.</span>
                    </label>
                    <div class="flex justify-end gap-2 w-full">
                    @if(($returnTo ?? null) === 'appointments')
                        <a href="{{ route('clinic.appointments.index') }}" class="px-4 py-2 text-sm text-gray-600">إلغاء</a>
                    @else
                        <a href="{{ route('clinic.prescriptions.index') }}" class="px-4 py-2 text-sm text-gray-600">إلغاء</a>
                    @endif
                    <button type="submit" class="rounded-lg bg-teal-600 px-6 py-2.5 text-sm font-semibold text-white hover:bg-teal-700">حفظ الروشتة</button>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
function prescriptionPad(patientId) {
    return {
        patientId: patientId,
        meds: [{ name: '', dosage: '', frequency: '', duration: '', alert: '' }],
        get hasAllergyAlerts() {
            return this.meds.some(m => m.alert);
        },
        addMed() { this.meds.push({ name: '', dosage: '', frequency: '', duration: '', alert: '' }); },
        removeMed(i) { this.meds.splice(i, 1); },
        async checkAllergy(idx) {
            const name = this.meds[idx]?.name?.trim();
            this.meds[idx].alert = '';
            if (!name || !this.patientId) return;
            try {
                const res = await fetch(@js(route('clinic.api.check-allergy')), {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({ patient_id: this.patientId, medication: name }),
                });
                const data = await res.json();
                if (data.alerts?.length) {
                    this.meds[idx].alert = data.alerts[0].message;
                }
            } catch (e) { /* ignore */ }
        },
    };
}
</script>
@endpush
