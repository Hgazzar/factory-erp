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

<div class="rounded-xl border border-teal-200 bg-white p-4 space-y-3">
    <div class="flex flex-wrap items-start justify-between gap-3">
        <div class="min-w-0 flex-1">
            <p class="text-sm font-bold text-teal-950">جدول أسعار الساعات <x-info field="nursery.settings_plan_hour_rates" /></p>
            <p class="text-xs text-teal-700/80 mt-1 leading-relaxed">
                للفائدة: باقة يوم بساعات محددة بسعر ثابت (مثل 4 ساعات = 1000، 8 ساعات = 1400) — وليس ضرب ساعة×عدد فقط.
                عند إضافة اشتراك يختار الموظف عدد الساعات فتُحسب القيمة بعد الضريبة تلقائياً.
            </p>
        </div>
        <button type="button" class="nursery-btn nursery-btn-soft text-xs py-1.5 shrink-0"
                @click="hourRates.push({ hours: '', amount: '' })">+ إضافة صف</button>
    </div>

    <div class="overflow-x-auto rounded-lg border border-teal-100">
        <table class="w-full min-w-[420px] text-sm">
            <thead>
                <tr class="bg-teal-50/80 text-teal-900">
                    <th class="px-3 py-2 text-right font-semibold w-[28%]">الساعات</th>
                    <th class="px-3 py-2 text-right font-semibold w-[32%]">السعر قبل الضريبة</th>
                    <th class="px-3 py-2 text-right font-semibold w-[28%]">بعد الضريبة</th>
                    <th class="px-3 py-2 text-center font-semibold w-[12%]"></th>
                </tr>
            </thead>
            <tbody>
                <template x-for="(row, index) in hourRates" :key="index">
                    <tr class="border-t border-teal-100">
                        <td class="px-3 py-2">
                            <input type="number" min="1" max="24" step="1" x-model.number="row.hours"
                                   :name="'hour_rates[' + index + '][hours]'"
                                   class="w-full rounded-lg border border-teal-200 px-2.5 py-1.5 tabular-nums" dir="ltr"
                                   placeholder="4">
                        </td>
                        <td class="px-3 py-2">
                            <input type="number" min="0" step="0.01" x-model.number="row.amount"
                                   :name="'hour_rates[' + index + '][amount]'"
                                   class="w-full rounded-lg border border-teal-200 px-2.5 py-1.5 tabular-nums" dir="ltr"
                                   placeholder="1000">
                        </td>
                        <td class="px-3 py-2">
                            <span class="inline-block w-full rounded-lg border border-teal-100 bg-teal-50/60 px-2.5 py-1.5 tabular-nums font-semibold text-teal-800"
                                  x-text="rowAfterTax(row)" dir="ltr"></span>
                        </td>
                        <td class="px-2 py-2 text-center">
                            <button type="button" class="text-xs font-semibold text-red-600 hover:underline px-1"
                                    @click="removeHourRow(index)"
                                    :disabled="hourRates.length <= 1 && !row.hours && !row.amount"
                                    :class="(hourRates.length <= 1 && !row.hours && !row.amount) ? 'opacity-40 cursor-not-allowed' : ''">
                                حذف
                            </button>
                        </td>
                    </tr>
                </template>
            </tbody>
        </table>
    </div>
    <p class="text-[11px] text-teal-700/70">اترك الجدول فارغاً إن كانت الخطة بسعر واحد فقط (القيمة الافتراضية أعلاه).</p>
</div>

<div class="flex flex-wrap gap-2 pt-2">
    <button type="submit" class="nursery-btn nursery-btn-primary" x-text="editPlan ? 'حفظ' : 'إضافة'"></button>
    <button type="button" @click="showAdd = false; editPlan = null" class="nursery-btn nursery-btn-soft">إلغاء</button>
</div>
