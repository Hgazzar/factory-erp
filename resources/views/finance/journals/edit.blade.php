@extends('layouts.app')

@section('title', 'تعديل قيد - UFUQ ERP')

@section('breadcrumb')
    <a href="{{ route('dashboard') }}" class="text-gray-500 hover:text-indigo-600">الرئيسية</a>
    <span class="mx-1 text-gray-400">›</span>
    <a href="{{ route('finance.dashboard') }}" class="text-gray-500 hover:text-indigo-600">لوحة المحاسبة</a>
    <span class="mx-1 text-gray-400">›</span>
    <a href="{{ route('finance.journals.index') }}" class="text-gray-500 hover:text-indigo-600">القيود اليومية</a>
    <span class="mx-1 text-gray-400">›</span>
    <span class="text-indigo-900 font-semibold">تعديل قيد {{ $entry->id }}</span>
@endsection

@section('content')
@php
    $entryData = [
        'date' => $entry->date?->format('Y-m-d'),
        'reference' => $entry->reference ?? '',
        'description' => $entry->description ?? '',
        'lines' => $entry->items->map(fn($i) => [
            'account_id' => (string) $i->account_id,
            'description' => $i->description ?? '',
            'cost_center' => $i->cost_center ?? '',
            'debit' => (float) $i->debit,
            'credit' => (float) $i->credit,
        ])->values()->all(),
    ];
    $journalEditXDataConfig = ['initial' => $entryData];
