@extends('layouts.app')

@section('title', 'الحضور — الموارد البشرية')

@section('breadcrumb')
    <a href="{{ route('dashboard') }}" class="text-gray-500 hover:text-indigo-600">الرئيسية</a>
    <span>›</span>
    <a href="{{ route('hr.dashboard') }}" class="text-gray-500 hover:text-indigo-600">الموارد البشرية</a>
    <span>›</span>
    <span class="text-indigo-900 font-semibold truncate">الحضور</span>
@endsection

@section('content')
@php
    $presentRate = ($summary['total'] ?? 0) > 0 ? round((($summary['present'] ?? 0) / $summary['total']) * 100, 1) : 0;
    $openImport = request()->boolean('open_import');
@endphp
<div class="max-w-full space-y-6" dir="rtl">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">الحضور</h1>
            <p class="mt-1 text-sm text-gray-500">تتبع وإدارة حضور الموظفين اليومي</p>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <button type="button" class="inline-flex items-center rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-indigo-700" data-bs-toggle="modal" data-bs-target="#attendanceImportModal">
                استيراد من Excel
            </button>
            <a href="{{ route('hr.overtime') }}" class="inline-flex items-center rounded-lg border border-gray-200 bg-white px-4 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50">
                العمل الإضافي
            </a>
        </div>
    </div>

    @if (session('error'))
        <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">{{ session('error') }}</div>
    @endif

    @if (session('success'))
        <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-900">{{ session('success') }}</div>
    @endif

    @if (session('attendance_import_report'))
        @php $rep = session('attendance_import_report'); @endphp
        <div class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm">
            <h2 class="mb-3 text-sm font-bold text-gray-900">تقرير آخر استيراد</h2>
            <p class="text-sm text-gray-600">ناجح: <span class="font-semibold text-emerald-700">{{ $rep['success'] ?? 0 }}</span> — فاشل: <span class="font-semibold text-red-700">{{ count($rep['failed'] ?? []) }}</span></p>
            @if (! empty($rep['failed']))
                <div class="mt-3 max-h-48 overflow-y-auto rounded border border-gray-100 bg-gray-50 text-xs">
                    <table class="w-full table-auto text-right">
                        <thead class="sticky top-0 bg-gray-100 text-gray-600">
                            <tr>
                                <th class="px-3 py-2 font-semibold">الصف</th>
                                <th class="px-3 py-2 font-semibold">السبب</th>
                                <th class="px-3 py-2 font-semibold">التفاصيل</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            @foreach ($rep['failed'] as $f)
                                <tr>
                                    <td class="px-3 py-2 tabular-nums">{{ $f['row'] ?? '—' }}</td>
                                    <td class="px-3 py-2">{{ $f['reason'] ?? '—' }}</td>
                                    <td class="px-3 py-2">{{ $f['detail'] ?? '—' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    @endif

    <div class="grid grid-cols-2 gap-4 lg:grid-cols-5">
        <div class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm">
            <div class="text-sm text-gray-500">إجمالي الموظفين</div>
            <div class="mt-2 text-2xl font-bold text-gray-900">{{ $summary['total'] }}</div>
        </div>
        <div class="rounded-lg border border-emerald-100 bg-emerald-50/40 p-4 shadow-sm">
            <div class="text-sm text-emerald-700">حاضر</div>
            <div class="mt-2 text-2xl font-bold text-emerald-700">{{ $summary['present'] }}</div>
        </div>
        <div class="rounded-lg border border-amber-100 bg-amber-50/40 p-4 shadow-sm">
            <div class="text-sm text-amber-700">متأخر</div>
            <div class="mt-2 text-2xl font-bold text-amber-700">{{ $summary['late'] }}</div>
        </div>
        <div class="rounded-lg border border-red-100 bg-red-50/40 p-4 shadow-sm">
            <div class="text-sm text-red-700">غائب</div>
            <div class="mt-2 text-2xl font-bold text-red-700">{{ $summary['absent'] }}</div>
            <div class="mt-2 border-t border-red-100/80 pt-2">
                <div class="text-xs font-medium text-blue-700">إجازة مدفوعة</div>
                <div class="mt-0.5 text-lg font-bold tabular-nums text-blue-800">{{ $summary['leave'] ?? 0 }}</div>
            </div>
        </div>
        <div class="rounded-lg border border-indigo-100 bg-indigo-50/40 p-4 shadow-sm">
            <div class="text-sm text-indigo-700">نسبة الحضور</div>
            <div class="mt-2 text-2xl font-bold text-indigo-700">{{ $presentRate }}%</div>
        </div>
    </div>

    <div class="rounded-lg border border-gray-200 bg-white p-3 shadow-sm md:p-4">
        <form method="get" action="{{ route('hr.attendance') }}" class="flex min-w-0 flex-row flex-wrap items-center gap-2 md:flex-nowrap">
            <input type="search"
                   name="search"
                   value="{{ request('search') }}"
                   placeholder="بحث"
                   autocomplete="off"
                   class="min-w-[10rem] min-h-[2.5rem] flex-1 rounded-lg border border-gray-200 bg-white px-3 py-2.5 text-right text-sm text-gray-900 placeholder:text-gray-400 shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/25">
            <div class="min-w-[10.5rem] max-w-full shrink-0 basis-[12rem] md:max-w-[20rem]">
                <x-custom-select
                    id="attendance_department_id"
                    name="department_id"
                    :options="$departmentSelectOptions"
                    :value="(string) request('department_id', '')"
                    placeholder="تصفية حسب القسم..."
                    empty-label="جميع الأقسام"
                    :empty-option="false"
                    :fixed-panel="true"
                />
                <div class="mt-1 text-xs text-gray-500"><x-info field="hr.attendance_department_filter" /></div>
            </div>
            <div class="min-w-[14rem] max-w-full shrink-0 basis-[15rem] md:max-w-[24rem]">
                <x-custom-select
                    id="attendance_date"
                    name="attendance_date"
                    :options="$dateOptions"
                    :value="$selectedDate"
                    placeholder="اختر التاريخ..."
                    :empty-option="false"
                    :fixed-panel="true"
                />
                <div class="mt-1 text-xs text-gray-500"><x-info field="hr.attendance_date_filter" /></div>
            </div>
            <button type="submit" class="inline-flex h-10 min-w-[6.5rem] shrink-0 items-center justify-center rounded-lg bg-blue-600 px-5 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-700">
                تطبيق
            </button>
        </form>
    </div>

    <div class="w-full min-w-0 overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm">
        <div class="w-full min-w-0 overflow-x-auto">
            <table class="w-full table-auto whitespace-nowrap text-right text-sm text-gray-800">
                <thead class="bg-gray-50 text-gray-600">
                    <tr class="border-b border-gray-200">
                        <th class="px-6 py-4 text-right font-semibold"><span class="inline-flex items-center gap-1.5">الموظف <x-info field="hr.attendance_employee_col" /></span></th>
                        <th class="px-6 py-4 text-right font-semibold"><span class="inline-flex items-center gap-1.5">القسم <x-info field="hr.attendance_department_col" /></span></th>
                        <th class="px-6 py-4 text-right font-semibold"><span class="inline-flex items-center gap-1.5">تسجيل الحضور <x-info field="hr.attendance_checkin_col" /></span></th>
                        <th class="px-6 py-4 text-right font-semibold"><span class="inline-flex items-center gap-1.5">تسجيل الانصراف <x-info field="hr.attendance_checkout_col" /></span></th>
                        <th class="px-6 py-4 text-right font-semibold"><span class="inline-flex items-center gap-1.5">ساعات العمل <x-info field="hr.attendance_hours_col" /></span></th>
                        <th class="px-6 py-4 text-right font-semibold"><span class="inline-flex items-center gap-1.5">دقائق التأخير <x-info field="hr.attendance_minutes_late_col" /></span></th>
                        <th class="px-6 py-4 text-right font-semibold"><span class="inline-flex items-center gap-1.5">قيمة الخصم <x-info field="hr.attendance_deduction_col" /></span></th>
                        <th class="px-6 py-4 text-right font-semibold"><span class="inline-flex items-center gap-1.5">الحالة <x-info field="hr.attendance_status_col" /></span></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 bg-white">
                    @forelse($attendanceRows as $row)
                        <tr class="hover:bg-gray-50/80">
                            <td class="px-6 py-4">
                                <a href="{{ route('hr.employees.show', $row['employee']) }}" class="font-semibold text-gray-900 hover:text-blue-700">
                                    {{ $row['employee']->name }}
                                </a>
                            </td>
                            <td class="px-6 py-4 text-gray-700">{{ $row['employee']->department?->name ?? $row['employee']->department ?? '—' }}</td>
                            <td class="px-6 py-4 tabular-nums text-gray-800">{{ $row['check_in'] }}</td>
                            <td class="px-6 py-4 tabular-nums text-gray-800">{{ $row['check_out'] }}</td>
                            <td class="px-6 py-4 tabular-nums text-gray-800">{{ $row['work_hours'] }}</td>
                            <td class="px-6 py-4 tabular-nums text-gray-800">{{ $row['minutes_late_display'] }}</td>
                            <td class="px-6 py-4 tabular-nums text-gray-800">{{ $row['deduction_display'] }}</td>
                            <td class="px-6 py-4">
                                @if($row['status_key'] === 'present')
                                    <span class="inline-flex items-center rounded-full bg-emerald-100 px-2.5 py-0.5 text-xs font-semibold text-emerald-800">حاضر</span>
                                @elseif($row['status_key'] === 'late')
                                    <span class="inline-flex items-center rounded-full bg-amber-100 px-2.5 py-0.5 text-xs font-semibold text-amber-800">متأخر</span>
                                @elseif($row['status_key'] === 'leave')
                                    <span class="inline-flex items-center rounded-full bg-blue-100 px-2.5 py-0.5 text-xs font-semibold text-blue-800">إجازة مدفوعة</span>
                                @else
                                    <span class="inline-flex items-center rounded-full bg-red-100 px-2.5 py-0.5 text-xs font-semibold text-red-800">غائب</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-6 py-10 text-center text-sm text-gray-500">لا توجد سجلات حضور مطابقة للتصفية.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- مودال استيراد الحضور (نفس أسلوب استيراد الموردين/الأصناف) --}}
    <div class="modal fade" id="attendanceImportModal" tabindex="-1" aria-hidden="true" dir="rtl"
         x-data="attendanceImportFlow()"
         @hidden.bs.modal="reset()">
        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content rounded-2xl">
                <div class="modal-header border-b border-gray-200">
                    <h5 class="modal-title text-base font-semibold text-gray-900">استيراد الحضور من Excel / CSV</h5>
                    <button type="button" class="btn-close ms-0 me-auto" data-bs-dismiss="modal" aria-label="إغلاق"></button>
                </div>
                <div class="modal-body space-y-5">
                    <template x-if="!executing && step === 1">
                        <div class="space-y-4">
                            <p class="text-sm text-gray-600 leading-relaxed">
                                ارفع ملفاً يحتوي في <strong>الصف الأول</strong> على أسماء الأعمدة. عند تطابق العناوين مع استيراد سابق تُنفَّذ العملية مباشرة دون شاشة الربط. وإلا فبعد المعاينة تختار أي عمود يمثل <strong>رقم البصمة</strong> وأي عمود يمثل <strong>التاريخ والوقت</strong>.
                            </p>
                            <div class="flex flex-wrap items-center gap-2">
                                <a href="{{ route('hr.attendance.import.template') }}"
                                   class="inline-flex items-center gap-2 rounded-lg border border-indigo-200 bg-indigo-50 px-4 py-2.5 text-sm font-semibold text-indigo-800 hover:bg-indigo-100">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a2 2 0 002 2h12a2 2 0 002-2v-1m-4-4l-4 4m0 0l-4-4m4 4V4" /></svg>
                                    تحميل نموذج Excel
                                </a>
                                <span class="text-xs text-gray-500"><x-info field="hr.attendance_import_template_download" /></span>
                            </div>
                            <div>
                                <label class="mb-1.5 block text-sm font-semibold text-gray-800">ملف البيانات <x-info field="hr.attendance_import_file" /></label>
                                <input type="file" x-ref="importFile" accept=".xlsx,.xls,.csv,.txt" class="block w-full text-sm text-gray-700 file:me-4 file:rounded-lg file:border-0 file:bg-indigo-50 file:px-4 file:py-2 file:font-medium file:text-indigo-700 hover:file:bg-indigo-100">
                                <p class="mt-1.5 text-xs text-gray-500">حد أقصى 12 ميجابايت.</p>
                            </div>
                        </div>
                    </template>

                    <template x-if="executing">
                        <div class="space-y-4 py-2">
                            <p class="text-sm font-medium text-gray-800">جاري استيراد البيانات…</p>
                            <div class="progress" style="height: 12px;" role="progressbar" aria-valuemin="0" aria-valuemax="100">
                                <div class="progress-bar progress-bar-striped progress-bar-animated w-100 bg-indigo-600"></div>
                            </div>
                            <p class="text-xs text-gray-500">يُرجى عدم إغلاق النافذة حتى الانتهاء.</p>
                        </div>
                    </template>

                    <template x-if="!executing && step === 2">
                        <div class="space-y-4">
                            <p class="text-sm text-gray-600">معاينة أول 5 صفوف بيانات (بعد صف العناوين). اختر العمودين ثم نفّذ الاستيراد.</p>
                            <div class="overflow-x-auto rounded-lg border border-gray-200">
                                <table class="w-full min-w-[28rem] table-auto whitespace-nowrap text-right text-xs text-gray-800">
                                    <thead class="bg-gray-50 text-gray-600">
                                        <tr>
                                            <template x-for="(h, idx) in headers" :key="'h'+idx">
                                                <th class="px-3 py-2 font-semibold" x-text="h || '—'"></th>
                                            </template>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-100">
                                        <template x-for="(row, ri) in preview" :key="'r'+ri">
                                            <tr>
                                                <template x-for="(h, ci) in headers" :key="'c'+ri+'_'+ci">
                                                    <td class="px-3 py-2 tabular-nums" x-text="(row[ci] !== null && row[ci] !== undefined && row[ci] !== '') ? row[ci] : '—'"></td>
                                                </template>
                                            </tr>
                                        </template>
                                    </tbody>
                                </table>
                            </div>
                            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                <div>
                                    <label class="mb-1.5 block text-sm font-semibold text-gray-800">عمود كود الموظف (البصمة) <x-info field="hr.attendance_import_device_col" /></label>
                                    <select x-model="deviceIdx" class="w-full rounded-lg border border-gray-200 bg-white px-3 py-2.5 text-sm">
                                        <template x-for="(h, i) in headers" :key="'d'+i">
                                            <option :value="String(i)" x-text="'#' + (i+1) + ' — ' + (h || '(فارغ)')"></option>
                                        </template>
                                    </select>
                                </div>
                                <div>
                                    <label class="mb-1.5 block text-sm font-semibold text-gray-800">عمود التاريخ والوقت <x-info field="hr.attendance_import_datetime_col" /></label>
                                    <select x-model="datetimeIdx" class="w-full rounded-lg border border-gray-200 bg-white px-3 py-2.5 text-sm">
                                        <template x-for="(h, i) in headers" :key="'t'+i">
                                            <option :value="String(i)" x-text="'#' + (i+1) + ' — ' + (h || '(فارغ)')"></option>
                                        </template>
                                    </select>
                                </div>
                            </div>
                            <div>
                                <label class="mb-1.5 block text-sm font-semibold text-gray-800">اسم مرجعي للتنسيق (اختياري) <x-info field="hr.attendance_import_mapping_name" /></label>
                                <input type="text" x-model="mappingName" maxlength="120" class="w-full rounded-lg border border-gray-200 bg-white px-3 py-2.5 text-sm" placeholder="مثال: تصدير جهاز البصمة">
                            </div>
                        </div>
                    </template>

                    <template x-if="!executing && step === 3">
                        <div class="space-y-3">
                            <p class="text-sm font-semibold text-emerald-800" x-text="resultMessage"></p>
                            <p class="text-sm text-gray-600">فاشل: <span class="font-semibold text-red-700" x-text="resultFailedCount"></span></p>
                            <div class="max-h-48 overflow-y-auto rounded border border-gray-100 bg-gray-50 text-xs" x-show="resultFailedCount > 0">
                                <table class="w-full table-auto text-right">
                                    <thead class="sticky top-0 bg-gray-100 text-gray-600">
                                        <tr>
                                            <th class="px-3 py-2 font-semibold">الصف</th>
                                            <th class="px-3 py-2 font-semibold">السبب</th>
                                            <th class="px-3 py-2 font-semibold">التفاصيل</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-200">
                                        <template x-for="(f, fi) in resultFailed" :key="'f'+fi">
                                            <tr>
                                                <td class="px-3 py-2 tabular-nums" x-text="f.row ?? '—'"></td>
                                                <td class="px-3 py-2" x-text="f.reason ?? '—'"></td>
                                                <td class="px-3 py-2" x-text="f.detail ?? '—'"></td>
                                            </tr>
                                        </template>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </template>

                    <div x-show="errorMsg" x-cloak class="rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-800" x-text="errorMsg"></div>
                    <div x-show="previewLoading" x-cloak class="text-center text-sm text-gray-500">جاري قراءة الملف…</div>
                </div>
                <div class="modal-footer border-t border-gray-200 flex flex-wrap items-center justify-between gap-2">
                    <template x-if="!executing && step === 1">
                        <div class="flex w-full justify-between gap-2">
                            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">إلغاء</button>
                            <button type="button" class="btn btn-primary" :disabled="previewLoading" @click="runPreview()">متابعة ومعاينة</button>
                        </div>
                    </template>
                    <template x-if="!executing && step === 2">
                        <div class="flex w-full justify-between gap-2">
                            <button type="button" class="btn btn-outline-secondary" :disabled="executing" @click="step = 1">رجوع</button>
                            <button type="button" class="btn btn-primary" :disabled="executing" @click="runExecute()">تنفيذ الاستيراد</button>
                        </div>
                    </template>
                    <template x-if="!executing && step === 3">
                        <div class="flex w-full justify-end gap-2">
                            <button type="button" class="btn btn-primary" @click="reloadPage()">إغلاق وتحديث الصفحة</button>
                        </div>
                    </template>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
function attendanceImportFlow() {
    return {
        step: 1,
        previewLoading: false,
        executing: false,
        errorMsg: '',
        token: null,
        headers: [],
        preview: [],
        deviceIdx: '0',
        datetimeIdx: '1',
        mappingName: '',
        resultMessage: '',
        resultFailed: [],
        resultFailedCount: 0,
        previewUrl: @json(route('hr.attendance.import.preview')),
        executeUrl: @json(route('hr.attendance.import.execute')),
        csrf: document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
        reset() {
            this.step = 1;
            this.previewLoading = false;
            this.executing = false;
            this.errorMsg = '';
            this.token = null;
            this.headers = [];
            this.preview = [];
            this.deviceIdx = '0';
            this.datetimeIdx = '1';
            this.mappingName = '';
            this.resultMessage = '';
            this.resultFailed = [];
            this.resultFailedCount = 0;
            if (this.$refs.importFile) this.$refs.importFile.value = '';
        },
        reloadPage() {
            window.location.href = @json(route('hr.attendance'));
        },
        async runPreview() {
            this.errorMsg = '';
            const input = this.$refs.importFile;
            if (!input || !input.files || !input.files.length) {
                this.errorMsg = 'يرجى اختيار ملف.';
                return;
            }
            this.previewLoading = true;
            const fd = new FormData();
            fd.append('file', input.files[0]);
            fd.append('_token', this.csrf);
            try {
                const res = await fetch(this.previewUrl, {
                    method: 'POST',
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                    body: fd,
                    credentials: 'same-origin',
                });
                const data = await res.json().catch(() => ({}));
                if (!res.ok) {
                    this.errorMsg = data.message || 'تعذّر إنشاء المعاينة.';
                    return;
                }
                this.token = data.token;
                this.headers = data.headers || [];
                this.preview = data.preview || [];
                const sm = data.saved_mapping;
                const known = data.known_header_signature === true;
                if (sm && typeof sm.device_column_index === 'number') {
                    this.deviceIdx = String(sm.device_column_index);
                    this.datetimeIdx = String(sm.datetime_column_index ?? 0);
                    this.mappingName = sm.name || '';
                } else {
                    this.deviceIdx = this.headers.length ? '0' : '0';
                    this.datetimeIdx = this.headers.length > 1 ? '1' : '0';
                }
                if (known && sm && typeof sm.device_column_index === 'number' && this.deviceIdx !== this.datetimeIdx) {
                    await this.runExecute(true);
                } else {
                    this.step = 2;
                }
            } catch (e) {
                this.errorMsg = 'خطأ في الاتصال. حاول مرة أخرى.';
            } finally {
                this.previewLoading = false;
            }
        },
        async runExecute(fromAuto = false) {
            this.errorMsg = '';
            if (this.deviceIdx === this.datetimeIdx) {
                this.errorMsg = 'يجب اختيار عمودين مختلفين.';
                return;
            }
            if (!this.token) {
                this.errorMsg = 'انتهت الجلسة. أعد رفع الملف.';
                return;
            }
            this.executing = true;
            const fd = new FormData();
            fd.append('_token', this.csrf);
            fd.append('token', this.token);
            fd.append('device_column_index', this.deviceIdx);
            fd.append('datetime_column_index', this.datetimeIdx);
            fd.append('mapping_name', this.mappingName || '');
            try {
                const res = await fetch(this.executeUrl, {
                    method: 'POST',
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                    body: fd,
                    credentials: 'same-origin',
                });
                const data = await res.json().catch(() => ({}));
                if (!res.ok || !data.success) {
                    this.errorMsg = data.message || 'فشل الاستيراد.';
                    if (fromAuto) {
                        this.step = 2;
                    }
                    return;
                }
                this.resultMessage = data.message || 'تم الاستيراد.';
                this.resultFailed = (data.report && data.report.failed) ? data.report.failed : [];
                this.resultFailedCount = this.resultFailed.length;
                this.step = 3;
            } catch (e) {
                this.errorMsg = 'خطأ في الاتصال. حاول مرة أخرى.';
                if (fromAuto) {
                    this.step = 2;
                }
            } finally {
                this.executing = false;
            }
        },
    };
}
document.addEventListener('DOMContentLoaded', function () {
    @if($openImport)
    var el = document.getElementById('attendanceImportModal');
    if (el && window.bootstrap && bootstrap.Modal) {
        bootstrap.Modal.getOrCreateInstance(el).show();
    }
    @endif
});
</script>
@endpush
@endsection
