@extends('layouts.app')

@section('title', 'دورة رواتب - MIRADA ERP')

@section('breadcrumb')
    <a href="{{ route('dashboard') }}" class="text-gray-500 hover:text-indigo-600">الرئيسية</a>
    <span>›</span>
    <a href="{{ route('hr.dashboard') }}" class="text-gray-500 hover:text-indigo-600">الموارد البشرية</a>
    <span>›</span>
    <a href="{{ route('hr.payrolls.index') }}" class="text-gray-500 hover:text-indigo-600">الرواتب</a>
    <span>›</span>
    <span class="text-indigo-900 font-semibold truncate">{{ $payroll->periodLabelAr() }}</span>
@endsection

@section('content')
<style>
    #modal_payment_account_id-listbox {
        z-index: 9999 !important;
    }
</style>
@php
    $statusAr = match ($payroll->status) {
        'approved' => 'معتمد',
        'paid' => 'مدفوع',
        default => 'مسودة',
    };
    $canPay = $payroll->status === \App\Models\Payroll::STATUS_APPROVED
        && ! $payroll->payment_journal_entry_id;
    $hasPayableAmount = (float) $payroll->total_amount > 0.0001;
    $paymentOptionsForModal = $paymentAccountOptions ?? [];
    $defaultPaymentValueForModal = old('payment_account_id', $defaultPaymentAccountId);
    $modalPreviewAmount = (float) $payroll->total_amount;
    $modalOpenDefault = $errors->has('payment_account_id') || $errors->has('payment_date');
@endphp
<div class="max-w-full space-y-6" dir="rtl" x-data="{ payModalOpen: @json($modalOpenDefault) }">
    @if($payroll->status === \App\Models\Payroll::STATUS_DRAFT && ! ($accountingLinksReady ?? true))
        <div class="rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-950">
            <p class="font-semibold">الربط المحاسبي غير مكتمل</p>
            <p class="mt-1 text-amber-900/90">لن يُسمح باعتماد الدورة ذات الصافي الأكبر من صفر حتى يُحفظ <span class="font-medium">مصروف الأجور</span> و<span class="font-medium">الأجور المستحقة</span> في
                <a href="{{ route('settings.company.edit') }}#payroll-accounts" class="font-semibold text-indigo-700 underline hover:text-indigo-900">إعدادات المنشأة — الرواتب</a>.
            </p>
        </div>
    @endif
    <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">
                @if(filled($payroll->name))
                    <span class="block text-lg font-semibold text-gray-800">{{ $payroll->name }}</span>
                @endif
                <span class="text-gray-600">دورة #{{ $payroll->id }}</span> — {{ $payroll->periodLabelAr() }}
                @if($payroll->department)
                    <span class="mt-1 block text-sm font-normal text-gray-500">القسم: {{ $payroll->department->name }}</span>
                @endif
            </h1>
            <p class="mt-1 text-sm text-gray-500">
                الحالة: <span class="font-semibold text-gray-800">{{ $statusAr }}</span>
                — إجمالي الراتب: <span class="font-mono tabular-nums">{{ number_format((float) $payroll->total_gross, 2) }}</span>
                — الخصومات: <span class="font-mono tabular-nums text-amber-900">{{ number_format((float) $payroll->total_deductions, 2) }}</span>
                — الصافي: <span class="font-mono tabular-nums font-semibold text-gray-900">{{ number_format((float) $payroll->total_amount, 2) }}</span>
                <x-info field="hr.payroll_show_summary" />
            </p>
            @if($payroll->notes)
                <p class="mt-2 text-sm text-gray-600">{{ $payroll->notes }}</p>
            @endif
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <a href="{{ route('hr.payrolls.index') }}" class="inline-flex items-center rounded-lg border border-gray-200 bg-white px-4 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50">قائمة الدورات</a>
            @if($payroll->status === \App\Models\Payroll::STATUS_DRAFT)
                <form method="post" action="{{ route('hr.payrolls.approve', $payroll) }}" onsubmit="return confirm('تأكيد اعتماد هذه الدورة؟ سيتم إثبات الاستحقاق محاسبياً (مصروف أجور / أجور مستحقة) إن كان الصافي أكبر من صفر.');">
                    @csrf
                    <button type="submit" class="inline-flex items-center justify-center gap-2 rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-indigo-700">
                        اعتماد الدورة
                    </button>
                </form>
            @endif
            @if($canPay)
                <button type="button" @click="payModalOpen = true" class="inline-flex items-center justify-center gap-2 rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-indigo-700">
                    دفع الرواتب
                </button>
            @endif
        </div>
    </div>

    @if($payroll->accrual_journal_entry_id || $payroll->payment_journal_entry_id)
    <div class="rounded-lg border border-gray-200 bg-slate-50/90 p-4 text-sm text-gray-800">
        <p class="mb-2 font-semibold">المسار المحاسبي <x-info field="hr.payroll_accounting_trail" /></p>
        <ul class="flex flex-wrap gap-4 gap-y-1">
            @if($payroll->accrual_journal_entry_id)
                <li>
                    قيد الاستحقاق:
                    <a href="{{ route('finance.journals.edit', $payroll->accrualJournalEntry) }}" class="font-mono text-indigo-600 hover:text-indigo-800">#{{ $payroll->accrual_journal_entry_id }}</a>
                </li>
            @endif
            @if($payroll->payment_journal_entry_id)
                <li>
                    قيد الدفع:
                    <a href="{{ route('finance.journals.edit', $payroll->paymentJournalEntry) }}" class="font-mono text-indigo-600 hover:text-indigo-800">#{{ $payroll->payment_journal_entry_id }}</a>
                </li>
            @endif
        </ul>
    </div>
    @endif

    @if (session('success'))
        <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-900">{{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">{{ session('error') }}</div>
    @endif

    <div class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="w-full min-w-[56rem] table-auto text-right text-sm text-gray-800">
                <thead class="bg-gray-50 text-gray-600">
                    <tr class="border-b border-gray-200">
                        <th class="px-4 py-3 font-semibold">الموظف <x-info field="hr.payroll_item_col_employee" /></th>
                        <th class="px-4 py-3 font-semibold">راتب أساسي <x-info field="hr.payroll_item_col_basic" /></th>
                        <th class="px-4 py-3 font-semibold">البدلات <x-info field="hr.payroll_item_col_allowances" /></th>
                        <th class="px-4 py-3 font-semibold">إضافي <x-info field="hr.payroll_item_col_overtime" /></th>
                        <th class="px-4 py-3 font-semibold">خصم حضور <x-info field="hr.payroll_item_col_attendance" /></th>
                        <th class="px-4 py-3 font-semibold">تأمينات/ضريبة <x-info field="hr.payroll_item_col_other_ded" /></th>
                        <th class="px-4 py-3 font-semibold">الصافي <x-info field="hr.payroll_item_col_net" /></th>
                        @if($payroll->status === \App\Models\Payroll::STATUS_APPROVED || $payroll->status === \App\Models\Payroll::STATUS_PAID)
                        <th class="px-4 py-3 w-20 font-semibold text-center">قسيمة <x-info field="hr.payroll_item_col_payslip" /></th>
                        @endif
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach($payroll->paySlips as $slip)
                        <tr class="hover:bg-gray-50/80">
                            <td class="px-4 py-3 font-medium text-gray-900">{{ $slip->employee?->name ?? '—' }}</td>
                            <td class="px-4 py-3 tabular-nums">{{ number_format((float) $slip->basic_salary, 2) }}</td>
                            <td class="px-4 py-3 tabular-nums">{{ number_format((float) $slip->total_allowances, 2) }}</td>
                            <td class="px-4 py-3 tabular-nums text-indigo-900">{{ number_format((float) $slip->overtime_amount, 2) }}</td>
                            <td class="px-4 py-3 tabular-nums text-amber-800">{{ number_format((float) $slip->attendance_deductions, 2) }}</td>
                            <td class="px-4 py-3 tabular-nums text-red-800">{{ number_format((float) $slip->statutory_deductions, 2) }}</td>
                            <td class="px-4 py-3 tabular-nums font-semibold text-gray-900">{{ number_format((float) $slip->net_salary, 2) }}</td>
                            @if($payroll->status === \App\Models\Payroll::STATUS_APPROVED || $payroll->status === \App\Models\Payroll::STATUS_PAID)
                            <td class="px-2 py-3 text-center">
                                <a href="{{ route('hr.payroll-slips.payslip', ['payroll' => $payroll, 'slip' => $slip]) }}?autoprint=1"
                                   target="_blank" rel="noopener noreferrer"
                                   class="inline-flex items-center justify-center rounded-lg p-1.5 text-indigo-600 hover:bg-indigo-50 hover:text-indigo-800"
                                   title="طباعة قسيمة الراتب">
                                    <span class="sr-only">طباعة قسيمة</span>
                                    <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 16 16" aria-hidden="true">
                                        <path d="M2.5 8a.5.5 0 1 0 0-1 .5.5 0 0 0 0 1z"/>
                                        <path d="M5 1a2 2 0 0 0-2 2v2H2a2 2 0 0 0-2 2v3a2 2 0 0 0 2 2h1v1a2 2 0 0 0 2 2h6a2 2 0 0 0 2-2v-1h1a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-1V3a2 2 0 0 0-2-2H5zM4 3a1 1 0 0 1 1-1h6a1 1 0 0 1 1 1v2H4V3zm1 5a2 2 0 0 0-2 2v1H2a1 1 0 0 1-1-1V7a1 1 0 0 1 1-1h12a1 1 0 0 1 1 1v3a1 1 0 0 1-1 1h-1v-1a2 2 0 0 0-2-2H5zm7 2v3a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1v-3a1 1 0 0 1 1-1h6a1 1 0 0 1 1 1z"/>
                                    </svg>
                                </a>
                            </td>
                            @endif
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    @if($canPay)
    <div
        x-show="payModalOpen"
        x-cloak
        class="fixed inset-0 z-[1050] flex items-center justify-center p-4 sm:p-6"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
    >
        <div class="absolute inset-0 bg-black/45 backdrop-blur-[2px]" @click="payModalOpen = false" aria-hidden="true"></div>
        <div class="relative z-[1060] w-full max-w-md overflow-hidden rounded-xl border border-indigo-200 bg-white shadow-2xl ring-1 ring-indigo-500/10" @click.outside="payModalOpen = false" role="dialog" aria-modal="true" aria-labelledby="payroll-pay-title">
            <div class="bg-gradient-to-l from-indigo-600 to-indigo-500 px-6 py-4 text-white">
                <h2 id="payroll-pay-title" class="text-lg font-bold">دفع الرواتب</h2>
                <p class="mt-0.5 text-sm text-indigo-100"><x-info field="hr.payroll_pay_modal_intro" /></p>
            </div>
            <div class="space-y-4 p-6">
                <div class="rounded-lg border border-indigo-100 bg-indigo-50/90 p-4">
                    <p class="text-sm text-gray-700">إجمالي الصرف</p>
                    <p class="mt-1 text-2xl font-bold text-indigo-900 tabular-nums">{{ number_format($modalPreviewAmount, 2) }}</p>
                </div>
                <form method="post" action="{{ route('hr.payrolls.pay', $payroll) }}" class="space-y-4">
                    @csrf
                    @if($hasPayableAmount)
                        <div class="block w-full">
                            <label for="modal_payment_account_id" class="mb-1.5 flex items-center gap-1 text-sm font-semibold text-gray-800">اختر حساب الصرف (الخزينة/البنك) <x-info field="hr.payroll_pay_source_account" /> <span class="text-red-600">*</span></label>
                            <x-searchable-select
                                name="payment_account_id"
                                id="modal_payment_account_id"
                                :options="$paymentOptionsForModal"
                                :value="$defaultPaymentValueForModal"
                                :required="true"
                                in-modal="true"
                                fixed-panel="true"
                                class="w-full"
                                style="z-index:9999;"
                                :error="$errors->has('payment_account_id')"
                                :emptyOption="true"
                                empty-label="— اختر الحساب —"
                                placeholder="ابحث بالرمز أو الاسم..."
                            />
                            @error('payment_account_id')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                        </div>
                    @else
                        <p class="text-sm text-amber-800">صافي الدورة صفر؛ سيتم تسجيل الحالة «مدفوع» دون قيد صرف مالي.</p>
                    @endif
                    <div>
                        <label for="payment_date" class="mb-1.5 block text-sm font-semibold text-gray-800">تاريخ التحصيل/الدفع <span class="text-red-600">*</span></label>
                        <input type="date" name="payment_date" id="payment_date" value="{{ old('payment_date', now()->format('Y-m-d')) }}" class="w-full rounded-lg border border-gray-200 bg-white px-3 py-2.5 text-sm" required>
                        @error('payment_date')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>
                    <div class="flex flex-wrap items-center justify-end gap-2 border-t border-gray-100 pt-4">
                        <button type="button" @click="payModalOpen = false" class="rounded-lg border border-gray-200 bg-white px-4 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50">إلغاء</button>
                        <button type="submit" class="rounded-lg bg-indigo-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-indigo-700">تأكيد الدفع</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @endif
</div>
@endsection
