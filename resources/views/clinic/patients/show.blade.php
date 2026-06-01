@extends('layouts.clinic')

@section('title', $patient->name.' — '.config('app.name'))

@section('content')
<div class="space-y-8" dir="rtl">
    <div class="flex flex-wrap items-start justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-teal-950">{{ $patient->name }}</h1>
            <p class="text-sm text-gray-500 font-mono">{{ $patient->code }} · {{ $patient->phone ?? '—' }}</p>
        </div>
        <div class="flex flex-wrap gap-2">
            <x-clinic-manual-prescription-capture :patient-id="$patient->id" size="md" />
            <a href="{{ route('clinic.patients.edit', $patient) }}"
               class="rounded-lg border border-gray-200 bg-white px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">تعديل البيانات</a>
            <a href="{{ route('clinic.prescriptions.create', ['patient_id' => $patient->id, 'return_to' => 'appointments']) }}"
               class="rounded-lg bg-teal-600 px-4 py-2 text-sm text-white hover:bg-teal-700">℞ روشتة</a>
            <a href="{{ route('clinic.patients.index') }}" class="rounded-lg border border-gray-200 bg-white px-4 py-2 text-sm">رجوع</a>
        </div>
    </div>

    {{-- Timeline --}}
    <section class="rounded-2xl border border-teal-100 bg-white p-6 shadow-sm"
             x-data="patientTimeline(@js($timelineEvents ?? []), {{ (int) $patient->id }})"
             @patient-timeline-prepend.window="if ($event.detail?.patientId === patientId) { prependEvent($event.detail.event); }">
        <h2 class="text-lg font-semibold text-teal-950 mb-4"><x-info field="clinic.patient_timeline" /> الخط الزمني</h2>
        <div class="relative pr-6 border-r-2 border-teal-200 space-y-6">
            <template x-if="events.length === 0">
                <p class="text-sm text-gray-500">لا توجد أحداث بعد.</p>
            </template>
            <template x-for="event in events" :key="event.type + '-' + event.id">
                <div class="relative">
                    <span class="absolute -right-[1.65rem] top-1 h-3 w-3 rounded-full ring-4 ring-white" :class="event.dot_class"></span>
                    <p class="text-xs text-gray-400" x-text="event.date_label"></p>
                    <p class="font-semibold text-gray-900" x-text="event.title"></p>
                    <template x-if="event.summary">
                        <p class="text-sm text-gray-600 mt-0.5" x-text="event.summary?.length > 120 ? event.summary.slice(0, 120) + '…' : event.summary"></p>
                    </template>
                    <template x-if="event.type === 'manual_prescription' && event.meta?.preview_url">
                        <a :href="event.meta.preview_url" target="_blank" class="mt-2 inline-block group">
                            <img :src="event.meta.preview_url" alt="روشتة يدوية" loading="lazy"
                                 class="max-h-32 rounded-lg border border-violet-200 shadow-sm group-hover:ring-2 group-hover:ring-violet-300 transition">
                        </a>
                    </template>
                </div>
            </template>
        </div>
    </section>

    <div class="grid gap-6 lg:grid-cols-2">
        {{-- Clinical profile --}}
        <section class="rounded-2xl border border-teal-100 bg-white p-6 shadow-sm">
            <h2 class="font-semibold text-gray-900 mb-4"><x-info field="clinic.clinical_profile" /> الملف الطبي</h2>
            <form method="POST" action="{{ route('clinic.patients.clinical.update', $patient) }}" class="space-y-3">
                @csrf
                @method('PATCH')
                <div>
                    <label class="block text-sm font-medium mb-1"><x-info field="clinic.allergies" /> الحساسية</label>
                    <textarea name="allergies" rows="2" class="w-full rounded-lg border-gray-300 text-sm" placeholder="مثال: بنسلين، أسبرين">{{ old('allergies', $patient->allergies) }}</textarea>
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1"><x-info field="clinic.chronic_conditions" /> الأمراض المزمنة</label>
                    <textarea name="chronic_conditions" rows="2" class="w-full rounded-lg border-gray-300 text-sm">{{ old('chronic_conditions', $patient->chronic_conditions) }}</textarea>
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1"><x-info field="clinic.medical_history" /> التاريخ الطبي</label>
                    <textarea name="medical_history_summary" rows="2" class="w-full rounded-lg border-gray-300 text-sm">{{ old('medical_history_summary', $patient->medical_history_summary) }}</textarea>
                </div>
                <button type="submit" class="rounded-lg bg-teal-600 px-4 py-2 text-sm text-white">حفظ</button>
            </form>
        </section>

        {{-- Clinical note --}}
        <section class="rounded-2xl border border-teal-100 bg-white p-6 shadow-sm">
            <h2 class="font-semibold text-gray-900 mb-4"><x-info field="clinic.clinical_note" /> ملخص زيارة</h2>
            <form method="POST" action="{{ route('clinic.clinical-notes.store', $patient) }}" class="space-y-3">
                @csrf
                <div>
                    <label class="block text-sm font-medium mb-1"><x-info field="clinic.chief_complaint" /> الشكوى الرئيسية</label>
                    <textarea name="chief_complaint" rows="2" class="w-full rounded-lg border-gray-300 text-sm"></textarea>
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1"><x-info field="clinic.examination" /> الفحص السريري</label>
                    <textarea name="examination" rows="2" class="w-full rounded-lg border-gray-300 text-sm"></textarea>
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1"><x-info field="clinic.diagnosis" /> التشخيص</label>
                    <textarea name="diagnosis" rows="2" class="w-full rounded-lg border-gray-300 text-sm"></textarea>
                </div>
                <button type="submit" class="rounded-lg bg-indigo-600 px-4 py-2 text-sm text-white">حفظ الملخص</button>
            </form>
        </section>
    </div>

    {{-- Attachments --}}
    <section class="rounded-2xl border border-teal-100 bg-white p-6 shadow-sm">
        <h2 class="font-semibold text-gray-900 mb-4"><x-info field="clinic.attachments" /> المرفقات الطبية</h2>
        <form method="POST" action="{{ route('clinic.attachments.store', $patient) }}" enctype="multipart/form-data" class="flex flex-wrap items-end gap-3 mb-4">
            @csrf
            <div>
                <label class="block text-xs text-gray-500 mb-1">الملف</label>
                <input type="file" name="file" required accept=".pdf,image/*" class="text-sm">
            </div>
            <div>
                <label class="block text-xs text-gray-500 mb-1">النوع</label>
                <x-searchable-select name="category" :options="[
                    ['value' => 'xray', 'label' => 'أشعة'],
                    ['value' => 'lab', 'label' => 'تحاليل'],
                    ['value' => 'image', 'label' => 'صورة'],
                    ['value' => 'other', 'label' => 'أخرى'],
                ]" :searchable="false" value="lab" />
            </div>
            <button type="submit" class="rounded-lg bg-teal-600 px-4 py-2 text-sm text-white">رفع</button>
        </form>
        <ul class="divide-y divide-gray-50 text-sm">
            @forelse($patient->medicalAttachments as $att)
                <li class="flex items-center justify-between py-2">
                    <span>{{ $att->original_name }} <span class="text-gray-400 text-xs">({{ \App\Models\Clinic\MedicalAttachment::categoryLabels()[$att->category] ?? $att->category }})</span></span>
                    <div class="flex gap-2">
                        @if($att->isImage())
                            <a href="{{ route('clinic.attachments.preview', $att) }}" target="_blank" class="text-violet-700 text-xs">معاينة</a>
                        @endif
                        <a href="{{ route('clinic.attachments.download', $att) }}" class="text-teal-700 text-xs">تحميل</a>
                        <form method="POST" action="{{ route('clinic.attachments.destroy', $att) }}" onsubmit="return confirm('حذف؟')">@csrf @method('DELETE')<button type="submit" class="text-red-600 text-xs">حذف</button></form>
                    </div>
                </li>
            @empty
                <li class="text-gray-500 py-4">لا مرفقات.</li>
            @endforelse
        </ul>
    </section>
</div>
@endsection

@push('scripts')
<script>
function patientTimeline(initialEvents, patientId) {
    return {
        patientId,
        events: [...(initialEvents || [])].sort((a, b) => (b.date_ts || 0) - (a.date_ts || 0)),
        prependEvent(event) {
            if (!event) return;
            this.events = [event, ...this.events.filter(e => !(e.type === event.type && e.id === event.id))]
                .sort((a, b) => (b.date_ts || 0) - (a.date_ts || 0));
        },
    };
}
</script>
@endpush
