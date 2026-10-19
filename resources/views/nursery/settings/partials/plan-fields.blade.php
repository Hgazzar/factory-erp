<div>
    <label class="block text-sm font-semibold text-teal-950 mb-1">نوع الخطة * <x-info field="nursery.settings_plan_type" /></label>
    <x-custom-select name="plan_type"
        :options="$planTypeOptions"
        :value="old('plan_type', $plan->plan_type ?? 'custom')"
        empty-label="— النوع —"
        :searchable="false"
        :in-modal="true" />
    @error('plan_type')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
</div>
<div>
    <label class="block text-sm font-semibold text-teal-950 mb-1">اسم الخطة * <x-info field="nursery.settings_plan_name" /></label>
    <input type="text" name="name" required value="{{ old('name', $plan->name ?? '') }}" class="w-full rounded-lg border border-teal-200 px-3 py-2">
    @error('name')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
</div>
<div class="grid gap-4 sm:grid-cols-2">
    <div>
        <label class="block text-sm font-semibold text-teal-950 mb-1">قيمة افتراضية * <x-info field="nursery.settings_plan_amount" /></label>
        <input type="number" step="0.01" min="0" name="amount" required x-model.number="amount"
               value="{{ old('amount', $plan->amount ?? '') }}" class="w-full rounded-lg border border-teal-200 px-3 py-2 tabular-nums">
        <p class="text-[11px] text-teal-700/70 mt-1">تُستخدم إن لم يُحدد صف ساعات، أو كمرجع سريع.</p>
        @error('amount')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
    </div>
    <div>
        <label class="block text-sm font-semibold text-teal-950 mb-1">العملة <x-info field="nursery.settings_plan_currency" /></label>
        <x-custom-select name="currency_code"
            :options="$currencyOptions"
            :value="old('currency_code', $plan->currency_code ?? 'SAR')"
            :searchable="false"
            :in-modal="true" />
    </div>
</div>
<div class="grid gap-4 sm:grid-cols-2">
    <div>
        <label class="block text-sm font-semibold text-teal-950 mb-1">ضريبة القيمة المضافة % * <x-info field="nursery.settings_plan_vat" /></label>
        <input type="number" step="0.01" min="0" max="100" name="tax_rate" required x-model.number="tax"
               value="{{ old('tax_rate', $plan->tax_rate ?? 15) }}" class="w-full rounded-lg border border-teal-200 px-3 py-2 tabular-nums">
        @error('tax_rate')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
    </div>
    <div>
        <label class="block text-sm font-semibold text-teal-950 mb-1">القيمة بعد الضريبة <x-info field="nursery.settings_plan_after_tax" /></label>
        <input type="text" readonly :value="afterTax()" class="w-full rounded-lg border border-teal-100 bg-teal-50/50 px-3 py-2 tabular-nums font-semibold text-teal-800">
    </div>
</div>

<div class="rounded-xl border border-teal-100 bg-teal-50/30 p-3 space-y-3">
    <div class="flex flex-wrap items-center justify-between gap-2">
        <div>
            <p class="text-sm font-bold text-teal-950">جدول أسعار الساعات <x-info field="nursery.settings_plan_hour_rates" /></p>
            <p class="text-xs text-teal-700/80">أضف صفوفاً بحرية (مثال: 4 ساعات = 1000). الضريبة تُحسب من نسبة الخطة أعلاه.</p>
        </div>
        <button type="button" class="nursery-btn nursery-btn-soft text-xs py-1.5" @click="hourRates.push({ hours: '', amount: '' })">+ صف</button>
    </div>
    <template x-for="(row, index) in hourRates" :key="index">
        <div class="grid gap-2 sm:grid-cols-[1fr_1fr_auto] items-end">
            <div>
                <label class="block text-xs text-teal-800/80 mb-1">عدد الساعات</label>
                <input type="number" min="1" max="24" step="1" x-model="row.hours"
                       :name="'hour_rates[' + index + '][hours]'"
                       class="w-full rounded-lg border border-teal-200 px-3 py-2 text-sm tabular-nums" dir="ltr"
                       placeholder="4">
            </div>
            <div>
                <label class="block text-xs text-teal-800/80 mb-1">السعر قبل الضريبة</label>
                <input type="number" min="0" step="0.01" x-model="row.amount"
                       :name="'hour_rates[' + index + '][amount]'"
                       class="w-full rounded-lg border border-teal-200 px-3 py-2 text-sm tabular-nums" dir="ltr"
                       placeholder="1000">
                <p class="text-[11px] text-teal-700/70 mt-1" x-text="'بعد الضريبة: ' + rowAfterTax(row)"></p>
            </div>
            <button type="button" class="nursery-btn nursery-btn-soft text-xs py-2 text-red-700"
                    @click="if (hourRates.length > 1) hourRates.splice(index, 1); else { row.hours = ''; row.amount = ''; }">حذف</button>
        </div>
    </template>
</div>

<div class="flex flex-wrap gap-2 pt-2">
    <button type="submit" class="nursery-btn nursery-btn-primary" x-text="editPlan ? 'حفظ' : 'إضافة'"></button>
    <button type="button" @click="showAdd = false; editPlan = null" class="nursery-btn nursery-btn-soft">إلغاء</button>
</div>
