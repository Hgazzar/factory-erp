@extends('layouts.clinic-portal')

@section('title', 'حجز موعد — '.$clinicName)

@section('content')
<div class="portal-wrap" dir="rtl" x-data="clinicPortalBook()" x-init="init()">
    <div class="text-center mb-4">
        <h1 class="h3 fw-bold text-teal-900 mb-1">{{ $clinicName }}</h1>
        <p class="text-muted mb-0"><x-info field="clinic.portal_intro" /> احجز موعدك أونلاين بخطوات بسيطة</p>
    </div>

    <div class="portal-progress">
        <span :class="step >= 1 ? 'is-done' : ''"></span>
        <span :class="step >= 2 ? 'is-done' : ''"></span>
        <span :class="step >= 3 ? 'is-done' : ''"></span>
        <span :class="step >= 4 ? 'is-done' : ''"></span>
    </div>

    <div class="portal-card mb-3" x-show="manageToken">
        <h2 class="h5 fw-bold mb-3">إدارة موعدك</h2>
        <p class="text-muted small mb-3">يمكنك تعديل أو إلغاء الموعد من نفس الرابط (حسب سياسة العيادة).</p>
        <div class="row g-2">
            <div class="col-12 col-md-5">
                <label class="form-label">تاريخ جديد</label>
                <input type="date" class="form-control" x-model="manageDate">
            </div>
            <div class="col-12 col-md-3">
                <label class="form-label">وقت جديد</label>
                <input type="time" class="form-control" x-model="manageTime">
            </div>
            <div class="col-12 col-md-4 d-flex gap-2 align-items-end">
                <button type="button" class="btn text-white flex-fill" style="background:#0d9488;" @click="rescheduleBooking()">تعديل</button>
                <button type="button" class="btn btn-outline-danger flex-fill" @click="cancelBooking()">إلغاء</button>
            </div>
        </div>
        <p x-show="manageMessage" class="small mt-2 mb-0 text-success" x-text="manageMessage"></p>
        <p x-show="manageError" class="small mt-2 mb-0 text-danger" x-text="manageError"></p>
    </div>

    <div class="portal-card" x-show="!confirmed">
        {{-- Step 1: Specialty --}}
        <div class="portal-step" :class="step === 1 ? 'is-active' : ''">
            <h2 class="h5 fw-bold mb-3"><x-info field="clinic.portal_specialty" /> اختر التخصص</h2>
            <div class="d-grid gap-2">
                <template x-for="s in specialties" :key="s.id">
                    <button type="button" class="btn btn-outline-teal text-start border-teal-subtle"
                            :class="selectedSpecialtyId === s.id ? 'btn-teal text-white' : ''"
                            style="--bs-btn-color:#0d9488; border-color:#99f6e4;"
                            @click="selectSpecialty(s.id)">
                        <span x-text="s.name"></span>
                    </button>
                </template>
            </div>
            <p x-show="specialties.length === 0 && !loading" class="text-muted small mt-3">جاري تحميل التخصصات…</p>
        </div>

        {{-- Step 2: Doctor --}}
        <div class="portal-step" :class="step === 2 ? 'is-active' : ''">
            <h2 class="h5 fw-bold mb-3"><x-info field="clinic.doctor" /> اختر الطبيب</h2>
            <div class="d-grid gap-2">
                <template x-for="d in doctors" :key="d.id">
                    <button type="button" class="btn btn-outline-secondary text-start"
                            :class="selectedDoctorId === d.id ? 'btn-teal text-white' : ''"
                            @click="selectDoctor(d.id)">
                        <span x-text="d.name"></span>
                        <small class="d-block opacity-75" x-text="d.job_title || ''"></small>
                    </button>
                </template>
            </div>
            <p x-show="doctors.length === 0 && !loading" class="text-warning small mt-3">لا يوجد أطباء متاحون لهذا التخصص.</p>
            <button type="button" class="btn btn-link text-muted mt-2 p-0" @click="step = 1">← رجوع</button>
        </div>

        {{-- Step 3: Date & Slot --}}
        <div class="portal-step" :class="step === 3 ? 'is-active' : ''">
            <h2 class="h5 fw-bold mb-3"><x-info field="clinic.portal_slot" /> اختر اليوم والوقت</h2>
            <div class="row g-2 mb-3" x-show="dates.length">
                <template x-for="dt in dates" :key="dt.date">
                    <div class="col-6 col-md-4">
                        <div class="date-btn" :class="selectedDate === dt.date ? 'is-selected' : ''"
                             @click="selectDate(dt.date)">
                            <div class="fw-semibold small" x-text="dt.label"></div>
                            <div class="text-muted" style="font-size:.75rem" x-text="dt.slots_count + ' موعد'"></div>
                        </div>
                    </div>
                </template>
            </div>
            <p x-show="dates.length === 0 && !loading" class="text-muted small">لا توجد أيام متاحة حالياً لهذا الطبيب.</p>

            <div class="d-flex flex-wrap gap-2" x-show="selectedDate">
                <template x-for="sl in slots" :key="sl.start">
                    <button type="button" class="slot-btn"
                            :class="selectedSlot === sl.start ? 'is-selected' : ''"
                            @click="selectedSlot = sl.start"
                            x-text="sl.label"></button>
                </template>
            </div>

            <div class="d-flex justify-content-between mt-3">
                <button type="button" class="btn btn-link text-muted p-0" @click="step = 2">← رجوع</button>
                <button type="button" class="btn btn-teal text-white px-4"
                        style="background:#0d9488;border:none;"
                        :disabled="!selectedSlot"
                        @click="step = 4">التالي</button>
            </div>
        </div>

        {{-- Step 4: Patient info --}}
        <div class="portal-step" :class="step === 4 ? 'is-active' : ''">
            <h2 class="h5 fw-bold mb-3"><x-info field="clinic.portal_confirm" /> بياناتك للتأكيد</h2>
            <div class="mb-3">
                <label class="form-label"><x-info field="clinic.patient_name" /> الاسم</label>
                <input type="text" class="form-control" x-model="patientName" required>
            </div>
            <div class="mb-3">
                <label class="form-label"><x-info field="clinic.patient_phone" /> رقم الهاتف (واتساب)</label>
                <input type="tel" class="form-control" x-model="patientPhone" dir="ltr" required>
            </div>
            <div class="alert alert-light border small mb-3" x-show="selectedDate && selectedSlot">
                الموعد: <strong x-text="selectedDate"></strong> — <strong x-text="selectedSlotLabel"></strong>
            </div>
            <p x-show="error" class="text-danger small" x-text="error"></p>
            <div class="d-flex justify-content-between mt-3">
                <button type="button" class="btn btn-link text-muted p-0" @click="step = 3">← رجوع</button>
                <button type="button" class="btn btn-teal text-white px-4"
                        style="background:#0d9488;border:none;"
                        :disabled="submitting || !patientName || !patientPhone"
                        @click="submitBooking()">
                    <span x-show="!submitting">تأكيد الحجز</span>
                    <span x-show="submitting">جاري الحجز…</span>
                </button>
            </div>
        </div>
    </div>

    {{-- Success --}}
    <div class="portal-card text-center" x-show="confirmed" x-cloak>
        <div class="display-4 text-teal-600 mb-2">✓</div>
        <h2 class="h5 fw-bold text-teal-900">تم تأكيد حجزك!</h2>
        <p class="text-muted mb-1">رقم الحجز: <strong x-text="confirmedNumber"></strong></p>
        <p class="small text-muted"><x-info field="clinic.portal_whatsapp_hint" /> ستصلك رسالة واتساب بتفاصيل الموعد.</p>
    </div>
