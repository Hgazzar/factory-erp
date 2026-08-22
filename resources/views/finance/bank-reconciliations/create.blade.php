@extends(niche_shell_layout())

@section('title', 'تسوية بنك جديدة - '.config('app.name'))

@section('breadcrumb')
    <a href="{{ route('dashboard') }}" class="text-gray-500 hover:text-blue-600">الرئيسية</a>
    <span class="mx-1 text-gray-400">›</span>
    <a href="{{ route('finance.dashboard') }}" class="text-gray-500 hover:text-blue-600">المحاسبة</a>
    <span class="mx-1 text-gray-400">›</span>
    <a href="{{ route('finance.bank-reconciliations.index') }}" class="text-gray-500 hover:text-blue-600">تسوية البنك</a>
    <span class="mx-1 text-gray-400">›</span>
    <span class="text-blue-900 font-semibold">تسوية بنك جديدة</span>
@endsection

@section('content')
@php
    $bankReconAccountOptions = collect($accounts ?? [])->map(fn ($account) => [
        'value' => $account->id,
        'label' => trim((string) ($account->code ?? '').' - '.(string) ($account->name_ar ?: $account->name_en ?? '')),
    ])->all();
@endphp
<div dir="rtl" class="mx-auto w-full max-w-full space-y-6">
    <header class="flex items-center justify-center gap-3">
        <h1 class="inline-flex items-center gap-2 text-4xl font-bold text-gray-900">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-7 w-7 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4h16v16H4V4zm4 4h8m-8 4h8m-8 4h5" />
            </svg>
            تسوية بنك جديدة
        </h1>
    </header>

    @php
        $initialItems = old('items', []);
    @endphp

    <form method="POST" action="{{ route('finance.bank-reconciliations.store') }}" class="space-y-6">
        @csrf
        <input type="hidden" name="status" value="{{ old('status', 'draft') }}">

        <section class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm">
            <div class="mb-6 text-right">
                <h2 class="text-2xl font-bold text-gray-900">معلومات كشف الحساب</h2>
                <p class="mt-1 text-sm text-gray-500">ادخل تفاصيل كشف الحساب البنكي للتسوية</p>
            </div>

            <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                <div class="space-y-1">
                    <label class="inline-flex items-center gap-1 text-sm font-medium text-gray-700" for="account_id-trigger">
                        <span>الحساب البنكي <span class="text-red-500">*</span></span>
                        <x-info field="bank_reconciliation_account" />
                    </label>
                    <x-searchable-select
                        name="account_id"
                        id="account_id"
                        :options="$bankReconAccountOptions"
                        :value="old('account_id')"
                        :required="true"
                        empty-label="اختر حسابا بنكيا"
                        placeholder="ابحث بالرمز أو اسم الحساب..."
                    />
                </div>

                <div class="space-y-1">
                    <label class="inline-flex items-center gap-1 text-sm font-medium text-gray-700">
                        <span>تاريخ كشف الحساب <span class="text-red-500">*</span></span>
                        <x-info field="bank_reconciliation_statement_date" />
                    </label>
                    <input type="date" name="statement_date" value="{{ old('statement_date', now()->toDateString()) }}" required class="h-11 w-full rounded-lg border border-gray-200 bg-gray-50 px-3 text-sm focus:border-blue-500 focus:ring-blue-500">
                </div>

                <div class="space-y-1">
                    <label class="inline-flex items-center gap-1 text-sm font-medium text-gray-700">
                        <span>الرصيد الافتتاحي <span class="text-red-500">*</span></span>
                        <x-info field="bank_reconciliation_book_balance" />
                    </label>
                    <input type="number" inputmode="decimal" step="any" name="book_balance" value="{{ old('book_balance', 0) }}" required class="h-11 w-full rounded-lg border border-gray-200 bg-gray-50 px-3 text-sm focus:border-blue-500 focus:ring-blue-500">
                    <p class="text-xs text-gray-500">الرصيد في بداية فترة كشف الحساب</p>
                </div>

                <div class="space-y-1">
                    <label class="inline-flex items-center gap-1 text-sm font-medium text-gray-700">
                        <span>الرصيد الختامي <span class="text-red-500">*</span></span>
                        <x-info field="bank_reconciliation_statement_balance" />
                    </label>
                    <input type="number" inputmode="decimal" step="any" name="statement_balance" value="{{ old('statement_balance', 0) }}" required class="h-11 w-full rounded-lg border border-gray-200 bg-gray-50 px-3 text-sm focus:border-blue-500 focus:ring-blue-500">
                    <p class="text-xs text-gray-500">الرصيد في نهاية كشف الحساب (حسب البنك)</p>
                </div>
            </div>
        </section>

        <section class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm" x-data='bankReconciliationLines(@json($initialItems))'>
            <div class="mb-4 flex flex-wrap items-start justify-between gap-3">
                <div class="text-right">
                    <h2 class="text-2xl font-bold text-gray-900">بنود كشف الحساب</h2>
                    <p class="mt-1 text-sm text-gray-500">أضف المعاملات من كشف الحساب البنكي</p>
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    <button type="button" class="inline-flex items-center gap-2 rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a2 2 0 002 2h12a2 2 0 002-2v-1M8 12l4-4m0 0l4 4m-4-4v12" />
                        </svg>
                        استيراد CSV
                    </button>
                    <button type="button" @click="addItem()" class="inline-flex items-center gap-2 rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">
                        <span class="text-base leading-none">+</span>
                        إضافة بند
                    </button>
                </div>
            </div>

            <div x-show="items.length === 0" class="rounded-lg border border-gray-100 bg-gray-50/40 py-14 text-center">
                <svg xmlns="http://www.w3.org/2000/svg" class="mx-auto h-12 w-12 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l3.414 3.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
                <p class="mt-3 text-base font-semibold text-gray-500">لم يتم إضافة بنود</p>
                <p class="mt-1 text-sm text-gray-400">أضف البنود يدويا أو استوردها من ملف CSV</p>
            </div>

            <div x-show="items.length > 0" class="overflow-x-auto rounded-lg border border-gray-100">
                <table class="w-full min-w-[980px] text-sm">
                    <thead class="bg-gray-50 text-xs font-semibold text-gray-500">
                        <tr>
                            <th class="px-3 py-3 text-right">التاريخ</th>
                            <th class="px-3 py-3 text-right">الوصف</th>
                            <th class="px-3 py-3 text-right">مرجع</th>
                            <th class="px-3 py-3 text-right">مدين</th>
                            <th class="px-3 py-3 text-right">دائن</th>
                            <th scope="col" class="w-[1%] whitespace-nowrap px-3 py-3 text-center text-xs font-semibold text-gray-500">إجراءات</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <template x-for="(item, index) in items" :key="index">
                            <tr class="hover:bg-gray-50">
                                <td class="px-3 py-2">
                                    <input type="date" :name="`items[${index}][date]`" x-model="item.date" class="h-10 w-full rounded-lg border border-gray-200 bg-gray-50 px-3 text-sm focus:border-blue-500 focus:ring-blue-500">
                                </td>
                                <td class="px-3 py-2">
                                    <input type="text" :name="`items[${index}][description]`" x-model="item.description" placeholder="وصف العملية" class="h-10 w-full rounded-lg border border-gray-200 bg-gray-50 px-3 text-sm focus:border-blue-500 focus:ring-blue-500">
                                </td>
                                <td class="px-3 py-2">
                                    <input type="text" :name="`items[${index}][reference]`" x-model="item.reference" placeholder="مرجع" class="h-10 w-full rounded-lg border border-gray-200 bg-gray-50 px-3 text-sm focus:border-blue-500 focus:ring-blue-500">
                                </td>
                                <td class="px-3 py-2">
                                    <input type="number" inputmode="decimal" step="any" min="0" :name="`items[${index}][debit]`" x-model="item.debit" class="h-10 w-full rounded-lg border border-gray-200 bg-gray-50 px-3 text-sm focus:border-blue-500 focus:ring-blue-500">
                                </td>
                                <td class="px-3 py-2">
                                    <input type="number" inputmode="decimal" step="any" min="0" :name="`items[${index}][credit]`" x-model="item.credit" class="h-10 w-full rounded-lg border border-gray-200 bg-gray-50 px-3 text-sm focus:border-blue-500 focus:ring-blue-500">
                                </td>
                                <td class="px-3 py-2">
                                    <button type="button" @click="removeItem(index)" class="inline-flex h-9 w-9 items-center justify-center rounded-md border border-red-200 bg-white text-red-500 hover:bg-red-50 hover:text-red-600" title="حذف البند">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6M9 7V4a1 1 0 011-1h4a1 1 0 011 1v3M4 7h16" />
                                        </svg>
                                    </button>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>
        </section>

        <div class="flex justify-end gap-3">
            <a href="{{ route('finance.bank-reconciliations.index') }}" class="rounded-lg border border-gray-200 bg-white px-5 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50">إلغاء</a>
            <button type="submit" class="inline-flex items-center gap-2 rounded-lg bg-blue-600 px-6 py-2.5 text-sm font-semibold text-white hover:bg-blue-700">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>
                إنشاء التسوية
            </button>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
    function bankReconciliationLines(initialItems) {
        return {
            items: Array.isArray(initialItems)
                ? initialItems.map((item) => ({
                    date: item?.date || '',
                    description: item?.description || '',
                    reference: item?.reference || '',
                    debit: item?.debit || '',
                    credit: item?.credit || '',
                }))
                : [],
            addItem() {
                this.items.push({
                    date: '',
                    description: '',
                    reference: '',
                    debit: '',
                    credit: '',
                });
            },
            removeItem(index) {
                this.items.splice(index, 1);
            },
        };
    }
</script>
@endpush
