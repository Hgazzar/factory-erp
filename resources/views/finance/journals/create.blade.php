@extends('layouts.app')

@section('title', 'إضافة قيد - MIRADA ERP')

@section('breadcrumb')
    <a href="{{ route('dashboard') }}" class="text-gray-500 hover:text-blue-600">الرئيسية</a>
    <span class="mx-1 text-gray-400">›</span>
    <a href="{{ route('finance.dashboard') }}" class="text-gray-500 hover:text-blue-600">المحاسبة</a>
    <span class="mx-1 text-gray-400">›</span>
    <a href="{{ route('finance.journals.index') }}" class="text-gray-500 hover:text-blue-600">القيود اليومية</a>
    <span class="mx-1 text-gray-400">›</span>
    <span class="text-blue-900 font-semibold">قيد جديد</span>
@endsection

@section('content')
@php
    $oldLines = old('lines');
    $linesForForm = [];
    if (is_array($oldLines) && count($oldLines) >= 2) {
        foreach ($oldLines as $line) {
            $line = is_array($line) ? $line : [];
            $linesForForm[] = [
                'account_id' => (string) ($line['account_id'] ?? ''),
                'description' => $line['description'] ?? '',
                'cost_center' => $line['cost_center'] ?? '',
                'debit' => $line['debit'] ?? '',
                'credit' => $line['credit'] ?? '',
            ];
        }
    } else {
        $linesForForm = [
            ['account_id' => '', 'description' => '', 'cost_center' => '', 'debit' => '', 'credit' => ''],
            ['account_id' => '', 'description' => '', 'cost_center' => '', 'debit' => '', 'credit' => ''],
        ];
    }