</div>
@endsection

@push('scripts')
<script>
function clinicPortalBook() {
    return {
        apiBase: @json($apiBase),
        manageToken: @json($manageToken ?? ''),
        manageAppointmentId: @json($manageAppointmentId ?? 0),
        csrf: document.querySelector('meta[name="csrf-token"]')?.content || '',
        step: 1,
        loading: false,
        submitting: false,
        error: '',
        confirmed: false,
        confirmedNumber: '',
        specialties: [],
        doctors: [],
        dates: [],
        slots: [],
        selectedSpecialtyId: null,
        selectedDoctorId: null,
        selectedDate: '',
        selectedSlot: '',
        patientName: '',
        patientPhone: '',
        manageDate: '',
        manageTime: '',
        manageMessage: '',
        manageError: '',

        get selectedSlotLabel() {
            const sl = this.slots.find(s => s.start === this.selectedSlot);
            return sl ? sl.label : this.selectedSlot?.substring(0, 5) || '';
        },

        async init() {
            this.loading = true;
            try {
                const res = await fetch(this.apiBase + '/specialties');
                const data = await res.json();
                this.specialties = data.specialties || [];
            } finally {
                this.loading = false;
            }
        },

        async selectSpecialty(id) {
            this.selectedSpecialtyId = id;
            this.loading = true;
            this.doctors = [];
            try {
                const res = await fetch(this.apiBase + '/doctors?specialty_id=' + id);
                const data = await res.json();
                this.doctors = data.doctors || [];
                this.step = 2;
            } finally {
                this.loading = false;
            }
        },

        async selectDoctor(id) {
            this.selectedDoctorId = id;
            this.selectedDate = '';
            this.selectedSlot = '';
            this.dates = [];
            this.slots = [];
            this.loading = true;
            try {
                const res = await fetch(this.apiBase + '/dates?doctor_id=' + id);
                const data = await res.json();
                this.dates = data.dates || [];
                this.step = 3;
            } finally {
                this.loading = false;
            }
        },

        async selectDate(date) {
            this.selectedDate = date;
            this.selectedSlot = '';
            this.loading = true;
            try {
                const res = await fetch(this.apiBase + '/slots?doctor_id=' + this.selectedDoctorId + '&date=' + date);
                const data = await res.json();
                this.slots = data.slots || [];
            } finally {
                this.loading = false;
            }
        },

        async submitBooking() {
            this.error = '';
            this.submitting = true;
            try {
                const res = await fetch(this.apiBase + '/book', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': this.csrf,
                    },
                    body: JSON.stringify({
                        patient_name: this.patientName,
                        patient_phone: this.patientPhone,
                        doctor_employee_id: this.selectedDoctorId,
                        appointment_date: this.selectedDate,
                        start_time: this.selectedSlot,
                    }),
                });
                const data = await res.json();
                if (!res.ok) {
                    this.error = data.message || 'تعذّر إتمام الحجز.';
                    return;
                }
                this.confirmed = true;
                this.confirmedNumber = data.appointment?.number || '';
            } catch (e) {
                this.error = 'حدث خطأ في الاتصال. حاول مرة أخرى.';
            } finally {
                this.submitting = false;
            }
        },
        async cancelBooking() {
            this.manageError = '';
            this.manageMessage = '';
            if (!this.manageToken) return;
            const res = await fetch(this.apiBase + '/manage/' + this.manageToken + '/cancel', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': this.csrf },
                body: JSON.stringify({ appointment_id: this.manageAppointmentId }),
            });
            const data = await res.json();
            if (!res.ok) { this.manageError = data.message || 'تعذّر إلغاء الموعد'; return; }
            this.manageMessage = data.message || 'تم إلغاء الموعد';
        },
        async rescheduleBooking() {
            this.manageError = '';
            this.manageMessage = '';
            if (!this.manageToken || !this.manageDate || !this.manageTime) {
                this.manageError = 'اختر التاريخ والوقت أولاً.';
                return;
            }
            const res = await fetch(this.apiBase + '/manage/' + this.manageToken + '/reschedule', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': this.csrf },
                body: JSON.stringify({
                    appointment_id: this.manageAppointmentId,
                    appointment_date: this.manageDate,
                    start_time: this.manageTime,
                }),
            });
            const data = await res.json();
            if (!res.ok) { this.manageError = data.message || 'تعذّرت إعادة الجدولة'; return; }
            this.manageMessage = data.message || 'تم تعديل الموعد';
        },
    };
}
</script>
@endpush
