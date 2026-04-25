@extends('layouts.app')

@section('title', 'طلب إجازة جديد — الموارد البشرية')

@section('breadcrumb')
    <a href="{{ route('dashboard') }}" class="text-gray-500 hover:text-indigo-600">الرئيسية</a>
    <span>›</span>
    <a href="{{ route('hr.dashboard') }}" class="text-gray-500 hover:text-indigo-600">الموارد البشرية</a>
    <span>›</span>
    <a href="{{ route('hr.leave-requests') }}" class="text-gray-500 hover:text-indigo-600">طلبات الإجازة</a>
    <span>›</span>
    <span class="text-indigo-900 font-semibold truncate">طلب جديد</span>
@endsection

@section('content')
<div class="max-w-5xl space-y-6" dir="rtl" x-data="leaveRequestForm({ excluded: @json($leaveExcludedIsoWeekdays), start: @json(old('start_date', '')), end: @json(old('end_date', '')) })">
    <div class="flex flex-wrap items-start justify-between gap-4">
        <div class="flex items-start gap-3">
            <a href="{{ route('hr.leave-requests') }}" class="mt-0.5 inline-flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-lg border border-gray-200 bg-white text-gray-600 shadow-sm hover:bg-gray-50" title="رجوع" aria-label="رجوع">
                <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M15 5l-7 7 7 7" /></svg>
            </a>
            <div>
                <h1 class="inline-flex items-center gap-2 text-2xl font-bold text-gray-900">
                    <span class="inline-flex h-9 w-9 items-center justify-center rounded-lg bg-indigo-100 text-indigo-600" aria-hidden="true">
                        <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 16 16"><path d="M3.5 0a.5.5 0 0 1 .5.5V1h8V.5a.5.5 0 0 1 1 0V1h1a2 2 0 0 1 2 2v11a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2V3a2 2 0 0 1 2-2h1V.5a.5.5 0 0 1 .5-.5zM1 4v10a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1V4H1z"/></svg>
                    </span>
                    طلب إجازة جديد
                    <x-info field="hr.leaves_create_title" />
                </h1>
                <p class="mt-1 text-sm text-gray-500">تقديم طلب إجازة جديد <x-info field="hr.leaves_create_subtitle" /></p>
                <p class="mt-2 max-w-2xl text-xs text-gray-500"><x-info field="hr.leaves_create_intro" /></p>
            </div>
        </div>
    </div>

    @if (session('error'))
        <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">{{ session('error') }}</div>
    @endif

    <form method="post" action="{{ route('hr.leave-requests.store') }}" enctype="multipart/form-data" class="space-y-6">
        @csrf
        <div class="grid grid-cols-1 gap-6 lg:grid-cols-4">
            <div class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm lg:col-span-1">
                <h2 class="text-sm font-bold text-gray-900">ملخص الإجازة <x-info field="hr.leaves_create_summary" /></h2>
                <div class="mt-4 text-center">
                    <p class="text-sm text-gray-500">أيام العمل <x-info field="hr.leaves_create_working_days" /></p>
                    <p class="mt-2 text-4xl font-bold tabular-nums text-indigo-600" x-text="workDays">0</p>
                </div>
            </div>

            <div class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm lg:col-span-3 md:p-6">
                <h2 class="mb-4 text-base font-bold text-gray-900">تفاصيل الطلب <x-info field="hr.leaves_create_details" /></h2>
                <div class="space-y-4">
                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                        <div>
                            <label for="create_leave_employee_id" class="mb-1.5 block text-sm font-semibold text-gray-800">موظف <span class="text-red-600" aria-hidden="true">*</span> <x-info field="hr.leaves_form_employee" /></label>
                            <x-searchable-select
                                name="employee_id"
                                id="create_leave_employee_id"
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
                        <div>
                            <label for="create_leave_type" class="mb-1.5 block text-sm font-semibold text-gray-800">نوع الإجازة <span class="text-red-600" aria-hidden="true">*</span> <x-info field="hr.leaves_form_type" /></label>
                            <x-searchable-select
                                name="leave_type"
                                id="create_leave_type"
                                :options="$leaveTypeOptions"
                                :value="old('leave_type', 'annual')"
                                :required="true"
                                :error="$errors->has('leave_type')"
                                :empty-option="false"
                                empty-label="اختر نوع الإجازة"
                                placeholder="اختر نوع الإجازة…"
                            />
                            @error('leave_type')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                        </div>
                    </div>

                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <label for="create_start_date" class="mb-1.5 block text-sm font-semibold text-gray-800">تاريخ البداية <span class="text-red-600" aria-hidden="true">*</span> <x-info field="hr.leaves_form_start" /></label>
                            <div class="relative">
                                <input type="date" name="start_date" id="create_start_date" x-model="start" value="{{ old('start_date') }}" class="w-full min-h-[2.5rem] rounded-lg border border-gray-200 bg-white py-2.5 pl-3 pr-10 text-sm @error('start_date') border-red-500 @enderror" required>
                                <span class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-2.5 text-gray-400" aria-hidden="true">
                                    <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 16 16"><path d="M3.5 0a.5.5 0 0 1 .5.5V1h8V.5a.5.5 0 0 1 1 0V1h1a2 2 0 0 1 2 2v11a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2V3a2 2 0 0 1 2-2h1V.5a.5.5 0 0 1 .5-.5zM1 4v10a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1V4H1z"/></svg>
                                </span>
                            </div>
                            @error('start_date')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label for="create_end_date" class="mb-1.5 block text-sm font-semibold text-gray-800">تاريخ الانتهاء <span class="text-red-600" aria-hidden="true">*</span> <x-info field="hr.leaves_form_end" /></label>
                            <div class="relative">
                                <input type="date" name="end_date" id="create_end_date" x-model="end" value="{{ old('end_date') }}" class="w-full min-h-[2.5rem] rounded-lg border border-gray-200 bg-white py-2.5 pl-3 pr-10 text-sm @error('end_date') border-red-500 @enderror" required>
                                <span class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-2.5 text-gray-400" aria-hidden="true">
                                    <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 16 16"><path d="M3.5 0a.5.5 0 0 1 .5.5V1h8V.5a.5.5 0 0 1 1 0V1h1a2 2 0 0 1 2 2v11a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2V3a2 2 0 0 1 2-2h1V.5a.5.5 0 0 1 .5-.5zM1 4v10a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1V4H1z"/></svg>
                                </span>
                            </div>
                            @error('end_date')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                        </div>
                    </div>

                    <div>
                        <label for="create_reason" class="mb-1.5 block text-sm font-semibold text-gray-800">السبب <x-info field="hr.leaves_form_reason" /></label>
                        <textarea name="reason" id="create_reason" rows="4" class="w-full rounded-lg border border-gray-200 bg-white px-3 py-2.5 text-sm @error('reason') border-red-500 @enderror" placeholder="أدخل سبب الإجازة…">{{ old('reason') }}</textarea>
                        <p class="mt-1 text-xs text-gray-500">قدّم تفاصيل إضافية حول طلب إجازتك. <x-info field="hr.leaves_create_reason_help" /></p>
                        @error('reason')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label class="mb-1.5 block text-sm font-semibold text-gray-800">مرفقات <x-info field="hr.leaves_form_attachments" /></label>
                        <input type="file" name="attachments[]" multiple class="w-full cursor-pointer rounded-lg border border-dashed border-gray-200 bg-gray-50/60 px-3 py-3 text-sm file:me-2 file:rounded-md file:border-0 file:bg-white file:px-3 file:py-1.5 file:text-sm file:font-medium file:text-indigo-700 hover:border-indigo-200">
                        @error('attachments')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                        @error('attachments.*')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>
                </div>
            </div>
        </div>

        <div class="flex flex-wrap items-center justify-end gap-2 border-t border-gray-100 pt-4">
            <a href="{{ route('hr.leave-requests') }}" class="inline-flex items-center justify-center rounded-lg border border-gray-200 bg-white px-5 py-2.5 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50">إلغاء</a>
            <button type="submit" class="inline-flex items-center justify-center gap-2 rounded-lg bg-indigo-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-indigo-700">
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
        Alpine.data('leaveRequestForm', (opts) => ({
            excluded: Array.isArray(opts.excluded) ? opts.excluded.map(Number) : [5, 6],
            start: opts.start || '',
            end: opts.end || '',
            get workDays() {
                if (!this.start || !this.end) return 0;
                const s = new Date(this.start + 'T00:00:00');
                const e = new Date(this.end + 'T00:00:00');
                if (e < s) return 0;
                let c = 0;
                for (let t = s.getTime(), end = e.getTime(); t <= end; t += 86400000) {
                    const d = new Date(t);
                    const iso = d.getDay() === 0 ? 7 : d.getDay();
                    if (!this.excluded.includes(iso)) c++;
                }
                return c;
            },
        }));
    });
</script>
@endpush
