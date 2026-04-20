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
    $journalAccountSelectOptions = collect($accounts ?? [])->map(fn ($account) => [
        'v' => (string) $account->id,
        'l' => trim((string) ($account->code ?? '').' - '.(string) ($account->name_ar ?? '')),
    ])->values()->all();
    $journalAccountSelectOptions = array_merge([['v' => '', 'l' => 'اختر الحساب']], $journalAccountSelectOptions);

    $journalCreateConfig = [
        'headerDefaults' => [
            'date' => old('date', now()->toDateString()),
            'reference' => old('reference', ''),
            'description' => old('description', ''),
        ],
        'accountOptions' => $journalAccountSelectOptions,
        'initial' => null,
    ];
    $oldLines = old('lines');
    if (is_array($oldLines) && count($oldLines) >= 2) {
        $mapLines = [];
        foreach ($oldLines as $line) {
            $line = is_array($line) ? $line : [];
            $mapLines[] = [
                'account_id' => (string) ($line['account_id'] ?? ''),
                'description' => $line['description'] ?? '',
                'cost_center' => $line['cost_center'] ?? '',
                'debit' => isset($line['debit']) && $line['debit'] !== '' ? (float) $line['debit'] : 0,
                'credit' => isset($line['credit']) && $line['credit'] !== '' ? (float) $line['credit'] : 0,
            ];
        }
        $journalCreateConfig['initial'] = [
            'date' => old('date', now()->toDateString()),
            'reference' => old('reference', ''),
            'description' => old('description', ''),
            'lines' => $mapLines,
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
        enctype="multipart/form-data"
        class="space-y-6"
        novalidate
        x-data="() => window.journalEntryForm(@js($journalCreateConfig))"
        @submit="if (!balanced) { $event.preventDefault() }"
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
                            x-model="header.date"
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
                            x-model="header.reference"
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
                            x-model="header.description"
                            placeholder="البيان العام للقيد"
                            class="h-10 w-full rounded-lg border border-gray-200 bg-gray-50 px-3 text-sm text-gray-900 focus:border-blue-500 focus:ring-2 focus:ring-blue-500"
                        >
                    </div>
                </div>
            </section>

            <section class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm">
                <x-attachment-handler
                    hint-field="journal_entry_attachments"
                    title="مرفقات القيد"
                    :existing="[]"
                    :show-existing="false"
                    :allow-delete="true"
                    help-text="مستندات داعمة اختيارية (حتى 20 ملفاً، 10 ميجابايت لكل ملف)."
                />
            </section>

            <section class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm">
                <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
                    <h2 class="text-base font-semibold text-gray-900">بنود القيد</h2>
                    <button
                        type="button"
                        x-on:click.prevent="addLine()"
                        class="inline-flex shrink-0 items-center gap-2 rounded-lg border-2 border-blue-600 bg-white px-4 py-2 text-sm font-semibold text-blue-700 shadow-sm transition hover:bg-blue-50 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="currentColor" viewBox="0 0 16 16" aria-hidden="true">
                            <path d="M8 4a.5.5 0 0 1 .5.5v3h3a.5.5 0 0 1 0 1h-3v3a.5.5 0 0 1-1 0v-3h-3a.5.5 0 0 1 0-1h3v-3A.5.5 0 0 1 8 4z"/>
                        </svg>
                        إضافة سطر
                    </button>
                </div>

                <div class="overflow-x-auto overflow-y-visible rounded-lg border border-gray-200">
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
                        <template x-for="(line, index) in lines" :key="line._lid">
                            <tbody class="border-b border-gray-100 bg-white">
                                <tr class="hover:bg-gray-50/80">
                                    <td class="relative z-30 min-w-[11rem] px-2 py-2 align-middle">
                                        <div
                                            class="relative w-full"
                                            x-data="window.journalLineAccountSearch(line, accountOptions, index)"
                                            @resize.window="if (open) positionPanel()"
                                            @scroll.window.passive="if (open) positionPanel()"
                                        >
                                            <input type="hidden" :name="'lines[' + lineIndex + '][account_id]'" x-model="line.account_id">
                                            <button
                                                type="button"
                                                x-ref="jTrigger"
                                                @click="toggle()"
                                                @keydown.escape.prevent.stop="close()"
                                                :aria-expanded="open"
                                                class="flex h-10 w-full items-center justify-between gap-2 rounded-lg border border-gray-200 bg-gray-50 px-3 text-right text-sm text-gray-900 shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100"
                                            >
                                                <span class="min-w-0 flex-1 truncate font-normal" x-text="display()"></span>
                                                <svg class="h-4 w-4 shrink-0 text-gray-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                                                </svg>
                                            </button>
                                            <div
                                                x-show="open"
                                                x-cloak
                                                x-ref="jPanel"
                                                x-transition
                                                class="fixed z-[200] overflow-hidden rounded-lg border border-gray-200 bg-white py-0.5 text-sm shadow-lg ring-1 ring-black/5"
                                                :style="'top:' + panelTop + 'px;left:' + panelLeft + 'px;width:' + panelWidth + 'px;max-width:calc(100vw - 1rem)'"
                                                @click.outside="close()"
                                                @keydown.escape.prevent.stop="close()"
                                            >
                                                <div class="border-b border-gray-100 px-2 pb-1.5 pt-1">
                                                    <input
                                                        type="search"
                                                        x-ref="jq"
                                                        x-model="q"
                                                        placeholder="ابحث بالرمز أو الاسم..."
                                                        autocomplete="off"
                                                        dir="rtl"
                                                        class="h-9 w-full rounded-md border border-gray-200 bg-gray-50 px-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                                                    >
                                                </div>
                                                <ul class="max-h-[min(15rem,50vh)] overflow-y-auto overscroll-contain py-0.5" role="listbox">
                                                    <template x-for="(row, idx) in filtered" :key="row.v + '-' + idx">
                                                        <li role="option">
                                                            <button
                                                                type="button"
                                                                class="flex w-full items-center justify-start px-3 py-2 text-right text-sm hover:bg-blue-50 focus:bg-blue-50 focus:outline-none"
                                                                :class="String(line.account_id) === String(row.v) ? 'bg-blue-50 font-semibold text-blue-900' : 'text-gray-800'"
                                                                @click="pick(row.v)"
                                                                x-text="row.l"
                                                            ></button>
                                                        </li>
                                                    </template>
                                                </ul>
                                                <div class="flex justify-end border-t border-gray-100 px-2 py-1" x-show="line.account_id !== '' && line.account_id != null">
                                                    <button type="button" class="text-xs font-medium text-gray-600 hover:text-blue-700" @click="clearSel()">مسح الاختيار</button>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-2 py-2 align-middle">
                                        <input
                                            type="text"
                                            :name="'lines[' + index + '][description]'"
                                            x-model="line.description"
                                            placeholder="وصف الحركة"
                                            class="h-10 w-full rounded-lg border border-gray-200 bg-gray-50 px-3 text-sm text-gray-900 focus:border-blue-500 focus:ring-2 focus:ring-blue-500"
                                        >
                                    </td>
                                    <td class="px-2 py-2 align-middle">
                                        <input
                                            type="text"
                                            :name="'lines[' + index + '][cost_center]'"
                                            x-model="line.cost_center"
                                            placeholder="—"
                                            class="h-10 w-full rounded-lg border border-gray-200 bg-gray-50 px-3 text-sm text-gray-600 focus:border-blue-500 focus:ring-2 focus:ring-blue-500"
                                        >
                                    </td>
                                    <td class="px-2 py-2 align-middle">
                                        <input
                                            type="number" inputmode="decimal"
                                            step="any"
                                            min="0"
                                            :name="'lines[' + index + '][debit]'"
                                            x-model.number="line.debit"
                                            x-on:input="syncCredit(index, 'debit')"
                                            class="h-10 w-full rounded-lg border border-gray-200 bg-gray-50 px-3 text-left text-sm tabular-nums text-gray-900 focus:border-blue-500 focus:ring-2 focus:ring-blue-500"
                                        >
                                    </td>
                                    <td class="px-2 py-2 align-middle">
                                        <input
                                            type="number" inputmode="decimal"
                                            step="any"
                                            min="0"
                                            :name="'lines[' + index + '][credit]'"
                                            x-model.number="line.credit"
                                            x-on:input="syncCredit(index, 'credit')"
                                            class="h-10 w-full rounded-lg border border-gray-200 bg-gray-50 px-3 text-left text-sm tabular-nums text-gray-900 focus:border-blue-500 focus:ring-2 focus:ring-blue-500"
                                        >
                                    </td>
                                    <td class="px-1 py-2 text-center align-middle">
                                        <button
                                            type="button"
                                            x-on:click.prevent="removeLine(index)"
                                            x-show="lines.length > 2"
                                            class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-red-100 bg-red-50 text-red-600 transition hover:bg-red-100"
                                            title="حذف السطر"
                                        >
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 16 16" fill="currentColor" aria-hidden="true">
                                                <path d="M5.5 5.5A.5.5 0 0 1 6 6v6a.5.5 0 0 1-1 0V6a.5.5 0 0 1 .5-.5zm2.5 0a.5.5 0 0 1 .5.5v6a.5.5 0 0 1-1 0V6a.5.5 0 0 1 .5-.5zm3 .5a.5.5 0 0 0-1 0v6a.5.5 0 0 0 1 0V6z"/>
                                                <path fill-rule="evenodd" d="M14.5 3a1 1 0 0 1-1 1H13v9a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V4h-.5a1 1 0 0 1-1-1V2a1 1 0 0 1 1-1H6a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1h3.5a1 1 0 0 1 1 1v1zM4.118 4 4 4.059V13a1 1 0 0 0 1 1h6a1 1 0 0 0 1-1V4.059L11.882 4H4.118zM2.5 3H13.5V2h-11v1z"/>
                                            </svg>
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </template>
                    </table>
                </div>

                <div class="mt-6 flex flex-wrap items-center justify-between gap-4 rounded-lg border border-gray-100 bg-gray-50 px-4 py-4">
                    <div class="flex flex-wrap gap-6 text-sm">
                        <div>
                            <span class="block text-gray-500">إجمالي المدين</span>
                            <span class="mt-1 block font-semibold text-gray-900 tabular-nums" x-text="totalDebit.toFixed(2)"></span>
                        </div>
                        <div>
                            <span class="block text-gray-500">إجمالي الدائن</span>
                            <span class="mt-1 block font-semibold text-gray-900 tabular-nums" x-text="totalCredit.toFixed(2)"></span>
                        </div>
                        <div>
                            <span class="block text-gray-500">الرصيد</span>
                            <span class="mt-1 block font-semibold tabular-nums" :class="balanced ? 'text-emerald-600' : 'text-red-600'" x-text="differenceDisplay"></span>
                        </div>
                    </div>
                    <div class="flex flex-wrap items-center gap-2 text-sm">
                        <span class="shrink-0 text-gray-600">حالة القيد</span>
                        <span
                            class="inline-flex min-h-[2rem] min-w-[6.5rem] items-center justify-center rounded-full border px-4 py-1.5 text-xs font-semibold"
                            :class="balanced ? 'border-emerald-200 bg-emerald-50 text-emerald-800' : 'border-red-200 bg-red-50 text-red-800'"
                            x-text="balanced ? 'متوازن' : 'غير متوازن'"
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
                            <span class="flex flex-1 items-center px-4 text-lg font-bold tabular-nums text-gray-900" dir="ltr" x-text="totalDebit.toFixed(2)"></span>
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
                                type="submit"
                                x-bind:disabled="!balanced"
                                x-bind:aria-disabled="!balanced"
                                x-bind:title="balanced ? 'حفظ القيد في دفتر اليومية' : 'يجب أن يتساوى المدين والدائن مع وجود مبالغ مدينة'"
                                class="inline-flex min-w-[10rem] items-center justify-center rounded-lg px-6 py-2 text-sm font-semibold shadow transition focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2"
                                :class="balanced
                                    ? 'bg-blue-600 text-white hover:bg-blue-700'
                                    : 'cursor-not-allowed border-2 border-amber-400 bg-amber-50 text-amber-900 opacity-80'"
                            >
                                إنشاء القيد
                            </button>
                        </div>
                        <p class="w-full text-end text-xs text-amber-800" x-show="!balanced" x-cloak>أدخل مبالغ متوازنة في البنود لتفعيل الحفظ.</p>
                    </div>
                </div>
            </div>
    </form>
</div>
@endsection
