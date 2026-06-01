@extends('layouts.app')

@section('title', 'عرض بيانات الموظف - '.config('app.name'))

@section('breadcrumb')
    <a href="{{ route('dashboard') }}" class="text-gray-500 hover:text-indigo-600">الرئيسية</a>
    <span>›</span>
    <a href="{{ route('hr.dashboard') }}" class="text-gray-500 hover:text-indigo-600">الموارد البشرية</a>
    <span>›</span>
    <a href="{{ route('hr.employees.index') }}" class="text-gray-500 hover:text-indigo-600">الموظفون</a>
    <span>›</span>
    <span class="text-indigo-900 font-semibold truncate">{{ $employee->name }}</span>
@endsection

@section('content')
@php
    $hire = $employee->hire_date ?? $employee->hired_at;
    $st = $employee->status ?? 'active';
    $salaryTypeKey = $employee->salary_type ?? 'monthly';
    $salaryTypeLabel = match ($salaryTypeKey) {
        'weekly' => 'أسبوعي',
        'daily' => 'يومي',
        default => 'شهري',
    };
    $policyKey = $employee->attendance_policy ?? 'none';
    $attendancePolicyLabel = match ($policyKey) {
        'day_for_day' => 'خصم اليوم بيوم',
        'hour_for_hour' => 'الساعة بساعة',
        default => 'بدون سياسة خصم تلقائية',
    };
