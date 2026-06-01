@props([
    'patientId',
    'appointmentId' => null,
    'size' => 'sm',
])

@php
    $btnClass = ($size ?? 'sm') === 'md'
        ? 'inline-flex items-center gap-2 rounded-xl border-2 border-violet-300 bg-violet-50 px-4 py-2.5 text-sm font-semibold text-violet-900 hover:bg-violet-100 shadow-sm transition'
        : 'inline-flex h-6 w-6 items-center justify-center rounded-md border-2 border-violet-300 bg-violet-50 text-violet-900 hover:bg-violet-100 shadow-sm transition';
    $inputId = 'manual-rx-'.($patientId).'-'.($appointmentId ?? 'p');
@endphp

<div
    x-data="manualRxCapture({{ (int) $patientId }}, {{ $appointmentId ? (int) $appointmentId : 'null' }})"
    class="inline-flex flex-col items-end gap-1"
    @manual-rx-uploaded.window="if ($event.detail?.patientId === patientId) { onExternalUpload($event.detail); }"
>
    <input
        type="file"
        id="{{ $inputId }}"
        accept="image/*"
        capture="environment"
        class="hidden"
        x-ref="fileInput"
        @change="onFileSelected($event)"
    >

    <button
        type="button"
        @click="$refs.fileInput.click()"
        :disabled="uploading"
        class="{{ $btnClass }}"
        :class="uploading ? 'opacity-70 cursor-wait' : ''"
        title="تصوير/رفع الروشتة اليدوية"
    >
        <span x-show="!uploading" class="leading-none">📸</span>
        <span x-show="uploading" x-cloak class="inline-block h-3.5 w-3.5 animate-spin rounded-full border-2 border-violet-400 border-t-violet-800"></span>
        @if(($size ?? 'sm') === 'md')
            <span x-show="!uploading"><x-info field="clinic.manual_rx_camera" /> تصوير الروشتة</span>
        @endif
    </button>

    <p x-show="successMsg" x-cloak x-text="successMsg" class="text-[10px] font-medium text-emerald-700 max-w-[8rem] text-left leading-tight"></p>
    <p x-show="errorMsg" x-cloak x-text="errorMsg" class="text-[10px] font-medium text-red-700 max-w-[8rem] text-left leading-tight"></p>
</div>

@once
@push('scripts')
<script>
function manualRxCapture(patientId, appointmentId) {
    return {
        patientId,
        appointmentId,
        uploading: false,
        successMsg: '',
        errorMsg: '',
        onFileSelected(event) {
            const file = event.target.files?.[0];
            event.target.value = '';
            if (!file) return;
            this.upload(file);
        },
        onExternalUpload(detail) {
            if (detail.timeline_event) {
                window.dispatchEvent(new CustomEvent('patient-timeline-prepend', {
                    detail: { event: detail.timeline_event, patientId: this.patientId },
                }));
            }
        },
        async upload(file) {
            this.uploading = true;
            this.successMsg = '';
            this.errorMsg = '';
            const form = new FormData();
            form.append('patient_id', String(this.patientId));
            if (this.appointmentId) {
                form.append('appointment_id', String(this.appointmentId));
            }
            form.append('image', file);
            try {
                const res = await fetch(@js(route('clinic.api.upload-manual-prescription')), {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]')?.content ?? '',
                        'Accept': 'application/json',
                    },
                    body: form,
                });
                const data = await res.json().catch(() => ({}));
                if (!res.ok) {
                    throw new Error(data.message || 'تعذّر رفع الصورة.');
                }
                this.successMsg = '✓ تم الرفع';
                window.dispatchEvent(new CustomEvent('manual-rx-uploaded', {
                    detail: {
                        patientId: this.patientId,
                        appointmentId: this.appointmentId,
                        timeline_event: data.timeline_event,
                        attachment: data.attachment,
                    },
                }));
                setTimeout(() => { this.successMsg = ''; }, 4000);
            } catch (e) {
                this.errorMsg = e.message || 'خطأ في الرفع';
                setTimeout(() => { this.errorMsg = ''; }, 5000);
            } finally {
                this.uploading = false;
            }
        },
    };
}
</script>
@endpush
@endonce
