<div>
    <label class="block text-sm font-semibold text-orange-950 mb-1">نوع الخطة * <x-info field="nursery.settings_plan_type" /></label>
    <x-custom-select name="plan_type"
        :options="$planTypeOptions"
        :value="old('plan_type', $plan->plan_type ?? 'custom')"
        empty-label="— اختر النوع —"
        :searchable="false" />
    @error('plan_type')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
</div>
<div>
    <label class="block text-sm font-semibold text-orange-950 mb-1">اسم الخطة * <x-info field="nursery.settings_plan_name" /></label>
    <input type="text" name="name" required value="{{ old('name', $plan->name ?? '') }}" class="w-full rounded-lg border border-orange-200 px-3 py-2">
    @error('name')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
</div>
<div class="grid gap-4 sm:grid-cols-2">
    <div>
        <label class="block text-sm font-semibold text-orange-950 mb-1">قيمة الاشتراك * <x-info field="nursery.settings_plan_amount" /></label>
        <input type="number" step="0.01" min="0" name="amount" required x-model.number="amount"
               value="{{ old('amount', $plan->amount ?? '') }}" class="w-full rounded-lg border border-orange-200 px-3 py-2 tabular-nums">
        @error('amount')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
    </div>
    <div>
        <label class="block text-sm font-semibold text-orange-950 mb-1">العملة <x-info field="nursery.settings_plan_currency" /></label>
        <x-custom-select name="currency_code"
            :options="$currencyOptions"
            :value="old('currency_code', $plan->currency_code ?? 'SAR')"
            :searchable="false" />
    </div>
</div>
<div class="grid gap-4 sm:grid-cols-2">
    <div>
        <label class="block text-sm font-semibold text-orange-950 mb-1">ضريبة القيمة المضافة % * <x-info field="nursery.settings_plan_vat" /></label>
        <input type="number" step="0.01" min="0" max="100" name="tax_rate" required x-model.number="tax"
               value="{{ old('tax_rate', $plan->tax_rate ?? 15) }}" class="w-full rounded-lg border border-orange-200 px-3 py-2 tabular-nums">
        @error('tax_rate')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
    </div>
    <div>
        <label class="block text-sm font-semibold text-orange-950 mb-1">القيمة بعد الضريبة <x-info field="nursery.settings_plan_after_tax" /></label>
        <input type="text" readonly :value="afterTax()" class="w-full rounded-lg border border-orange-100 bg-orange-50/50 px-3 py-2 tabular-nums font-semibold text-orange-800">
    </div>
</div>
<div class="flex flex-wrap gap-2 pt-2">
    <button type="submit" class="nursery-btn nursery-btn-primary" x-text="editPlan ? 'حفظ' : 'إضافة'"></button>
    <button type="button" @click="showAdd = false; editPlan = null" class="nursery-btn nursery-btn-soft">إلغاء</button>
</div>