@endphp
<div class="max-w-full space-y-6" dir="rtl">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">بيانات الموظف</h1>
            <p class="mt-1 text-sm text-gray-500">{{ $employee->name }} — <span class="font-mono text-gray-700">{{ $employee->code ?: '—' }}</span></p>
        </div>
        <div class="flex flex-wrap items-center justify-end gap-2">
            <a href="{{ route('hr.employees.index') }}" class="inline-flex shrink-0 items-center justify-center rounded-lg border border-gray-200 bg-white px-4 py-2.5 text-sm font-semibold text-gray-800 shadow-sm transition hover:bg-gray-50">
                رجوع للقائمة
            </a>
            @if($latestPaySlip && $latestPaySlip->payrollCycle)
                <a href="{{ route('hr.payroll-slips.payslip', ['payroll' => $latestPaySlip->payroll_cycle_id, 'slip' => $latestPaySlip->id]) }}"
                   target="_blank"
                   rel="noopener"
                   class="inline-flex shrink-0 items-center justify-center gap-2 rounded-lg border border-teal-200 bg-teal-50 px-4 py-2.5 text-sm font-semibold text-teal-900 shadow-sm hover:bg-teal-100">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16" aria-hidden="true"><path d="M0 3.5A1.5 1.5 0 0 1 1.5 2h13A1.5 1.5 0 0 1 16 3.5v9A1.5 1.5 0 0 1 14.5 14h-13A1.5 1.5 0 0 1 0 12.5zM1 4v8.5A.5.5 0 0 0 1.5 13h13a.5.5 0 0 0 .5-.5V4z"/></svg>
                    آخر قسيمة راتب
                </a>
            @endif
            <a href="{{ route('hr.employees.edit', $employee) }}" class="inline-flex shrink-0 items-center justify-center gap-2 rounded-lg bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-blue-700">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16" aria-hidden="true"><path d="M12.146.146a.5.5 0 0 1 .708 0l3 3a.5.5 0 0 1 0 .708l-9.5 9.5a.5.5 0 0 1-.168.11l-5 2a.5.5 0 0 1-.65-.65l2-5a.5.5 0 0 1 .11-.168zM11.207 2L3 10.207V12h1.793L13 3.793z"/></svg>
                تعديل البيانات
            </a>
        </div>
    </div>

    <div x-data="{ tab: 'personal' }" class="space-y-6">
        <nav class="flex flex-wrap gap-2 rounded-lg border border-gray-200 bg-white p-2 shadow-sm" aria-label="أقسام ملف الموظف">
            <button type="button" @click="tab = 'personal'" :class="tab === 'personal' ? 'bg-indigo-600 text-white shadow-sm' : 'bg-gray-50 text-gray-700 hover:bg-gray-100'" class="rounded-lg px-4 py-2.5 text-sm font-semibold transition">البيانات الشخصية والتواصل</button>
            <button type="button" @click="tab = 'job'" :class="tab === 'job' ? 'bg-indigo-600 text-white shadow-sm' : 'bg-gray-50 text-gray-700 hover:bg-gray-100'" class="rounded-lg px-4 py-2.5 text-sm font-semibold transition">الوظيفة</button>
            <button type="button" @click="tab = 'finance'" :class="tab === 'finance' ? 'bg-indigo-600 text-white shadow-sm' : 'bg-gray-50 text-gray-700 hover:bg-gray-100'" class="rounded-lg px-4 py-2.5 text-sm font-semibold transition">المالية والبنك</button>
            <button type="button" @click="tab = 'attendance'" :class="tab === 'attendance' ? 'bg-indigo-600 text-white shadow-sm' : 'bg-gray-50 text-gray-700 hover:bg-gray-100'" class="rounded-lg px-4 py-2.5 text-sm font-semibold transition">سجل الحضور</button>
            <button type="button" @click="tab = 'files'" :class="tab === 'files' ? 'bg-indigo-600 text-white shadow-sm' : 'bg-gray-50 text-gray-700 hover:bg-gray-100'" class="rounded-lg px-4 py-2.5 text-sm font-semibold transition">المرفقات</button>
        </nav>

        <div x-show="tab === 'personal'" class="space-y-6">
    <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
        <section class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm">
            <h2 class="mb-4 border-b border-gray-100 pb-3 text-lg font-bold text-gray-900">المعلومات الشخصية <x-info field="hr.employee_view_basic" /></h2>
            <dl class="space-y-3 text-sm text-gray-800">
                <div class="flex justify-between gap-4"><dt class="text-gray-500">كود الموظف</dt><dd class="font-mono">{{ $employee->code ?: '—' }}</dd></div>
                <div class="flex justify-between gap-4"><dt class="text-gray-500">كود البصمة (نفس كود الموظف) <x-info field="hr.employee_attendance_device_id" /></dt><dd class="font-mono">{{ $employee->attendance_device_id ?: '—' }}</dd></div>
                <div class="flex justify-between gap-4"><dt class="text-gray-500">الاسم الأول</dt><dd>{{ $employee->first_name ?: '—' }}</dd></div>
                <div class="flex justify-between gap-4"><dt class="text-gray-500">الاسم الأوسط</dt><dd>{{ $employee->middle_name ?: '—' }}</dd></div>
                <div class="flex justify-between gap-4"><dt class="text-gray-500">اسم العائلة</dt><dd>{{ $employee->last_name ?: '—' }}</dd></div>
                <div class="flex justify-between gap-4"><dt class="text-gray-500">الاسم الكامل</dt><dd class="font-semibold text-gray-900">{{ $employee->name ?: '—' }}</dd></div>
                <div class="flex justify-between gap-4"><dt class="text-gray-500">الجنس</dt><dd>@if($employee->gender === 'male')ذكر@elseif($employee->gender === 'female')أنثى@elseif($employee->gender === 'other')آخر@else—@endif</dd></div>
                <div class="flex justify-between gap-4"><dt class="text-gray-500">تاريخ الميلاد</dt><dd class="tabular-nums">{{ optional($employee->birth_date)->format('Y-m-d') ?: '—' }}</dd></div>
                <div class="flex justify-between gap-4"><dt class="text-gray-500">الحالة الاجتماعية</dt><dd>@switch($employee->marital_status)@case('single')أعزب@break @case('married')متزوج@break @case('divorced')مطلق@break @case('widowed')أرمل@break @default — @endswitch</dd></div>
                <div class="flex justify-between gap-4"><dt class="text-gray-500">الجنسية</dt><dd>{{ $employee->nationality ?: '—' }}</dd></div>
                <div class="flex justify-between gap-4"><dt class="text-gray-500">رقم الهوية</dt><dd>{{ $employee->id_number ?: '—' }}</dd></div>
                <div class="flex justify-between gap-4"><dt class="text-gray-500">رقم جواز السفر</dt><dd>{{ $employee->passport_number ?: '—' }}</dd></div>
            </dl>
        </section>

        <section class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm">
            <h2 class="mb-4 border-b border-gray-100 pb-3 text-lg font-bold text-gray-900">الاتصال والعنوان</h2>
            <dl class="space-y-3 text-sm text-gray-800">
                <div class="flex justify-between gap-4"><dt class="text-gray-500">البريد الوظيفي</dt><dd class="break-all">{{ $employee->email ?: '—' }}</dd></div>
                <div class="flex justify-between gap-4"><dt class="text-gray-500">البريد الشخصي</dt><dd class="break-all">{{ $employee->personal_email ?: '—' }}</dd></div>
                <div class="flex justify-between gap-4"><dt class="text-gray-500">الجوال</dt><dd>{{ $employee->mobile ?: '—' }}</dd></div>
                <div class="flex justify-between gap-4"><dt class="text-gray-500">الهاتف</dt><dd>{{ $employee->phone ?: '—' }}</dd></div>
                <div class="flex justify-between gap-4"><dt class="text-gray-500">العنوان</dt><dd>{{ $employee->address ?: '—' }}</dd></div>
                <div class="flex justify-between gap-4"><dt class="text-gray-500">المدينة</dt><dd>{{ $employee->city ?: '—' }}</dd></div>
                <div class="flex justify-between gap-4"><dt class="text-gray-500">المنطقة</dt><dd>{{ $employee->region ?: '—' }}</dd></div>
                <div class="flex justify-between gap-4"><dt class="text-gray-500">الرمز البريدي</dt><dd>{{ $employee->postal_code ?: '—' }}</dd></div>
                <div class="flex justify-between gap-4"><dt class="text-gray-500">الدولة</dt><dd>{{ $employee->country ?: '—' }}</dd></div>
                <div class="flex justify-between gap-4"><dt class="text-gray-500">اسم جهة الطوارئ</dt><dd>{{ $employee->emergency_contact_name ?: '—' }}</dd></div>
                <div class="flex justify-between gap-4"><dt class="text-gray-500">هاتف جهة الطوارئ</dt><dd>{{ $employee->emergency_contact_phone ?: '—' }}</dd></div>
                <div class="flex justify-between gap-4"><dt class="text-gray-500">صلة القرابة</dt><dd>{{ $employee->emergency_contact_relation ?: '—' }}</dd></div>
            </dl>
        </section>
    </div>
        </div>

        <div x-show="tab === 'job'" x-cloak class="space-y-6">
        <section class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm lg:max-w-3xl">
            <h2 class="mb-4 border-b border-gray-100 pb-3 text-lg font-bold text-gray-900">العمل والربط <x-info field="hr.employee_view_job" /></h2>
            <dl class="space-y-3 text-sm text-gray-800">
                <div class="flex justify-between gap-4"><dt class="text-gray-500">نوع التوظيف</dt><dd>@switch($employee->employment_type)@case('full_time')دوام كامل@break @case('part_time')دوام جزئي@break @case('contract')عقد@break @case('temporary')مؤقت@break @case('intern')متدرب@break @default — @endswitch</dd></div>
                <div class="flex justify-between gap-4"><dt class="text-gray-500">القسم</dt><dd>{{ $employee->department?->name ?? $employee->department ?? '—' }}</dd></div>
                <div class="flex justify-between gap-4"><dt class="text-gray-500">المسمى الوظيفي</dt><dd>{{ $employee->position ?? $employee->job_title ?? '—' }}</dd></div>
                <div class="flex justify-between gap-4"><dt class="text-gray-500">الحالة</dt><dd>@if($st === 'active') نشط @elseif($st === 'on_leave') في إجازة @else غير نشط @endif</dd></div>
                <div class="flex justify-between gap-4"><dt class="text-gray-500">تاريخ التعيين</dt><dd class="tabular-nums">{{ $hire?->format('Y-m-d') ?? '—' }}</dd></div>
                <div class="flex justify-between gap-4"><dt class="text-gray-500">المستخدم المرتبط</dt><dd>{{ $employee->linkedUser?->email ?: '—' }}</dd></div>
                <div class="flex justify-between gap-4"><dt class="text-gray-500">الصلاحية</dt><dd>{{ $employee->linkedUser ? \App\Support\ErpRoles::roleLabelAr($employee->linkedUser->role) : '—' }}</dd></div>
            </dl>
        </section>
        </div>

        <div x-show="tab === 'finance'" x-cloak class="space-y-6">
        <section class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm">
            <h2 class="mb-4 border-b border-gray-100 pb-3 text-lg font-bold text-gray-900">الراتب والتعويضات والسياسات <x-info field="hr.employee_profile_finance_salary" /></h2>
            <dl class="space-y-3 text-sm text-gray-800">
                <div class="flex justify-between gap-4"><dt class="text-gray-500">الراتب الأساسي <x-info field="hr.employee_basic_salary" /></dt><dd class="tabular-nums font-medium text-gray-900">{{ $employee->base_salary !== null ? number_format((float) $employee->base_salary, 2) : '—' }}</dd></div>
                <div class="flex justify-between gap-4"><dt class="text-gray-500">نوع الراتب <x-info field="hr.employee_salary_type" /></dt><dd>{{ $salaryTypeLabel }}</dd></div>
                <div class="flex justify-between gap-4"><dt class="text-gray-500">سياسة الحضور (الخصم) <x-info field="hr.employee_attendance_policy" /></dt><dd>{{ $attendancePolicyLabel }}</dd></div>
                <div class="flex justify-between gap-4"><dt class="text-gray-500">خصم تأمينات ثابت <x-info field="hr.employee_fixed_insurance_deduction" /></dt><dd class="tabular-nums">{{ $employee->fixed_insurance_deduction !== null ? number_format((float) $employee->fixed_insurance_deduction, 2) : '—' }}</dd></div>
                <div class="flex justify-between gap-4"><dt class="text-gray-500">خصم ضريبة ثابت <x-info field="hr.employee_fixed_tax_deduction" /></dt><dd class="tabular-nums">{{ $employee->fixed_tax_deduction !== null ? number_format((float) $employee->fixed_tax_deduction, 2) : '—' }}</dd></div>
                <div class="flex justify-between gap-4"><dt class="text-gray-500">بدل سكن</dt><dd class="tabular-nums">{{ $employee->housing_allowance !== null ? number_format((float) $employee->housing_allowance, 2) : '—' }}</dd></div>
                <div class="flex justify-between gap-4"><dt class="text-gray-500">بدل مواصلات</dt><dd class="tabular-nums">{{ $employee->transport_allowance !== null ? number_format((float) $employee->transport_allowance, 2) : '—' }}</dd></div>
                <div class="flex justify-between gap-4"><dt class="text-gray-500">بدلات أخرى</dt><dd class="tabular-nums">{{ $employee->other_allowance !== null ? number_format((float) $employee->other_allowance, 2) : '—' }}</dd></div>
                <div class="flex justify-between gap-4"><dt class="text-gray-500">مركز التكلفة <x-info field="hr.employee_cost_center" /></dt><dd>@if($employee->costCenter){{ $employee->costCenter->code ? $employee->costCenter->code.' — ' : '' }}{{ $employee->costCenter->name }}@else—@endif</dd></div>
                <div class="flex justify-between gap-4"><dt class="text-gray-500">حساب الأجور <x-info field="hr.employee_wage_account" /></dt><dd>@if($employee->ledgerAccount){{ $employee->ledgerAccount->code }} — {{ $employee->ledgerAccount->name_ar }}@else—@endif</dd></div>
            </dl>
        </section>
        <section class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm">
            <h2 class="mb-4 border-b border-gray-100 pb-3 text-lg font-bold text-gray-900">البنك والمعلومات الحكومية <x-info field="hr.employee_profile_finance_bank" /></h2>
            <dl class="space-y-3 text-sm text-gray-800">
                <div class="flex justify-between gap-4"><dt class="text-gray-500">اسم البنك</dt><dd>{{ $employee->bank_name ?: '—' }}</dd></div>
                <div class="flex justify-between gap-4"><dt class="text-gray-500">رقم الحساب</dt><dd>{{ $employee->bank_account_number ?: '—' }}</dd></div>
                <div class="flex justify-between gap-4"><dt class="text-gray-500">الآيبان</dt><dd>{{ $employee->iban ?: '—' }}</dd></div>
                <div class="flex justify-between gap-4"><dt class="text-gray-500">رقم التأمينات الاجتماعية</dt><dd>{{ $employee->social_insurance_number ?: '—' }}</dd></div>
                <div class="flex justify-between gap-4"><dt class="text-gray-500">الرقم الضريبي</dt><dd>{{ $employee->tax_number ?: '—' }}</dd></div>
                <div class="flex justify-between gap-4"><dt class="text-gray-500">رقم التأمين الصحي</dt><dd>{{ $employee->insurance_number ?: '—' }}</dd></div>
                <div class="flex justify-between gap-4"><dt class="text-gray-500">ملاحظات</dt><dd class="max-w-md text-left sm:text-right">{{ $employee->notes ?: '—' }}</dd></div>
            </dl>
        </section>
        </div>

        <div x-show="tab === 'attendance'" x-cloak class="space-y-6">
    <section class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm">
        <h2 class="mb-4 border-b border-gray-100 pb-3 text-lg font-bold text-gray-900">سجل الحضور التفصيلي</h2>
        <div class="overflow-x-auto">
            <table class="w-full table-auto whitespace-nowrap text-right text-sm text-gray-800">
                <thead class="bg-gray-50 text-gray-600">
                    <tr class="border-b border-gray-200">
                        <th class="px-4 py-3 text-right font-semibold">التاريخ <x-info field="hr.employee_show_attendance_col_date" /></th>
                        <th class="px-4 py-3 text-right font-semibold">الحضور <x-info field="hr.employee_show_attendance_col_checkin" /></th>
                        <th class="px-4 py-3 text-right font-semibold">الانصراف <x-info field="hr.employee_show_attendance_col_checkout" /></th>
                        <th class="px-4 py-3 text-right font-semibold">ساعات العمل <x-info field="hr.employee_show_attendance_col_hours" /></th>
                        <th class="px-4 py-3 text-right font-semibold">دقائق التأخير <x-info field="hr.employee_show_attendance_col_minutes_late" /></th>
                        <th class="px-4 py-3 text-right font-semibold">قيمة الخصم <x-info field="hr.employee_show_attendance_col_deduction" /></th>
                        <th class="px-4 py-3 text-right font-semibold">الحالة <x-info field="hr.employee_show_attendance_col_status" /></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 bg-white">
                    @foreach($attendanceHistory as $att)
                        <tr>
                            <td class="px-4 py-3 tabular-nums">{{ $att['date'] }}</td>
                            <td class="px-4 py-3 tabular-nums">{{ $att['check_in'] }}</td>
                            <td class="px-4 py-3 tabular-nums">{{ $att['check_out'] }}</td>
                            <td class="px-4 py-3 tabular-nums">{{ $att['work_hours'] }}</td>
                            <td class="px-4 py-3 tabular-nums">{{ $att['minutes_late_display'] }}</td>
                            <td class="px-4 py-3 tabular-nums">{{ $att['deduction_display'] }}</td>
                            <td class="px-4 py-3">
                                @if($att['status_key'] === 'present')
                                    <span class="inline-flex items-center rounded-full bg-emerald-100 px-2.5 py-0.5 text-xs font-semibold text-emerald-800">حاضر</span>
                                @elseif($att['status_key'] === 'late')
                                    <span class="inline-flex items-center rounded-full bg-amber-100 px-2.5 py-0.5 text-xs font-semibold text-amber-800">متأخر</span>
                                @elseif($att['status_key'] === 'leave')
                                    <span class="inline-flex items-center rounded-full bg-blue-100 px-2.5 py-0.5 text-xs font-semibold text-blue-800">إجازة مدفوعة</span>
                                @else
                                    <span class="inline-flex items-center rounded-full bg-red-100 px-2.5 py-0.5 text-xs font-semibold text-red-800">غائب</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </section>
        </div>

        <div x-show="tab === 'files'" x-cloak class="space-y-6">
    <section class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm">
        <x-attachment-handler
            hint-field="hr.employee_view_attachments"
            title="المرفقات وأوراق التعيين"
            :existing="$employee->attachments"
            :show-existing="true"
            :uploadable="false"
            :allow-delete="true"
            help-text="المستندات المرفوعة من شاشة إضافة/تعديل الموظف."
        />
    </section>
        </div>
    </div>
</div>
@endsection
