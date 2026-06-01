@extends('layouts.app')

@section('title', 'دورة رواتب جديدة - '.config('app.name'))

@section('breadcrumb')
    <a href="{{ route('dashboard') }}" class="text-gray-500 hover:text-indigo-600">الرئيسية</a>
    <span>›</span>
    <a href="{{ route('hr.dashboard') }}" class="text-gray-500 hover:text-indigo-600">الموارد البشرية</a>
    <span>›</span>
    <a href="{{ route('hr.payrolls.index') }}" class="text-gray-500 hover:text-indigo-600">الرواتب</a>
    <span>›</span>
    <span class="text-indigo-900 font-semibold truncate">جديد</span>
@endsection

@php
    $defaultQuick = $quickMonths[count($quickMonths) - 1] ?? ($quickMonths[0] ?? null);
    $oldStart = old('period_start', $defaultQuick['start'] ?? now()->startOfMonth()->format('Y-m-d'));
    $oldEnd = old('period_end', $defaultQuick['end'] ?? now()->endOfMonth()->format('Y-m-d'));
    $oldName = old('name', $defaultQuick['nameSuggest'] ?? '');
@endphp

@section('content')
<div class="max-w-5xl space-y-6" dir="rtl" x-data="payrollCycleCreateForm({ months: @json($quickMonths), start: @json($oldStart), end: @json($oldEnd), name: @json($oldName) })">
    <div class="flex flex-wrap items-start justify-between gap-4">
        <div class="flex items-start gap-3">
            <a href="{{ route('hr.payrolls.index') }}" class="mt-0.5 inline-flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-lg border border-gray-200 bg-white text-gray-600 shadow-sm hover:bg-gray-50" title="رجوع" aria-label="رجوع">
                <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M15 5l-7 7 7 7" /></svg>
            </a>
            <div>
                <h1 class="inline-flex items-center gap-2 text-2xl font-bold text-gray-900">
                    <span class="inline-flex h-9 w-9 items-center justify-center rounded-lg bg-indigo-100 text-indigo-600" aria-hidden="true">
                        <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 16 16"><path d="M0 2.5A1.5 1.5 0 0 1 1.5 1h11A1.5 1.5 0 0 1 14 2.5v1.382c.307.345.5.729.5 1.118 0 .391-.193.774-.5 1.118V12.5A1.5 1.5 0 0 1 12.5 14h-11A1.5 1.5 0 0 1 0 12.5V9.382a1.497 1.497 0 0 1-.5-1.118c0-.39.193-.774.5-1.118V2.5z"/><path d="M0 7.5A1.5 1.5 0 0 1 1.5 6h11a1.5 1.5 0 0 1 1.5 1.5v1.321C13.463 9.42 12 10.395 12 11c0 .628.692 1.41 1.5 2.189V12.5a.5.5 0 0 1-.5.5h-11a.5.5 0 0 1-.5-.5V9.414c-.757-.746-1.5-1.527-1.5-2.189 0-.643.693-1.248 1.5-2.122V7.5z"/></svg>
                    </span>
                    دورة رواتب جديدة
                    <x-info field="hr.payroll_create_page_title" />
                </h1>
                <p class="mt-1 text-sm text-gray-500"><x-info field="hr.payroll_create_intro" /></p>
            </div>
        </div>
    </div>

    @if (session('error'))
        <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">{{ session('error') }}</div>
    @endif

    <form method="post" action="{{ route('hr.payrolls.store') }}" class="space-y-6">
        @csrf
        <div class="grid grid-cols-1 gap-6 lg:grid-cols-4">
            <div class="space-y-4 lg:col-span-1">
                <div class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm">
                    <h2 class="text-sm font-bold text-gray-900">إكمال الدورة <x-info field="hr.payroll_create_completion_card" /></h2>
                    <ul class="mt-3 space-y-2 text-sm text-gray-700">
                        <li class="flex items-start gap-2">
                            @if($accountingLinksReady)
                                <span class="mt-0.5 inline-flex h-5 w-5 flex-shrink-0 items-center justify-center rounded-full bg-emerald-100 text-emerald-700" aria-hidden="true">✓</span>
                                <span>الربط المحاسبي جاهز للاعتماد.</span>
                            @else
                                <span class="mt-0.5 inline-flex h-5 w-5 flex-shrink-0 items-center justify-center rounded-full bg-amber-100 text-amber-800" aria-hidden="true">!</span>
                                <span>أكمل <a href="{{ route('settings.company.edit') }}#payroll-accounts" class="font-semibold text-indigo-600 hover:text-indigo-800">ربط حسابات الرواتب</a> في إعدادات المنشأة قبل الاعتماد.</span>
                            @endif
                        </li>
                        <li class="flex items-start gap-2">
                            <span class="text-gray-400" aria-hidden="true">•</span>
                            <span>راجع <a href="{{ route('hr.attendance') }}" class="font-semibold text-indigo-600 hover:text-indigo-800">الحضور</a> للفترة.</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <span class="text-gray-400" aria-hidden="true">•</span>
                            <span>بعد الحفظ: <a href="{{ route('hr.payrolls.index') }}" class="font-semibold text-indigo-600 hover:text-indigo-800">قائمة الدورات</a> ← عرض الدورة ← اعتماد ← دفع.</span>
                        </li>
                    </ul>
                </div>
                <div class="rounded-lg border border-indigo-100 bg-indigo-50/60 p-4 text-sm text-gray-800 shadow-sm">
                    <p class="font-semibold text-indigo-900">ملخص سريع</p>
                    <p class="mt-2 text-xs leading-relaxed text-gray-600"><x-info field="hr.payroll_create_accounting_hint" /></p>
                </div>
            </div>

            <div class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm md:p-6 lg:col-span-3">
                <h2 class="mb-1 inline-flex items-center gap-1 text-base font-bold text-gray-900">
                    تفاصيل المسير
                    <x-info field="hr.payroll_create_details_section" />
                </h2>
                <p class="mb-6 border-b border-gray-100 pb-4 text-xs text-gray-500">الفترة يجب أن تكون ضمن شهر واحد؛ يُستخدم الشهر لاحتساب الحضور والرواتب.</p>

                <div class="space-y-5">
                    <div>
                        <label for="payroll_cycle_name" class="mb-1.5 block text-sm font-semibold text-gray-800">الاسم <span class="text-red-600" aria-hidden="true">*</span> <x-info field="hr.payroll_field_cycle_name" /></label>
                        <input type="text" name="name" id="payroll_cycle_name" x-model="name" required maxlength="255" class="w-full rounded-lg border border-gray-200 bg-gray-50/50 px-3 py-2.5 text-sm focus:border-indigo-300 focus:bg-white focus:ring-1 focus:ring-indigo-500 @error('name') border-red-500 @enderror" placeholder="مثال: مسير رواتب — فبراير 2026">
                        @error('name')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <p class="mb-2 text-sm font-semibold text-gray-800">اختيار سريع <x-info field="hr.payroll_field_quick_month" /></p>
                        <div class="flex flex-wrap gap-2">
                            <template x-for="(m, idx) in months" :key="idx">
                                <button type="button" @click="selectQuick(idx)" :class="activeIdx === idx ? 'border-indigo-500 bg-indigo-50 text-indigo-900 ring-1 ring-indigo-200' : 'border-gray-200 bg-white text-gray-700 hover:bg-gray-50'" class="rounded-lg border px-3 py-2 text-xs font-semibold shadow-sm" x-text="m.label"></button>
                            </template>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <label for="period_start" class="mb-1.5 block text-sm font-semibold text-gray-800">بداية الفترة <span class="text-red-600" aria-hidden="true">*</span> <x-info field="hr.payroll_field_period_start" /></label>
                            <div class="relative">
                                <input type="date" name="period_start" id="period_start" x-model="periodStart" class="w-full min-h-[2.5rem] rounded-lg border border-gray-200 bg-gray-50/50 py-2.5 pl-3 pr-10 text-sm focus:border-indigo-300 focus:bg-white @error('period_start') border-red-500 @enderror" required>
                                <span class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-2.5 text-gray-400" aria-hidden="true">
                                    <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 16 16"><path d="M3.5 0a.5.5 0 0 1 .5.5V1h8V.5a.5.5 0 0 1 1 0V1h1a2 2 0 0 1 2 2v11a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2V3a2 2 0 0 1 2-2h1V.5a.5.5 0 0 1 .5-.5zM1 4v10a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1V4H1z"/></svg>
                                </span>
                            </div>
                            @error('period_start')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label for="period_end" class="mb-1.5 block text-sm font-semibold text-gray-800">نهاية الفترة <span class="text-red-600" aria-hidden="true">*</span> <x-info field="hr.payroll_field_period_end" /></label>
                            <div class="relative">
                                <input type="date" name="period_end" id="period_end" x-model="periodEnd" class="w-full min-h-[2.5rem] rounded-lg border border-gray-200 bg-gray-50/50 py-2.5 pl-3 pr-10 text-sm focus:border-indigo-300 focus:bg-white @error('period_end') border-red-500 @enderror" required>
                                <span class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-2.5 text-gray-400" aria-hidden="true">
                                    <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 16 16"><path d="M3.5 0a.5.5 0 0 1 .5.5V1h8V.5a.5.5 0 0 1 1 0V1h1a2 2 0 0 1 2 2v11a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2V3a2 2 0 0 1 2-2h1V.5a.5.5 0 0 1 .5-.5zM1 4v10a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1V4H1z"/></svg>
                                </span>
                            </div>
                            @error('period_end')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                        </div>
                    </div>

                    <div>
                        <label for="payroll_department_id" class="mb-1.5 block text-sm font-semibold text-gray-800">القسم <x-info field="hr.payroll_field_department" /></label>
                        <x-searchable-select
                            name="department_id"
                            id="payroll_department_id"
                            :options="$departmentOptions"
                            :value="old('department_id', '')"
                            :required="false"
                            :error="$errors->has('department_id')"
                            :empty-option="true"
                            empty-label="جميع الأقسام"
                            placeholder="ابحث أو اختر القسم…"
                        />
                        @error('department_id')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label for="payment_date" class="mb-1.5 block text-sm font-semibold text-gray-800">تاريخ الدفع المقترح <x-info field="hr.payroll_field_payment_date" /></label>
                        <input type="date" name="payment_date" id="payment_date" value="{{ old('payment_date') }}" class="w-full rounded-lg border border-gray-200 bg-gray-50/50 px-3 py-2.5 text-sm focus:border-indigo-300 focus:bg-white @error('payment_date') border-red-500 @enderror">
                        @error('payment_date')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label for="payroll_notes" class="mb-1.5 block text-sm font-semibold text-gray-800">الوصف <x-info field="hr.payroll_field_description" /></label>
                        <textarea name="notes" id="payroll_notes" rows="4" maxlength="5000" class="w-full rounded-lg border border-gray-200 bg-gray-50/50 px-3 py-2.5 text-sm focus:border-indigo-300 focus:bg-white @error('notes') border-red-500 @enderror" placeholder="أدخل الوصف">{{ old('notes') }}</textarea>
                        @error('notes')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>
                </div>
            </div>
        </div>

        <div class="flex flex-wrap items-center justify-end gap-2 border-t border-gray-100 pt-4">
            <a href="{{ route('hr.payrolls.index') }}" class="inline-flex items-center justify-center rounded-lg border border-gray-200 bg-white px-5 py-2.5 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50">إلغاء</a>
            <button type="submit" class="inline-flex items-center justify-center gap-2 rounded-lg bg-indigo-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-indigo-700">
                <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 16 16" aria-hidden="true"><path d="M8 4a.5.5 0 0 1 .5.5v3h3a.5.5 0 0 1 0 1h-3v3a.5.5 0 0 1-1 0v-3h-3a.5.5 0 0 1 0-1h3v-3A.5.5 0 0 1 8 4z"/></svg>
                إنشاء مسير رواتب
            </button>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('payrollCycleCreateForm', (opts) => ({
            months: Array.isArray(opts.months) ? opts.months : [],
            name: opts.name || '',
            periodStart: opts.start || '',
            periodEnd: opts.end || '',
            activeIdx: null,
            init() {
                const last = this.months.length - 1;
                if (last >= 0) {
                    const m = this.months[last];
                    if (this.periodStart === m.start && this.periodEnd === m.end) {
                        this.activeIdx = last;
                    }
                }
            },
            selectQuick(idx) {
                const m = this.months[idx];
                if (!m) return;
                this.activeIdx = idx;
                this.name = m.nameSuggest || this.name;
                this.periodStart = m.start;
                this.periodEnd = m.end;
            },
        }));
    });
</script>
@endpush
