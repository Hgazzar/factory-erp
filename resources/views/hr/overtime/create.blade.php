@extends('layouts.app')

@section('title', 'طلب عمل إضافي جديد — الموارد البشرية')

@section('breadcrumb')
    <a href="{{ route('dashboard') }}" class="text-gray-500 hover:text-indigo-600">الرئيسية</a>
    <span>›</span>
    <a href="{{ route('hr.dashboard') }}" class="text-gray-500 hover:text-indigo-600">الموارد البشرية</a>
    <span>›</span>
    <a href="{{ route('hr.overtime') }}" class="text-gray-500 hover:text-indigo-600">العمل الإضافي</a>
    <span>›</span>
    <span class="text-indigo-900 font-semibold truncate">طلب جديد</span>
@endsection

@section('content')
<div
    class="max-w-5xl space-y-6"
    dir="rtl"
    x-data="overtimeCreateForm({ timeStart: @js(old('time_start', '')), timeEnd: @js(old('time_end', '')) })"
>
    <div class="flex flex-wrap items-start justify-between gap-4">
        <div class="flex items-start gap-3">
            <a href="{{ route('hr.overtime') }}" class="mt-0.5 inline-flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-lg border border-gray-200 bg-white text-gray-600 shadow-sm hover:bg-gray-50" title="رجوع" aria-label="رجوع">
                <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M15 5l-7 7 7 7" /></svg>
            </a>
            <div>
                <h1 class="inline-flex items-center gap-2 text-2xl font-bold text-gray-900">
                    <span class="inline-flex h-9 w-9 items-center justify-center rounded-lg bg-indigo-100 text-indigo-600" aria-hidden="true">
                        <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 16 16"><path d="M8.5 5.5a.5.5 0 0 0-1 0v2.793L6.354 6.146a.5.5 0 1 0-.708.708l2 2a.5.5 0 0 0 .708 0l2-2a.5.5 0 0 0-.708-.708L8.5 8.293V5.5z"/><path d="M0 4a2 2 0 0 1 2-2h12a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2V4zm2-1a1 1 0 0 0-1 1v8a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1V4a1 1 0 0 0-1-1H2z"/></svg>
                    </span>
                    طلب عمل إضافي جديد
                    <x-info field="hr.overtime_create_title" />
                </h1>
                <p class="mt-1 text-sm text-gray-500">تفاصيل طلب العمل الإضافي <x-info field="hr.overtime_create_subtitle" /></p>
            </div>
        </div>
    </div>

    @if (session('error'))
        <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">{{ session('error') }}</div>
    @endif

    <form method="post" action="{{ route('hr.overtime.store') }}" class="space-y-6" @submit="if (computedHours < 0.01) { $event.preventDefault(); window.alert('يرجى تحديد وقت بداية ونهاية يترتب عليهما مدة ساعات صحيحة.'); }">
        @csrf
        <div class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm md:p-6">
            <h2 class="mb-4 text-base font-bold text-gray-900">تفاصيل الطلب <x-info field="hr.overtime_create_card_intro" /></h2>
            <div class="space-y-6">
                <div>
                    <label for="ot_employee_id" class="mb-1.5 block text-sm font-semibold text-gray-800">موظف <span class="text-red-600" aria-hidden="true">*</span> <x-info field="hr.overtime_create_employee" /></label>
                    <x-searchable-select
                        name="employee_id"
                        id="ot_employee_id"
                        :options="$employeeOptions"
                        :value="old('employee_id', '')"
                        :required="true"
                        :error="$errors->has('employee_id')"
                        :empty-option="true"
                        empty-label="اختر الموظف"
                        placeholder="ابحث بالاسم أو الكود…"
                    />
                    @error('employee_id')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>

                <div class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-4">
                    <div>
                        <label for="ot_work_date" class="mb-1.5 block text-sm font-semibold text-gray-800">التاريخ <span class="text-red-600" aria-hidden="true">*</span> <x-info field="hr.overtime_create_date" /></label>
                        <div class="relative">
                            <span class="pointer-events-none absolute inset-y-0 right-0 flex w-9 items-center justify-center text-indigo-500" aria-hidden="true">
                                <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 16 16"><path d="M3.5 0a.5.5 0 0 1 .5.5V1h8V.5a.5.5 0 0 1 1 0V1h1a2 2 0 0 1 2 2v11a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2V3a2 2 0 0 1 2-2h1V.5a.5.5 0 0 1 .5-.5zM1 4v10a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1V4H1z"/></svg>
                            </span>
                            <input
                                type="date"
                                name="work_date"
                                id="ot_work_date"
                                value="{{ old('work_date', now()->format('Y-m-d')) }}"
                                required
                                class="w-full min-h-[2.5rem] rounded-lg border border-gray-200 bg-gray-50/50 py-2.5 pr-10 pl-3 text-sm focus:border-indigo-300 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500/20 @error('work_date') border-red-500 @enderror"
                            >
                        </div>
                        <p class="mt-1 text-xs text-gray-500">اختر التاريخ <x-info field="hr.overtime_create_date_help" /></p>
                        @error('work_date')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label for="ot_time_start" class="mb-1.5 block text-sm font-semibold text-gray-800">وقت البداية <span class="text-red-600" aria-hidden="true">*</span> <x-info field="hr.overtime_create_time_start" /></label>
                        <div class="relative" dir="ltr">
                            <span class="pointer-events-none absolute inset-y-0 right-0 flex w-9 items-center justify-center text-indigo-500" aria-hidden="true">
                                <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 16 16"><path d="M8 3.5a.5.5 0 0 0-1 0V9a.5.5 0 0 0 .252.434l3.5 2a.5.5 0 0 0 .496-.868L8 8.71V3.5z"/><path d="M8 16A8 8 0 1 0 8 0a8 8 0 0 0 0 16zm7-8A7 7 0 1 1 1 8a7 7 0 0 1 14 0z"/></svg>
                            </span>
                            <input
                                type="time"
                                name="time_start"
                                id="ot_time_start"
                                x-model="timeStart"
                                required
                                class="w-full min-h-[2.5rem] rounded-lg border border-gray-200 bg-gray-50/50 py-2.5 pr-10 pl-3 text-sm focus:border-indigo-300 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500/20 @error('time_start') border-red-500 @enderror"
                            >
                        </div>
                        @error('time_start')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label for="ot_time_end" class="mb-1.5 block text-sm font-semibold text-gray-800">وقت النهاية <span class="text-red-600" aria-hidden="true">*</span> <x-info field="hr.overtime_create_time_end" /></label>
                        <div class="relative" dir="ltr">
                            <span class="pointer-events-none absolute inset-y-0 right-0 flex w-9 items-center justify-center text-indigo-500" aria-hidden="true">
                                <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 16 16"><path d="M8 3.5a.5.5 0 0 0-1 0V9a.5.5 0 0 0 .252.434l3.5 2a.5.5 0 0 0 .496-.868L8 8.71V3.5z"/><path d="M8 16A8 8 0 1 0 8 0a8 8 0 0 0 0 16zm7-8A7 7 0 0 1 1 8a7 7 0 0 1 14 0z"/></svg>
                            </span>
                            <input
                                type="time"
                                name="time_end"
                                id="ot_time_end"
                                x-model="timeEnd"
                                required
                                class="w-full min-h-[2.5rem] rounded-lg border border-gray-200 bg-gray-50/50 py-2.5 pr-10 pl-3 text-sm focus:border-indigo-300 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500/20 @error('time_end') border-red-500 @enderror"
                            >
                        </div>
                        @error('time_end')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label for="ot_hours_display" class="mb-1.5 block text-sm font-semibold text-gray-800">الساعات <x-info field="hr.overtime_create_hours_computed" /></label>
                        <div class="relative">
                            <span class="pointer-events-none absolute inset-y-0 right-0 flex w-9 items-center justify-center text-indigo-500" aria-hidden="true">
                                <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 16 16"><path d="M8 3.5a.5.5 0 0 0-1 0V9a.5.5 0 0 0 .252.434l3.5 2a.5.5 0 0 0 .496-.868L8 8.71V3.5z"/><path d="M8 16A8 8 0 1 0 8 0a8 8 0 0 0 0 16zm7-8A7 7 0 0 1 1 8a7 7 0 0 1 14 0z"/></svg>
                            </span>
                            <div
                                id="ot_hours_display"
                                class="w-full min-h-[2.5rem] flex items-center rounded-lg border border-gray-200 bg-gray-50/50 py-2.5 pr-10 pl-3 text-sm text-gray-900 tabular-nums"
                                role="status"
                                aria-live="polite"
                                aria-atomic="true"
                                x-text="hoursLabel"
                            >—</div>
                        </div>
                        <p class="mt-1 text-xs text-gray-500">يُحسب تلقائياً من البداية والنهاية <x-info field="hr.overtime_create_hours_help" /></p>
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                    <div>
                        <label for="ot_kind" class="mb-1.5 block text-sm font-semibold text-gray-800">النوع <x-info field="hr.overtime_create_kind" /></label>
                        <x-custom-select
                            name="kind"
                            id="ot_kind"
                            :options="$kindOptions"
                            :value="old('kind', 'regular')"
                            :empty-option="false"
                            :searchable="false"
                        />
                        <p class="mt-1 text-xs text-gray-500">نوع العمل الإضافي <x-info field="hr.overtime_create_kind_help" /></p>
                        @error('kind')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>
                </div>

                <div>
                    <label for="ot_reason" class="mb-1.5 block text-sm font-semibold text-gray-800">السبب <span class="text-red-600" aria-hidden="true">*</span> <x-info field="hr.overtime_create_reason" /></label>
                    <textarea
                        name="reason"
                        id="ot_reason"
                        rows="4"
                        required
                        maxlength="2000"
                        placeholder="أدخل سبب العمل الإضافي"
                        class="w-full rounded-lg border border-gray-200 bg-gray-50/50 px-3 py-2.5 text-sm focus:border-indigo-300 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500/20 @error('reason') border-red-500 @enderror"
                    >{{ old('reason', '') }}</textarea>
                    <p class="mt-1 text-xs text-gray-500">سبب العمل الإضافي <x-info field="hr.overtime_create_reason_help" /></p>
                    @error('reason')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
            </div>
        </div>

        <div class="flex flex-wrap items-center justify-end gap-2">
            <a href="{{ route('hr.overtime') }}" class="inline-flex h-10 items-center rounded-lg border border-gray-200 bg-white px-4 text-sm font-medium text-gray-800 shadow-sm hover:bg-gray-50">إلغاء</a>
            <button type="submit" class="inline-flex h-10 items-center gap-2 rounded-lg bg-indigo-600 px-5 text-sm font-semibold text-white shadow-sm hover:bg-indigo-700">
                <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 16 16" aria-hidden="true"><path d="M4 1.5H3a2 2 0 0 0-2 2V14a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V3.5a2 2 0 0 0-2-2h-1v1h1a1 1 0 0 1 1 1V14a1 1 0 0 1-1 1H3a1 1 0 0 1-1-1V3.5a1 1 0 0 1 1-1h1v-1z"/><path d="M9.5 1a.5.5 0 0 1 .5.5v1a.5.5 0 0 1-.5.5h-3a.5.5 0 0 1-.5-.5v-1A.5.5 0 0 1 6.5 1h3z"/></svg>
                تقديم الطلب
            </button>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('overtimeCreateForm', (opts) => ({
            timeStart: typeof opts.timeStart === 'string' ? opts.timeStart : '',
            timeEnd: typeof opts.timeEnd === 'string' ? opts.timeEnd : '',
            workDate: typeof opts.workDate === 'string' ? opts.workDate : '',
            kind: typeof opts.kind === 'string' ? opts.kind : 'regular',
            get computedHours() {
                if (!this.timeStart || !this.timeEnd) {
                    return 0;
                }
                const p = (s) => {
                    const parts = s.split(':');
                    const h = parseInt(parts[0] ?? 0, 10);
                    const m = parseInt(parts[1] ?? 0, 10);
                    if (Number.isNaN(h) || Number.isNaN(m)) {
                        return null;
                    }
                    return h * 60 + m;
                };
                const a = p(this.timeStart);
                const b = p(this.timeEnd);
                if (a === null || b === null) {
                    return 0;
                }
                let end = b;
                if (end <= a) {
                    end += 24 * 60;
                }
                return Math.round(((end - a) / 60) * 100) / 100;
            },
            get hoursLabel() {
                const h = this.computedHours;
                if (h <= 0) {
                    return '—';
                }
                const t = h % 1 === 0 ? String(h) : h.toFixed(2);
                return t + 'h';
            },
        }));
    });
</script>
@endpush