@endphp
<div dir="rtl" class="mx-auto w-full max-w-full">
    <header class="mb-6 flex flex-wrap items-center justify-between gap-4 border-b border-gray-100 pb-4">
        <div class="flex items-center gap-3">
            <a href="{{ route('finance.journals.index') }}" class="flex h-10 w-10 items-center justify-center rounded-lg border border-gray-200 bg-white text-gray-600 hover:bg-gray-50 transition" aria-label="العودة">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                </svg>
            </a>
            <div>
                <h1 class="text-2xl font-bold text-gray-900">إضافة قيد</h1>
                <p class="mt-1 text-sm text-gray-500">لن يتم حفظ القيد إلا إذا تساوى إجمالي المدين مع إجمالي الدائن.</p>
            </div>
        </div>
    </header>

    <form
        wire:ignore.self
        id="journal-entry-form"
        method="POST"
        action="{{ route('finance.journals.store') }}"
        class="space-y-6"
        novalidate
    >
            @csrf

            <section class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm">
                <h2 class="mb-4 text-base font-semibold text-gray-900">تفاصيل القيد</h2>
                <div class="grid grid-cols-1 gap-6 md:grid-cols-3">
                    <div class="space-y-1">
                        <label class="flex items-center gap-1 text-sm font-medium text-gray-700">
                            تاريخ القيد
                            <span class="text-red-500" aria-hidden="true">*</span>
                            <x-info field="journal_entry_date" />
                        </label>
                        <input
                            type="date"
                            name="date"
                            value="{{ old('date', now()->toDateString()) }}"
                            class="h-10 w-full rounded-lg border bg-gray-50 px-3 text-sm text-gray-900 focus:border-blue-500 focus:ring-2 focus:ring-blue-500 {{ $errors->has('date') ? 'border-red-500 ring-1 ring-red-200' : 'border-gray-200' }}"
                        >
                        @error('date')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="space-y-1">
                        <label class="flex items-center gap-1 text-sm font-medium text-gray-700">
                            المرجع
                            <x-info field="journal_entry_reference" />
                        </label>
                        <input
                            type="text"
                            name="reference"
                            maxlength="50"
                            value="{{ old('reference') }}"
                            placeholder="أدخل المرجع..."
                            class="h-10 w-full rounded-lg border border-gray-200 bg-gray-50 px-3 text-sm text-gray-900 focus:border-blue-500 focus:ring-2 focus:ring-blue-500"
                        >
                    </div>

                    <div class="space-y-1">
                        <label class="flex items-center gap-1 text-sm font-medium text-gray-700">
                            البيان
                            <x-info field="journal_entry_description" />
                        </label>
                        <input
                            type="text"
                            name="description"
                            value="{{ old('description') }}"
                            placeholder="البيان العام للقيد"
                            class="h-10 w-full rounded-lg border border-gray-200 bg-gray-50 px-3 text-sm text-gray-900 focus:border-blue-500 focus:ring-2 focus:ring-blue-500"
                        >
                    </div>
                </div>
            </section>

            <section class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm">
                <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
                    <h2 class="text-base font-semibold text-gray-900">بنود القيد</h2>
                    <button
                        id="add-line-button"
                        type="button"
                        class="inline-flex shrink-0 items-center gap-2 rounded-lg border-2 border-blue-600 bg-white px-4 py-2 text-sm font-semibold text-blue-700 shadow-sm transition hover:bg-blue-50 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="currentColor" viewBox="0 0 16 16" aria-hidden="true">
                            <path d="M8 4a.5.5 0 0 1 .5.5v3h3a.5.5 0 0 1 0 1h-3v3a.5.5 0 0 1-1 0v-3h-3a.5.5 0 0 1 0-1h3v-3A.5.5 0 0 1 8 4z"/>
                        </svg>
                        إضافة سطر
                    </button>
                </div>

                <div class="overflow-x-auto rounded-lg border border-gray-200">
                    <table class="min-w-full border-collapse text-sm">
                        <thead class="border-b border-gray-200 bg-gray-50 text-gray-700">
                            <tr>
                                <th class="px-3 py-3 text-right font-medium">
                                    <span class="inline-flex items-center gap-1">الحساب <span class="text-red-500" aria-hidden="true">*</span> <x-info field="journal_line_account" /></span>
                                </th>
                                <th class="px-3 py-3 text-right font-medium">
                                    <span class="inline-flex items-center gap-1">الوصف <x-info field="journal_line_description" /></span>
                                </th>
                                <th class="px-3 py-3 text-right font-medium">
                                    <span class="inline-flex items-center gap-1">مركز التكلفة <x-info field="journal_line_cost_center" /></span>
                                </th>
                                <th class="px-3 py-3 text-right font-medium">
                                    <span class="inline-flex items-center gap-1">مدين <x-info field="journal_line_debit" /></span>
                                </th>
                                <th class="px-3 py-3 text-right font-medium">
                                    <span class="inline-flex items-center gap-1">دائن <x-info field="journal_line_credit" /></span>
                                </th>
                                <th class="w-12 px-2 py-3 text-center font-medium"></th>
                            </tr>
                        </thead>
                        <tbody id="journal-lines-body">
                            @foreach($linesForForm as $index => $line)
                                <tr class="journal-line-row border-b border-gray-100 bg-white hover:bg-gray-50/80" data-journal-line>
                                    <td class="px-2 py-2 align-middle">
                                        <select
                                            name="lines[{{ $index }}][account_id]"
                                            class="journal-field-account h-10 w-full min-w-[10rem] rounded-lg border border-gray-200 bg-gray-50 px-2 text-sm text-gray-900 focus:border-blue-500 focus:ring-2 focus:ring-blue-500"
                                        >
                                            <option value="">اختر الحساب</option>
                                            @foreach($accounts as $account)
                                                <option value="{{ $account->id }}" @selected((string) ($line['account_id'] ?? '') === (string) $account->id)>
                                                    {{ $account->code }} - {{ $account->name_ar }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </td>

                                    <td class="px-2 py-2 align-middle">
                                        <input
                                            type="text"
                                            name="lines[{{ $index }}][description]"
                                            value="{{ $line['description'] ?? '' }}"
                                            placeholder="وصف الحركة"
                                            class="h-10 w-full rounded-lg border border-gray-200 bg-gray-50 px-3 text-sm text-gray-900 focus:border-blue-500 focus:ring-2 focus:ring-blue-500"
                                        >
                                    </td>

                                    <td class="px-2 py-2 align-middle">
                                        <input
                                            type="text"
                                            name="lines[{{ $index }}][cost_center]"
                                            value="{{ $line['cost_center'] ?? '' }}"
                                            placeholder="—"
                                            class="h-10 w-full rounded-lg border border-gray-200 bg-gray-50 px-3 text-sm text-gray-600 focus:border-blue-500 focus:ring-2 focus:ring-blue-500"
                                        >
                                    </td>

                                    <td class="px-2 py-2 align-middle">
                                        <input
                                            type="number" inputmode="decimal"
                                            step="any"
                                            min="0"
                                            name="lines[{{ $index }}][debit]"
                                            value="{{ $line['debit'] !== '' && $line['debit'] !== null ? $line['debit'] : '' }}"
                                            data-field="debit"
                                            class="journal-field-amount h-10 w-full rounded-lg border border-gray-200 bg-gray-50 px-3 text-left text-sm tabular-nums text-gray-900 focus:border-blue-500 focus:ring-2 focus:ring-blue-500"
                                        >
                                    </td>

                                    <td class="px-2 py-2 align-middle">
                                        <input
                                            type="number" inputmode="decimal"
                                            step="any"
                                            min="0"
                                            name="lines[{{ $index }}][credit]"
                                            value="{{ $line['credit'] !== '' && $line['credit'] !== null ? $line['credit'] : '' }}"
                                            data-field="credit"
                                            class="journal-field-amount h-10 w-full rounded-lg border border-gray-200 bg-gray-50 px-3 text-left text-sm tabular-nums text-gray-900 focus:border-blue-500 focus:ring-2 focus:ring-blue-500"
                                        >
                                    </td>

                                    <td class="px-1 py-2 text-center align-middle">
                                        <button
                                            type="button"
                                            class="journal-remove-line inline-flex h-9 w-9 items-center justify-center rounded-lg border border-red-100 bg-red-50 text-red-600 transition hover:bg-red-100 {{ count($linesForForm) <= 2 ? 'invisible pointer-events-none' : '' }}"
                                            title="حذف السطر"
                                        >
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 16 16" fill="currentColor" aria-hidden="true">
                                                <path d="M5.5 5.5A.5.5 0 0 1 6 6v6a.5.5 0 0 1-1 0V6a.5.5 0 0 1 .5-.5zm2.5 0a.5.5 0 0 1 .5.5v6a.5.5 0 0 1-1 0V6a.5.5 0 0 1 .5-.5zm3 .5a.5.5 0 0 0-1 0v6a.5.5 0 0 0 1 0V6z"/>
                                                <path fill-rule="evenodd" d="M14.5 3a1 1 0 0 1-1 1H13v9a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V4h-.5a1 1 0 0 1-1-1V2a1 1 0 0 1 1-1H6a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1h3.5a1 1 0 0 1 1 1v1zM4.118 4 4 4.059V13a1 1 0 0 0 1 1h6a1 1 0 0 0 1-1V4.059L11.882 4H4.118zM2.5 3H13.5V2h-11v1z"/>
                                            </svg>
                                        </button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="mt-6 flex flex-wrap items-center justify-between gap-4 rounded-lg border border-gray-100 bg-gray-50 px-4 py-4">
                    <div class="flex flex-wrap gap-6 text-sm">
                        <div>
                            <span class="block text-gray-500">إجمالي المدين</span>
                            <span id="journal-total-debit" class="mt-1 block font-semibold text-gray-900 tabular-nums">0.00</span>
                        </div>
                        <div>
                            <span class="block text-gray-500">إجمالي الدائن</span>
                            <span id="journal-total-credit" class="mt-1 block font-semibold text-gray-900 tabular-nums">0.00</span>
                        </div>
                        <div>
                            <span class="block text-gray-500">الرصيد</span>
                            <span id="journal-diff" class="mt-1 block font-semibold tabular-nums text-red-600">0.00</span>
                        </div>
                    </div>
                    <div class="flex flex-wrap items-center gap-2 text-sm">
                        <span class="shrink-0 text-gray-600">حالة القيد</span>
                        <span
                            id="journal-balance-badge"
                            class="inline-flex min-h-[2rem] min-w-[6.5rem] items-center justify-center rounded-full border border-red-200 bg-red-50 px-4 py-1.5 text-xs font-semibold text-red-800"
                            role="status"
                            aria-live="polite"
                        >غير متوازن</span>
                    </div>
                </div>

                @error('lines')
                    <p class="mt-3 text-sm text-red-600">{{ $message }}</p>
                @enderror
                @error('balance')
                    <p class="mt-3 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </section>

            <section class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm">
                <h2 class="mb-4 text-base font-semibold text-gray-900">ملاحظات</h2>
                <textarea
                    name="notes"
                    rows="4"
                    class="min-h-[110px] w-full rounded-lg border border-gray-200 bg-gray-50 px-3 py-2.5 text-sm text-gray-900 focus:border-blue-500 focus:ring-2 focus:ring-blue-500"
                    placeholder="أضف ملاحظاتك هنا..."
                >{{ old('notes') }}</textarea>
            </section>

            <div class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm">
                <div class="flex flex-col gap-5 lg:flex-row lg:items-center lg:justify-between">
                    <div class="space-y-2">
                        <p class="text-sm font-medium text-gray-700">إجمالي المدين</p>
                        <div class="flex h-11 w-full max-w-xs overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm">
                            <span class="flex w-16 shrink-0 items-center justify-center border-r border-gray-200 bg-gray-50 text-xs font-semibold tracking-wide text-gray-600">SAR</span>
                            <span id="journal-total-debit-footer" class="flex flex-1 items-center px-4 text-lg font-bold tabular-nums text-gray-900" dir="ltr">0.00</span>
                        </div>
                        <p class="text-xs text-gray-500">يُقارَن مع إجمالي الدائن أعلاه؛ عند التساوي يمكن حفظ القيد.</p>
                    </div>
                    <div class="flex w-full flex-col items-stretch gap-2 lg:w-auto lg:max-w-none">
                        <div class="flex w-full flex-wrap items-center justify-end gap-3">
                            <a
                                href="{{ route('finance.journals.index') }}"
                                class="inline-flex items-center justify-center rounded-lg bg-gray-100 px-6 py-2 text-sm font-semibold text-gray-800 shadow-sm transition hover:bg-gray-200 focus:outline-none focus:ring-2 focus:ring-gray-400 focus:ring-offset-2"
                            >
                                إلغاء
                            </a>
                            <button
                                id="journal-submit-btn"
                                type="submit"
                                disabled
                                title="يجب أن يتساوى المدين والدائن مع وجود مبالغ مدينة"
                                class="inline-flex min-w-[10rem] items-center justify-center rounded-lg bg-blue-600 px-6 py-2 text-sm font-semibold text-white shadow transition hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 disabled:cursor-not-allowed disabled:bg-blue-600 disabled:text-white disabled:opacity-50 disabled:hover:bg-blue-600"
                            >
                                إنشاء القيد
                            </button>
                        </div>
                        <p id="journal-hint-unbalanced" class="w-full text-end text-xs text-amber-800">أدخل مبالغ متوازنة في البنود لتفعيل الحفظ.</p>
                    </div>
                </div>
            </div>
    </form>
</div>

{{-- قالب مخفي لاستنساخ سطر جديد (بدون Alpine) --}}
<template id="journal-line-row-template">
    <tr class="journal-line-row border-b border-gray-100 bg-white hover:bg-gray-50/80" data-journal-line>
        <td class="px-2 py-2 align-middle">
            <select
                name="lines[__INDEX__][account_id]"
                class="journal-field-account h-10 w-full min-w-[10rem] rounded-lg border border-gray-200 bg-gray-50 px-2 text-sm text-gray-900 focus:border-blue-500 focus:ring-2 focus:ring-blue-500"
            >
                <option value="">اختر الحساب</option>
                @foreach($accounts as $account)
                    <option value="{{ $account->id }}">{{ $account->code }} - {{ $account->name_ar }}</option>
                @endforeach
            </select>
        </td>
        <td class="px-2 py-2 align-middle">
            <input type="text" name="lines[__INDEX__][description]" value="" placeholder="وصف الحركة" class="h-10 w-full rounded-lg border border-gray-200 bg-gray-50 px-3 text-sm text-gray-900 focus:border-blue-500 focus:ring-2 focus:ring-blue-500">
        </td>
        <td class="px-2 py-2 align-middle">
            <input type="text" name="lines[__INDEX__][cost_center]" value="" placeholder="—" class="h-10 w-full rounded-lg border border-gray-200 bg-gray-50 px-3 text-sm text-gray-600 focus:border-blue-500 focus:ring-2 focus:ring-blue-500">
        </td>
        <td class="px-2 py-2 align-middle">
            <input type="number" inputmode="decimal" step="any" min="0" name="lines[__INDEX__][debit]" value="" data-field="debit" class="journal-field-amount h-10 w-full rounded-lg border border-gray-200 bg-gray-50 px-3 text-left text-sm tabular-nums text-gray-900 focus:border-blue-500 focus:ring-2 focus:ring-blue-500">
        </td>
        <td class="px-2 py-2 align-middle">
            <input type="number" inputmode="decimal" step="any" min="0" name="lines[__INDEX__][credit]" value="" data-field="credit" class="journal-field-amount h-10 w-full rounded-lg border border-gray-200 bg-gray-50 px-3 text-left text-sm tabular-nums text-gray-900 focus:border-blue-500 focus:ring-2 focus:ring-blue-500">
        </td>
        <td class="px-1 py-2 text-center align-middle">
            <button type="button" class="journal-remove-line inline-flex h-9 w-9 items-center justify-center rounded-lg border border-red-100 bg-red-50 text-red-600 transition hover:bg-red-100" title="حذف السطر">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 16 16" fill="currentColor" aria-hidden="true">
                    <path d="M5.5 5.5A.5.5 0 0 1 6 6v6a.5.5 0 0 1-1 0V6a.5.5 0 0 1 .5-.5zm2.5 0a.5.5 0 0 1 .5.5v6a.5.5 0 0 1-1 0V6a.5.5 0 0 1 .5-.5zm3 .5a.5.5 0 0 0-1 0v6a.5.5 0 0 0 1 0V6z"/>
                    <path fill-rule="evenodd" d="M14.5 3a1 1 0 0 1-1 1H13v9a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V4h-.5a1 1 0 0 1-1-1V2a1 1 0 0 1 1-1H6a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1h3.5a1 1 0 0 1 1 1v1zM4.118 4 4 4.059V13a1 1 0 0 0 1 1h6a1 1 0 0 0 1-1V4.059L11.882 4H4.118zM2.5 3H13.5V2h-11v1z"/>
                </svg>
            </button>
        </td>
    </tr>
</template>

<script>
    (function () {
        function toNumber(value) {
            if (value === null || value === undefined || value === '') return 0;
            var n = Number(value);
            return Number.isFinite(n) ? n : 0;
        }

        function getRows() {
            return Array.prototype.slice.call(document.querySelectorAll('#journal-lines-body .journal-line-row'));
        }

        function reindexLineNames() {
            getRows().forEach(function (row, index) {
                row.querySelectorAll('[name^="lines["]').forEach(function (el) {
                    el.name = el.name.replace(/lines\[\d+]/, 'lines[' + index + ']');
                });
            });
        }

        function syncDebitCredit(row, changed) {
            var debitInput = row.querySelector('input[data-field="debit"]');
            var creditInput = row.querySelector('input[data-field="credit"]');
            if (!debitInput || !creditInput) return;
            if (changed === 'debit') {
                var d = toNumber(debitInput.value);
                if (d > 0) creditInput.value = '';
            } else if (changed === 'credit') {
                var c = toNumber(creditInput.value);
                if (c > 0) debitInput.value = '';
            }
        }

        function recalc() {
            var totalDebit = 0;
            var totalCredit = 0;
            getRows().forEach(function (row) {
                var d = row.querySelector('input[data-field="debit"]');
                var c = row.querySelector('input[data-field="credit"]');
                totalDebit += toNumber(d && d.value);
                totalCredit += toNumber(c && c.value);
            });
            var diff = totalDebit - totalCredit;
            var balanced = Math.abs(diff) < 0.005 && totalDebit > 0;

            var td = document.getElementById('journal-total-debit');
            var tc = document.getElementById('journal-total-credit');
            var df = document.getElementById('journal-diff');
            var foot = document.getElementById('journal-total-debit-footer');
            var badge = document.getElementById('journal-balance-badge');
            var btn = document.getElementById('journal-submit-btn');
            var hint = document.getElementById('journal-hint-unbalanced');

            if (td) td.textContent = totalDebit.toFixed(2);
            if (tc) tc.textContent = totalCredit.toFixed(2);
            if (foot) foot.textContent = totalDebit.toFixed(2);
            if (df) {
                df.textContent = diff.toFixed(2);
                df.classList.toggle('text-emerald-600', balanced);
                df.classList.toggle('text-red-600', !balanced);
            }
            if (badge) {
                badge.textContent = balanced ? 'متوازن' : 'غير متوازن';
                badge.className = 'inline-flex min-h-[2rem] min-w-[6.5rem] items-center justify-center rounded-full border px-4 py-1.5 text-xs font-semibold ' +
                    (balanced ? 'border-emerald-200 bg-emerald-50 text-emerald-800' : 'border-red-200 bg-red-50 text-red-800');
            }
            if (btn) {
                btn.disabled = !balanced;
                btn.setAttribute('aria-disabled', balanced ? 'false' : 'true');
                btn.title = balanced ? 'حفظ القيد في دفتر اليومية' : 'يجب أن يتساوى المدين والدائن مع وجود مبالغ مدينة';
            }
            if (hint) {
                hint.style.display = balanced ? 'none' : 'block';
            }

            updateRemoveButtons();
        }

        function updateRemoveButtons() {
            var rows = getRows();
            var allowRemove = rows.length > 2;
            rows.forEach(function (row) {
                var b = row.querySelector('.journal-remove-line');
                if (!b) return;
                if (allowRemove) {
                    b.classList.remove('invisible', 'pointer-events-none');
                } else {
                    b.classList.add('invisible', 'pointer-events-none');
                }
            });
        }

        function bindRow(row) {
            var debitInput = row.querySelector('input[data-field="debit"]');
            var creditInput = row.querySelector('input[data-field="credit"]');
            if (debitInput) {
                debitInput.addEventListener('input', function () {
                    syncDebitCredit(row, 'debit');
                    recalc();
                });
            }
            if (creditInput) {
                creditInput.addEventListener('input', function () {
                    syncDebitCredit(row, 'credit');
                    recalc();
                });
            }
            var rm = row.querySelector('.journal-remove-line');
            if (rm) {
                rm.addEventListener('click', function () {
                    if (getRows().length <= 2) return;
                    row.remove();
                    reindexLineNames();
                    recalc();
                });
            }
        }

        document.addEventListener('DOMContentLoaded', function () {
            var body = document.getElementById('journal-lines-body');
            var addBtn = document.getElementById('add-line-button');
            var tpl = document.getElementById('journal-line-row-template');
            var form = document.getElementById('journal-entry-form');

            getRows().forEach(bindRow);

            if (addBtn && body && tpl) {
                addBtn.addEventListener('click', function () {
                    var html = tpl.innerHTML.replace(/__INDEX__/g, String(getRows().length));
                    var wrap = document.createElement('tbody');
                    wrap.innerHTML = html.trim();
                    var newRow = wrap.firstElementChild;
                    if (!newRow) return;
                    body.appendChild(newRow);
                    reindexLineNames();
                    bindRow(newRow);
                    recalc();
                });
            }

            if (form) {
                form.addEventListener('submit', function (e) {
                    var rows = getRows();
                    var totalDebit = 0;
                    var totalCredit = 0;
                    rows.forEach(function (row) {
                        var d = row.querySelector('input[data-field="debit"]');
                        var c = row.querySelector('input[data-field="credit"]');
                        totalDebit += toNumber(d && d.value);
                        totalCredit += toNumber(c && c.value);
                    });
                    var diff = totalDebit - totalCredit;
                    var balanced = Math.abs(diff) < 0.005 && totalDebit > 0;
                    if (!balanced) {
                        e.preventDefault();
                    }
                });
            }

            recalc();
        });
    })();
</script>
@endsection
