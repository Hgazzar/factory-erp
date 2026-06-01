@extends('layouts.clinic')

@section('title', 'جداول الأطباء — '.config('app.name'))

@section('content')
<div class="space-y-6" dir="rtl">
    @if(session('success'))
        <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-900">{{ session('success') }}</div>
    @endif

    <div class="clinic-page-header">
        <div>
            <h1 class="clinic-page-title text-2xl font-bold text-teal-950"><x-info field="clinic.doctor_schedules" /> جداول عمل الأطباء</h1>
            <p class="text-sm text-gray-500 mt-1"><x-info field="clinic.doctor_schedules_hint" /> حدّد أيام وساعات عمل كل طبيب لمنع الحجز المزدوج وتفعيل البوابة.</p>
        </div>
    </div>

    <div class="rounded-xl border border-teal-100 bg-white p-5">
        <form method="GET" action="{{ route('clinic.doctor-schedules.index') }}" class="flex flex-wrap items-end gap-3 mb-4">
            <div class="min-w-[220px]">
                <label class="block text-sm font-medium text-gray-700 mb-1"><x-info field="clinic.doctor" /> الطبيب</label>
                <x-searchable-select name="doctor_id" :options="$doctors" :selected="(string) ($selectedDoctorId ?? '')"
                                     placeholder="اختر طبيباً" :searchable="true" />
            </div>
            <button type="submit" class="clinic-btn clinic-btn-primary rounded-lg bg-teal-600 px-4 py-2 text-sm text-white">عرض الجدول</button>
        </form>

        @if($selectedDoctorId)
            <div class="grid md:grid-cols-2 gap-6">
                <div>
                    <h2 class="font-semibold text-teal-900 mb-3">فترات العمل الأسبوعية</h2>
                    @forelse($doctorSchedules as $schedule)
                        <div class="flex items-center justify-between border border-gray-100 rounded-lg px-3 py-2 mb-2 text-sm">
                            <span>{{ $dayLabels[$schedule->day_of_week] ?? $schedule->day_of_week }}
                                — {{ substr((string) $schedule->start_time, 0, 5) }} إلى {{ substr((string) $schedule->end_time, 0, 5) }}
                                ({{ $schedule->slot_duration_minutes }} د)</span>
                            <form method="POST" action="{{ route('clinic.doctor-schedules.destroy', $schedule->id) }}">
                                @csrf @method('DELETE')
                                <button type="submit" class="text-red-600 hover:underline text-xs">حذف</button>
                            </form>
                        </div>
                    @empty
                        <p class="text-gray-500 text-sm">لا توجد فترات — أضف أول جدول أدناه.</p>
                    @endforelse

                    <form method="POST" action="{{ route('clinic.doctor-schedules.store') }}" class="mt-4 space-y-3 border-t pt-4">
                        @csrf
                        <input type="hidden" name="doctor_employee_id" value="{{ $selectedDoctorId }}">
                        <div>
                            <label class="block text-sm font-medium mb-1"><x-info field="clinic.schedule_day" /> اليوم</label>
                            <x-searchable-select name="day_of_week" :searchable="false"
                                :options="collect($dayLabels)->map(fn ($l, $k) => ['value' => (string) $k, 'label' => $l])->values()->all()"
                                placeholder="اليوم" />
                        </div>
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-sm font-medium mb-1"><x-info field="clinic.schedule_from" /> من</label>
                                <input type="time" name="start_time" class="form-control w-full border rounded-lg px-3 py-2" required>
                            </div>
                            <div>
                                <label class="block text-sm font-medium mb-1"><x-info field="clinic.schedule_to" /> إلى</label>
                                <input type="time" name="end_time" class="form-control w-full border rounded-lg px-3 py-2" required>
                            </div>
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-1"><x-info field="clinic.slot_duration" /> مدة الموعد (دقيقة)</label>
                            <input type="number" name="slot_duration_minutes" value="30" min="5" max="240"
                                   class="form-control w-full border rounded-lg px-3 py-2">
                        </div>
                        <button type="submit" class="clinic-btn clinic-btn-primary rounded-lg bg-teal-600 px-4 py-2 text-sm text-white">إضافة فترة</button>
                    </form>
                </div>

                <div>
                    <h2 class="font-semibold text-teal-900 mb-3"><x-info field="clinic.blocked_slots" /> إغلاقات وإجازات</h2>
                    @foreach($blockedSlots as $block)
                        <div class="flex items-center justify-between border border-gray-100 rounded-lg px-3 py-2 mb-2 text-sm">
                            <span>
                                {{ $block->blocked_date->format('Y-m-d') }}
                                @if($block->is_full_day) — يوم كامل @else — {{ substr((string) $block->start_time, 0, 5) }}–{{ substr((string) $block->end_time, 0, 5) }} @endif
                                @if($block->doctor) ({{ $block->doctor->name }}) @else (كل العيادة) @endif
                            </span>
                            <form method="POST" action="{{ route('clinic.blocked-slots.destroy', $block->id) }}">
                                @csrf @method('DELETE')
                                <button type="submit" class="text-red-600 hover:underline text-xs">حذف</button>
                            </form>
                        </div>
                    @endforeach

                    <form method="POST" action="{{ route('clinic.blocked-slots.store') }}" class="mt-4 space-y-3 border-t pt-4">
                        @csrf
                        <div>
                            <label class="block text-sm font-medium mb-1"><x-info field="clinic.doctor" /> الطبيب (اختياري)</label>
                            <x-searchable-select name="doctor_employee_id" :options="$doctors" empty-label="كل العيادة" :searchable="true" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-1">التاريخ</label>
                            <input type="date" name="blocked_date" class="form-control w-full border rounded-lg px-3 py-2" required>
                        </div>
                        <label class="flex items-center gap-2 text-sm">
                            <input type="checkbox" name="is_full_day" value="1"> <x-info field="clinic.blocked_full_day" /> يوم كامل
                        </label>
                        <div class="grid grid-cols-2 gap-3">
                            <input type="time" name="start_time" class="border rounded-lg px-3 py-2" placeholder="من">
                            <input type="time" name="end_time" class="border rounded-lg px-3 py-2" placeholder="إلى">
                        </div>
                        <input type="text" name="reason" placeholder="السبب (اختياري)" class="w-full border rounded-lg px-3 py-2">
                        <button type="submit" class="clinic-btn rounded-lg border border-gray-300 px-4 py-2 text-sm">تسجيل إغلاق</button>
                    </form>
                </div>
            </div>
        @endif
    </div>
</div>
@endsection