@endphp
<div dir="rtl" class="content-wrap">
    <div class="mb-3 flex items-center justify-between">
        <div>
            <h1 class="text-lg md:text-xl font-semibold text-gray-900">تعديل قيد #{{ $entry->id }}</h1>
            <p class="mt-1 text-[11px] text-gray-500">تحديث تفاصيل القيد وبنوده.</p>
        </div>
    </div>

    <div
        x-cloak
        class="bg-white border border-gray-200 rounded-xl shadow-sm"
    >
        <div class="flex items-center justify-between border-b border-gray-100 px-5 py-3">
            <h2 class="text-sm font-semibold text-gray-800">تعديل القيد</h2>
        </div>

        <form
            wire:ignore.self
            method="POST"
            action="{{ route('finance.journals.update', $entry) }}"
            x-data="() => window.journalEntryForm(@js($journalEditXDataConfig))"
        >
            @csrf
            @method('PUT')

            <section class="border-b border-gray-100 px-5 py-4">
                <h3 class="mb-3 text-xs font-semibold text-gray-600">تفاصيل القيد</h3>
                <div class="grid grid-cols-1 gap-3 md:grid-cols-3 text-[11px]">
                    <div class="space-y-1">
                        <label class="block text-[11px] font-medium text-gray-600">تاريخ القيد <span class="text-red-500" aria-hidden="true">*</span></label>
                        <input type="date" name="date" x-model="header.date"
                            class="block w-full rounded-md border px-2 py-1.5 text-[11px] text-gray-800 focus:border-indigo-500 focus:outline-none focus:ring-0 {{ $errors->has('date') ? 'border-red-500 bg-red-50/40' : 'border-gray-200 bg-white' }}">
                        @error('date')
                            <p class="mt-1 text-[11px] text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                    <div class="space-y-1">
                        <label class="block text-[11px] font-medium text-gray-600">المرجع</label>
                        <input type="text" name="reference" x-model="header.reference" maxlength="50" placeholder="أدخل المرجع..."
                            class="block w-full rounded-md border border-gray-200 bg-white px-2 py-1.5 text-[11px] text-gray-800 focus:border-indigo-500 focus:outline-none focus:ring-0">
                    </div>
                    <div class="space-y-1 md:col-span-1">
                        <label class="block text-[11px] font-medium text-gray-600">البيان</label>
                        <input type="text" name="description" x-model="header.description" placeholder="البيان العام للقيد"
                            class="block w-full rounded-md border border-gray-200 bg-white px-2 py-1.5 text-[11px] text-gray-800 focus:border-indigo-500 focus:outline-none focus:ring-0">
                    </div>
                </div>
            </section>

            <section class="border-b border-gray-100 px-5 py-4">
                <div class="mb-2 flex items-center justify-between">
                    <h3 class="text-xs font-semibold text-gray-600">بنود القيد</h3>
                    <button type="button" x-on:click.prevent="addLine()"
                        class="inline-flex items-center rounded-md bg-indigo-50 px-3 py-1 text-[11px] font-semibold text-indigo-700 hover:bg-indigo-100">
                        <svg xmlns="http://www.w3.org/2000/svg" class="ml-1 h-3.5 w-3.5" fill="currentColor" viewBox="0 0 16 16"><path d="M8 4a.5.5 0 0 1 .5.5v3h3a.5.5 0 0 1 0 1h-3v3a.5.5 0 0 1-1 0v-3h-3a.5.5 0 0 1 0-1h3v-3A.5.5 0 0 1 8 4z"/></svg>
                        إضافة سطر
                    </button>
                </div>
                <div class="overflow-x-auto rounded-md border border-gray-200">
                    <table class="min-w-full border-collapse text-[11px]">
                        <thead class="bg-gray-50 text-gray-600">
                            <tr>
                                <th class="border-b border-gray-200 px-3 py-1.5 text-right">الحساب <span class="text-red-500" aria-hidden="true">*</span></th>
                                <th class="border-b border-gray-200 px-3 py-1.5 text-right">الوصف</th>
                                <th class="border-b border-gray-200 px-3 py-1.5 text-right">مركز التكلفة</th>
                                <th class="border-b border-gray-200 px-3 py-1.5 text-left">مدين</th>
                                <th class="border-b border-gray-200 px-3 py-1.5 text-left">دائن</th>
                                <th class="w-10 border-b border-gray-200 px-2 py-1.5 text-center"></th>
                            </tr>
                        </thead>
                        <template x-for="(line, index) in lines" :key="line._lid">
                            <tbody class="border-b border-gray-100 bg-white">
                                <tr class="h-9 hover:bg-gray-50">
                                    <td class="border-l border-gray-100 px-2 align-middle">
                                        <select :name="`lines[${index}][account_id]`" x-model="line.account_id"
                                            class="block w-full border-none bg-transparent px-1 py-0.5 text-[11px] text-gray-800 focus:outline-none focus:ring-0">
                                            <option value="">اختر الحساب</option>
                                            @foreach($accounts as $account)
                                                <option value="{{ $account->id }}">{{ $account->code }} - {{ $account->name_ar }}</option>
                                            @endforeach
                                        </select>
                                    </td>
                                    <td class="border-l border-gray-100 px-2 align-middle">
                                        <input type="text" :name="`lines[${index}][description]`" x-model="line.description" placeholder="وصف الحركة"
                                            class="block w-full border-none bg-transparent px-1 py-0.5 text-[11px] text-gray-800 focus:outline-none focus:ring-0">
                                    </td>
                                    <td class="border-l border-gray-100 px-2 align-middle">
                                        <input type="text" :name="`lines[${index}][cost_center]`" x-model="line.cost_center" placeholder="لا يوجد"
                                            class="block w-full border-none bg-transparent px-1 py-0.5 text-[11px] text-gray-500 focus:outline-none focus:ring-0">
                                    </td>
                                    <td class="border-l border-gray-100 px-2 align-middle">
                                        <input type="number" inputmode="decimal" step="any" min="0" :name="`lines[${index}][debit]`" x-model.number="line.debit" x-on:input="syncCredit(index, 'debit')"
                                            class="block w-full border-none bg-transparent px-1 py-0.5 text-left text-[11px] text-gray-800 focus:outline-none focus:ring-0">
                                    </td>
                                    <td class="border-l border-gray-100 px-2 align-middle">
                                        <input type="number" inputmode="decimal" step="any" min="0" :name="`lines[${index}][credit]`" x-model.number="line.credit" x-on:input="syncCredit(index, 'credit')"
                                            class="block w-full border-none bg-transparent px-1 py-0.5 text-left text-[11px] text-gray-800 focus:outline-none focus:ring-0">
                                    </td>
                                    <td class="px-1 text-center align-middle">
                                        <button type="button" x-on:click.prevent="removeLine(index)" x-show="lines.length > 2"
                                            class="inline-flex items-center justify-center rounded bg-red-50 px-1.5 py-1 text-red-500 hover:bg-red-100">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" viewBox="0 0 16 16" fill="currentColor"><path d="M5.5 5.5A.5.5 0 0 1 6 6v6a.5.5 0 0 1-1 0V6a.5.5 0 0 1 .5-.5zm2.5 0a.5.5 0 0 1 .5.5v6a.5.5 0 0 1-1 0V6a.5.5 0 0 1 .5-.5zm3 .5a.5.5 0 0 0-1 0v6a.5.5 0 0 0 1 0V6z"/><path fill-rule="evenodd" d="M14.5 3a1 1 0 0 1-1 1H13v9a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V4h-.5a1 1 0 0 1-1-1V2a1 1 0 0 1 1-1H6a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1h3.5a1 1 0 0 1 1 1v1zM4.118 4 4 4.059V13a1 1 0 0 0 1 1h6a1 1 0 0 0 1-1V4.059L11.882 4H4.118zM2.5 3H13.5V2h-11v1z"/></svg>
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </template>
                    </table>
                </div>
                <div class="mt-3 flex flex-wrap items-center justify-between text-[11px]">
                    <div class="flex flex-wrap gap-4">
                        <div class="flex flex-col"><span class="text-gray-500">إجمالي المدين</span><span class="mt-0.5 font-semibold text-gray-900" x-text="totalDebit.toFixed(2)"></span></div>
                        <div class="flex flex-col"><span class="text-gray-500">إجمالي الدائن</span><span class="mt-0.5 font-semibold text-gray-900" x-text="totalCredit.toFixed(2)"></span></div>
                        <div class="flex flex-col"><span class="text-gray-500">الرصيد</span><span class="mt-0.5 font-semibold" :class="balanced ? 'text-emerald-600' : 'text-red-600'" x-text="differenceDisplay"></span></div>
                    </div>
                    <div class="mt-2 flex flex-wrap items-center gap-2 md:mt-0">
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
                    <p class="mt-2 px-5 text-[11px] text-red-600">{{ $message }}</p>
                @enderror
                @error('balance')
                    <p class="mt-2 px-5 text-[11px] text-red-600">{{ $message }}</p>
                @enderror
            </section>

            <section class="border-b border-gray-100 px-5 py-4">
                <h3 class="mb-2 text-xs font-semibold text-gray-600">ملاحظات</h3>
                <textarea
                    name="notes"
                    rows="3"
                    class="block w-full rounded-md border border-gray-200 bg-white px-2 py-2 text-[11px] text-gray-800 focus:border-indigo-500 focus:outline-none focus:ring-0"
                    placeholder="ملاحظات داخلية…"
                >{{ old('notes', $entry->notes) }}</textarea>
                @error('notes')
                    <p class="mt-1 text-[11px] text-red-600">{{ $message }}</p>
                @enderror
            </section>

            <div class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm">
                <div class="flex flex-col gap-5 lg:flex-row lg:items-center lg:justify-between">
                    <div class="space-y-2">
                        <p class="text-sm font-medium text-gray-700">إجمالي المدين</p>
                        <div class="flex h-11 w-full max-w-xs overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm">
                            <span class="flex w-16 shrink-0 items-center justify-center border-r border-gray-200 bg-gray-50 text-xs font-semibold tracking-wide text-gray-600">SAR</span>
                            <span class="flex flex-1 items-center px-4 text-lg font-bold tabular-nums text-gray-900" dir="ltr" x-text="totalDebit.toFixed(2)"></span>
                        </div>
                        <p class="text-xs text-gray-500">عند تساوي المدين والدائن يمكن حفظ التعديلات.</p>
                    </div>
                    <div class="flex w-full flex-col gap-2 lg:w-auto lg:max-w-none">
                        <div class="flex w-full flex-wrap items-center justify-end gap-3">
                            <a href="{{ route('finance.journals.index') }}" class="inline-flex min-h-[2.75rem] items-center justify-center rounded-lg border border-gray-200 bg-white px-5 py-2.5 text-sm font-medium text-gray-800 hover:bg-gray-50 transition">إلغاء</a>
                            <button
                                type="submit"
                                x-bind:disabled="!balanced"
                                x-bind:aria-disabled="!balanced"
                                x-bind:title="balanced ? 'حفظ التعديلات' : 'يجب أن يتساوى المدين والدائن مع وجود مبالغ مدينة'"
                                class="inline-flex min-h-[2.75rem] min-w-[10rem] items-center justify-center rounded-lg px-6 py-2.5 text-sm font-bold shadow-sm transition"
                                :class="balanced
                                    ? '!bg-blue-600 !text-white hover:!bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 border-0'
                                    : 'border-2 border-amber-400 bg-amber-50 text-amber-900 ring-1 ring-amber-200/60 cursor-not-allowed'"
                            >
                                حفظ التعديلات
                            </button>
                        </div>
                        <p class="w-full text-end text-xs text-amber-800" x-show="!balanced" x-cloak>أدخل مبالغ متوازنة في البنود لتفعيل الحفظ.</p>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection
