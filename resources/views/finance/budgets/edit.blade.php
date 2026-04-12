@extends('layouts.app')

@section('title', 'تعديل الميزانية - MIRADA ERP')

@section('breadcrumb')
    <a href="{{ route('dashboard') }}" class="text-gray-500 hover:text-blue-600">الرئيسية</a>
    <span class="mx-1 text-gray-400">›</span>
    <a href="{{ route('finance.dashboard') }}" class="text-gray-500 hover:text-blue-600">المحاسبة</a>
    <span class="mx-1 text-gray-400">›</span>
    <a href="{{ route('finance.budgets.index') }}" class="text-gray-500 hover:text-blue-600">الموازنات</a>
    <span class="mx-1 text-gray-400">›</span>
    <span class="text-blue-900 font-semibold">تعديل</span>
@endsection

@section('content')
<div dir="rtl" class="mx-auto w-full max-w-full space-y-6">
    <header class="flex items-center justify-between gap-3 border-b border-gray-100 pb-4">
        <h1 class="text-3xl font-bold text-gray-900">تعديل الميزانية</h1>
    </header>

    @php
        $monthKeys = ['jan', 'feb', 'mar', 'apr', 'may', 'jun', 'jul', 'aug', 'sep', 'oct', 'nov', 'dec'];
        $budgetEditItems = old('items', $budget->items->map(function ($item) use ($monthKeys) {
            $months = is_array($item->monthly_amounts) ? $item->monthly_amounts : [];
            $payload = [
                'account_id' => $item->account_id,
                'cost_center_id' => $item->cost_center_id,
                'target_total' => (float) $item->planned_amount,
            ];
            foreach ($monthKeys as $key) {
                $payload[$key] = (float) ($months[$key] ?? 0);
            }
            return $payload;
        })->all());
    @endphp

    <form method="POST" action="{{ route('finance.budgets.update', $budget) }}" class="space-y-6" x-data='budgetEditForm({
        accounts: @json($accountsTree),
        costCenters: @json($costCenters),
        initialItems: @json($budgetEditItems),
        monthKeys: @json($monthKeys),
        initialYear: @json((int) old('fiscal_year', $budget->fiscal_year)),
        initialEndDate: @json(old('end_date', $budget->end_date->format('Y-m-d'))),
    })' x-init="setup()">
        @csrf
        @method('PUT')
        <input type="hidden" name="start_date" :value="startDate">

        <section class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm">
            <h2 class="mb-4 text-2xl font-bold text-gray-900">بيانات الميزانية</h2>
            <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                <div class="space-y-1">
                    <label class="inline-flex items-center gap-1 text-sm font-medium text-gray-700">
                        <span>اسم الميزانية <span class="text-red-500">*</span></span>
                        <x-info field="budget_name" />
                    </label>
                    <input type="text" name="name" value="{{ old('name', $budget->name) }}" class="h-11 w-full rounded-lg border border-gray-200 bg-gray-50 px-3 text-sm focus:border-blue-500 focus:ring-blue-500">
                </div>
                <div class="space-y-1">
                    <label class="inline-flex items-center gap-1 text-sm font-medium text-gray-700">
                        <span>السنة المالية <span class="text-red-500">*</span></span>
                        <x-info field="fiscal_year" />
                    </label>
                    <select name="fiscal_year" x-model.number="fiscalYear" @change="syncDates()" class="h-11 w-full rounded-lg border border-gray-200 bg-gray-50 px-3 text-sm focus:border-blue-500 focus:ring-blue-500">
                        @foreach($fiscalYears as $year)
                            <option value="{{ $year }}">{{ $year }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="space-y-1">
                    <label class="inline-flex items-center gap-1 text-sm font-medium text-gray-700">
                        <span>تاريخ النهاية <span class="text-red-500">*</span></span>
                        <x-info field="budget_end_date" />
                    </label>
                    <input type="date" name="end_date" x-model="endDate" class="h-11 w-full rounded-lg border border-gray-200 bg-gray-50 px-3 text-sm focus:border-blue-500 focus:ring-blue-500">
                </div>
                <div class="space-y-1">
                    <label class="inline-flex items-center gap-1 text-sm font-medium text-gray-700">
                        <span>الوصف</span>
                        <x-info field="budget_description" />
                    </label>
                    <textarea name="description" rows="3" class="w-full rounded-lg border border-gray-200 bg-gray-50 px-3 py-2 text-sm focus:border-blue-500 focus:ring-blue-500">{{ old('description', $budget->description) }}</textarea>
                </div>
            </div>
        </section>

        <section class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm">
            <div class="mb-4 flex items-center justify-between gap-3">
                <h2 class="text-2xl font-bold text-gray-900">بنود الميزانية</h2>
                <button type="button" @click="addRow()" class="inline-flex items-center gap-2 rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">
                    <span class="text-base leading-none">+</span>
                    إضافة حساب
                </button>
            </div>

            <div class="overflow-x-auto rounded-lg border border-gray-100">
                <table class="w-full min-w-[1800px] text-sm">
                    <thead class="bg-gray-50 text-xs font-semibold text-gray-500">
                        <tr>
                            <th class="px-4 py-3 text-right">الحساب <x-info field="budget_account" /></th>
                            <th class="px-4 py-3 text-right">مركز التكلفة <x-info field="budget_cost_center" /></th>
                            <th class="px-4 py-3 text-right">يناير <x-info field="budget_monthly_distribution" /></th>
                            <th class="px-4 py-3 text-right">فبراير <x-info field="budget_monthly_distribution" /></th>
                            <th class="px-4 py-3 text-right">مارس <x-info field="budget_monthly_distribution" /></th>
                            <th class="px-4 py-3 text-right">أبريل <x-info field="budget_monthly_distribution" /></th>
                            <th class="px-4 py-3 text-right">مايو <x-info field="budget_monthly_distribution" /></th>
                            <th class="px-4 py-3 text-right">يونيو <x-info field="budget_monthly_distribution" /></th>
                            <th class="px-4 py-3 text-right">يوليو <x-info field="budget_monthly_distribution" /></th>
                            <th class="px-4 py-3 text-right">أغسطس <x-info field="budget_monthly_distribution" /></th>
                            <th class="px-4 py-3 text-right">سبتمبر <x-info field="budget_monthly_distribution" /></th>
                            <th class="px-4 py-3 text-right">أكتوبر <x-info field="budget_monthly_distribution" /></th>
                            <th class="px-4 py-3 text-right">نوفمبر <x-info field="budget_monthly_distribution" /></th>
                            <th class="px-4 py-3 text-right">ديسمبر <x-info field="budget_monthly_distribution" /></th>
                            <th class="px-4 py-3 text-right">إجمالي السطر <x-info field="budget_total_planned" /></th>
                            <th class="px-4 py-3 text-right">التوزيع المتساوي <x-info field="budget_equal_distribution" /></th>
                            <th class="px-4 py-3 text-left">حذف <x-info field="budget_actions" /></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <template x-for="(item, index) in items" :key="index">
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-3">
                                    <select class="h-10 w-60 rounded-lg border border-gray-200 bg-gray-50 px-3 text-sm focus:border-blue-500 focus:ring-blue-500"
                                            :name="`items[${index}][account_id]`"
                                            x-model="item.account_id">
                                        <option value="">اختر الحساب</option>
                                        <template x-for="acc in accounts" :key="acc.id">
                                            <option :value="String(acc.id)" x-text="acc.label"></option>
                                        </template>
                                    </select>
                                </td>
                                <td class="px-4 py-3">
                                    <select class="h-10 w-48 rounded-lg border border-gray-200 bg-gray-50 px-3 text-sm focus:border-blue-500 focus:ring-blue-500"
                                            :name="`items[${index}][cost_center_id]`"
                                            x-model="item.cost_center_id">
                                        <option value="">لا شيء</option>
                                        <template x-for="cc in costCenters" :key="cc.id">
                                            <option :value="String(cc.id)" x-text="`${cc.code} - ${cc.name}`"></option>
                                        </template>
                                    </select>
                                </td>
                                <template x-for="month in monthKeys" :key="month">
                                    <td class="px-2 py-3">
                                        <input type="number" inputmode="decimal"
                                               min="0"
                                               step="any"
                                               class="h-10 w-28 rounded-lg border border-gray-200 bg-gray-50 px-2 text-sm focus:border-blue-500 focus:ring-blue-500"
                                               :name="`items[${index}][${month}]`"
                                               x-model.number="item[month]">
                                    </td>
                                </template>
                                <td class="px-4 py-3 text-sm font-semibold text-blue-700" x-text="money(rowTotal(item))"></td>
                                <td class="px-4 py-3">
                                    <div class="flex items-center gap-2">
                                        <input type="number" inputmode="decimal" min="0" step="any" class="h-10 w-28 rounded-lg border border-gray-200 bg-gray-50 px-2 text-sm focus:border-blue-500 focus:ring-blue-500" x-model.number="item.target_total">
                                        <button type="button"
                                                @click="distributeEvenly(index)"
                                                class="inline-flex items-center rounded-lg border border-blue-200 bg-blue-50 px-3 py-2 text-xs font-semibold text-blue-700 hover:bg-blue-100">
                                            توزيع بالتساوي
                                        </button>
                                    </div>
                                </td>
                                <td class="px-4 py-3">
                                    <button type="button"
                                            @click="removeRow(index)"
                                            class="inline-flex h-8 w-8 items-center justify-center rounded-md border border-red-200 bg-white text-red-500 hover:bg-red-50 hover:text-red-600"
                                            title="حذف">
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

            <div class="erp-totals-left mt-4 flex items-center">
                <div class="rounded-lg border border-blue-100 bg-blue-50 px-4 py-2 text-sm font-semibold text-blue-800 text-right">
                    إجمالي المخطط <x-info field="budget_total_planned" />:
                    <span class="mr-1" x-text="money(plannedTotal())"></span>
                </div>
            </div>
        </section>

        <div class="flex justify-end gap-3">
            <a href="{{ route('finance.budgets.index') }}" class="rounded-lg border border-gray-200 bg-white px-5 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50">إلغاء</a>
            <button type="submit" class="inline-flex items-center gap-2 rounded-lg bg-blue-600 px-6 py-2.5 text-sm font-semibold text-white hover:bg-blue-700">حفظ التعديلات</button>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
    function budgetEditForm(config) {
        return {
            accounts: config.accounts || [],
            costCenters: config.costCenters || [],
            monthKeys: config.monthKeys || [],
            fiscalYear: Number(config.initialYear || new Date().getFullYear()),
            startDate: '',
            endDate: config.initialEndDate || '',
            items: [],

            setup() {
                this.syncDates();
                this.items = (config.initialItems || []).map((item) => this.normalizeItem(item));
                if (!this.items.length) this.addRow();
            },

            normalizeItem(item = {}) {
                const row = {
                    account_id: item.account_id ? String(item.account_id) : '',
                    cost_center_id: item.cost_center_id ? String(item.cost_center_id) : '',
                    target_total: Number(item.target_total || 0),
                };
                this.monthKeys.forEach((key) => {
                    row[key] = Number(item[key] || 0);
                });
                return row;
            },

            syncDates() {
                const year = Number(this.fiscalYear) || new Date().getFullYear();
                this.startDate = `${year}-01-01`;
                if (!this.endDate || !String(this.endDate).startsWith(String(year))) {
                    this.endDate = `${year}-12-31`;
                }
            },

            addRow() {
                this.items.push(this.normalizeItem());
            },

            removeRow(index) {
                if (this.items.length === 1) return;
                this.items.splice(index, 1);
            },

            rowTotal(item) {
                return this.monthKeys.reduce((sum, key) => sum + Number(item[key] || 0), 0);
            },

            plannedTotal() {
                return this.items.reduce((sum, item) => sum + this.rowTotal(item), 0);
            },

            distributeEvenly(index) {
                const row = this.items[index];
                const target = Number(row.target_total || 0);
                if (target <= 0) return;
                const base = Math.floor((target / 12) * 100) / 100;
                let consumed = 0;
                this.monthKeys.forEach((key, idx) => {
                    if (idx === this.monthKeys.length - 1) {
                        row[key] = Number((target - consumed).toFixed(2));
                    } else {
                        row[key] = base;
                        consumed += base;
                    }
                });
            },

            money(amount) {
                return `SAR ${Number(amount || 0).toFixed(2)}`;
            },
        };
    }
</script>
@endpush

