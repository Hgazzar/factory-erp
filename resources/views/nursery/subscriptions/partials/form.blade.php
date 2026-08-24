@php
    $plansJson = collect($planOptions)->map(fn ($p) => [
        'id' => $p['value'],
        'amount' => $p['amount_after_tax'],
    ])->values();
@endphp
<form method="post" action="{{ route('nursery.subscriptions.store') }}" class="space-y-4" id="nurserySubscriptionForm">
    @csrf

    <div>
        <label class="block text-sm font-semibold text-teal-950 mb-1">
            اسم الطفل <span class="text-red-600">*</span>
            <x-info field="nursery.sub_child" />
        </label>
        <x-custom-select name="child_id" :options="$childOptions" :value="old('child_id')" placeholder="اختر الطفل" :searchable="true" />
        @error('child_id')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
    </div>

    <div>
        <label class="block text-sm font-semibold text-teal-950 mb-1">
            اسم الخطة <span class="text-red-600">*</span>
            <x-info field="nursery.sub_plan" />
        </label>
        <x-custom-select name="plan_id" id="subscription_plan_select" :options="$planOptions" :value="old('plan_id')" placeholder="اختر الخطة" :searchable="true" />
        @error('plan_id')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
    </div>

    <div>
        <label class="block text-sm font-semibold text-teal-950 mb-1">
            القيمة بعد الضريبة
            <x-info field="nursery.sub_amount_after_tax" />
        </label>
        <input type="number" name="amount_after_tax" id="subscription_amount_after_tax" step="0.01" min="0"
               value="{{ old('amount_after_tax') }}"
               class="w-full rounded-lg border border-teal-200 px-3 py-2" dir="ltr" placeholder="تُملأ تلقائياً من الخطة">
        @error('amount_after_tax')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
    </div>

    <div x-data="{ showDiscount: {{ old('discount_amount') ? 'true' : 'false' }} }">
        <button type="button" @click="showDiscount = !showDiscount" class="text-sm font-semibold text-teal-600 hover:underline">+ إضافة خصم</button>
        <div x-show="showDiscount" x-cloak class="mt-2">
            <label class="block text-sm font-semibold text-teal-950 mb-1">قيمة الخصم <x-info field="nursery.sub_discount" /></label>
            <input type="number" name="discount_amount" step="0.01" min="0" value="{{ old('discount_amount', 0) }}"
                   class="w-full rounded-lg border border-teal-200 px-3 py-2" dir="ltr">
        </div>
    </div>

    <div>
        <label class="block text-sm font-semibold text-teal-950 mb-1">ملاحظة <x-info field="nursery.sub_notes" /></label>
        <textarea name="notes" rows="3" maxlength="500" placeholder="اكتب شيئاً..."
                  class="w-full rounded-lg border border-teal-200 px-3 py-2 text-sm">{{ old('notes') }}</textarea>
        @error('notes')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
    </div>

    <div class="grid grid-cols-2 gap-3">
        <div>
            <label class="block text-sm font-semibold text-teal-950 mb-1">تاريخ البداية <span class="text-red-600">*</span></label>
            <input type="date" name="starts_on" value="{{ old('starts_on', now()->format('Y-m-d')) }}" required
                   class="w-full rounded-lg border border-teal-200 px-3 py-2">
            @error('starts_on')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
        </div>
        <div>
            <label class="block text-sm font-semibold text-teal-950 mb-1">تاريخ الانتهاء <span class="text-red-600">*</span></label>
            <input type="date" name="ends_on" value="{{ old('ends_on') }}" required
                   class="w-full rounded-lg border border-teal-200 px-3 py-2">
            @error('ends_on')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
        </div>
    </div>

    <div class="flex items-center gap-3">
        <label class="nursery-switch shrink-0">
            <input type="checkbox" name="is_paid" value="1" class="nursery-switch-input" @checked(old('is_paid'))>
            <span class="nursery-switch-track"></span>
        </label>
        <span class="text-sm font-semibold text-teal-950">الاشتراك مدفوع <x-info field="nursery.sub_is_paid" /></span>
    </div>

    <div class="flex gap-2 justify-end pt-2">
        <button type="button" @click="showAdd = false" class="nursery-btn nursery-btn-soft">إلغاء</button>
        <button type="submit" class="nursery-btn nursery-btn-primary">إضافة اشتراك</button>
    </div>
</form>

@push('scripts')
<script>
(function () {
    var plans = @json($plansJson);
    var amountInput = document.getElementById('subscription_amount_after_tax');

    function applyPlanAmount(planId) {
        if (!amountInput) return;
        var match = plans.find(function (p) { return String(p.id) === String(planId); });
        if (match) amountInput.value = String(match.amount);
    }

    document.addEventListener('custom-select-change', function (e) {
        if (e.detail && e.detail.name === 'plan_id') {
            applyPlanAmount(e.detail.value);
        }
    });

    var hidden = document.querySelector('input[name="plan_id"]');
    if (hidden && hidden.value) applyPlanAmount(hidden.value);
})();
</script>
@endpush
