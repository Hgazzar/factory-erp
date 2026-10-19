@php
    $plansJson = collect($planOptions)->map(fn ($p) => [
        'id' => $p['value'],
        'amount' => $p['amount_after_tax'],
        'plan_type' => $p['plan_type'] ?? 'custom',
        'hour_rates' => $p['hour_rates'] ?? [],
    ])->values();
    $defaultStarts = old('starts_on', '');
    $hourOptions = [];
@endphp
<form method="post" action="{{ route('nursery.subscriptions.store') }}" class="space-y-4" id="nurserySubscriptionForm">
    @csrf

    <div>
        <label class="block text-sm font-semibold text-teal-950 mb-1">
            اسم الطفل <span class="text-red-600">*</span>
            <x-info field="nursery.sub_child" />
        </label>
        <x-custom-select name="child_id" :options="$childOptions" :value="old('child_id')"
            empty-label="— الطفل —" placeholder="ابحث عن الطفل..." :searchable="true" :in-modal="true" />
        @error('child_id')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
    </div>

    <div>
        <label class="block text-sm font-semibold text-teal-950 mb-1">
            اسم الخطة <span class="text-red-600">*</span>
            <x-info field="nursery.sub_plan" />
        </label>
        <x-custom-select name="plan_id" id="subscription_plan_select" :options="$planOptions" :value="old('plan_id')"
            empty-label="— الخطة —" placeholder="ابحث عن الخطة..." :searchable="true" :in-modal="true" />
        @error('plan_id')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
    </div>

    <div id="subscription_hours_wrap" class="hidden">
        <label class="block text-sm font-semibold text-teal-950 mb-1">
            عدد الساعات <span class="text-red-600">*</span>
            <x-info field="nursery.sub_hours" />
        </label>
        <input type="hidden" name="hours" id="subscription_hours" value="{{ old('hours') }}">
        <div id="subscription_hours_chips" class="flex flex-wrap gap-2"></div>
        <p class="text-xs text-teal-700/70 mt-1">اختر عدد الساعات من جدول أسعار الخطة.</p>
        @error('hours')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
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

    <div class="space-y-1">
        <div class="grid grid-cols-2 gap-3">
            <div>
                <label class="block text-sm font-semibold text-teal-950 mb-1">تاريخ البداية <span class="text-red-600">*</span></label>
                <input type="date" name="starts_on" id="subscription_starts_on" value="{{ $defaultStarts }}"
                       min="{{ now()->format('Y-m-d') }}" required
                       class="w-full rounded-lg border border-teal-200 px-3 py-2">
                @error('starts_on')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="block text-sm font-semibold text-teal-950 mb-1">تاريخ الانتهاء <span class="text-red-600">*</span></label>
                <input type="date" name="ends_on" id="subscription_ends_on" value="{{ old('ends_on') }}" required
                       class="w-full rounded-lg border border-teal-200 px-3 py-2">
                @error('ends_on')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
            </div>
        </div>
        <p class="text-xs text-teal-700/70">يُحسب تلقائياً حسب الخطة — قابل للتعديل</p>
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
    var startsInput = document.getElementById('subscription_starts_on');
    var endsInput = document.getElementById('subscription_ends_on');
    var hoursWrap = document.getElementById('subscription_hours_wrap');
    var hoursInput = document.getElementById('subscription_hours');
    var hoursChips = document.getElementById('subscription_hours_chips');
    var endsTouched = {{ old('ends_on') ? 'true' : 'false' }};
    var oldHours = @json(old('hours'));

    function pad(n) { return String(n).padStart(2, '0'); }

    function toIsoDate(d) {
        return d.getFullYear() + '-' + pad(d.getMonth() + 1) + '-' + pad(d.getDate());
    }

    function parseIsoDate(value) {
        if (!value) return null;
        var parts = String(value).split('-');
        if (parts.length !== 3) return null;
        var d = new Date(Number(parts[0]), Number(parts[1]) - 1, Number(parts[2]));
        return isNaN(d.getTime()) ? null : d;
    }

    function addMonths(date, months) {
        var d = new Date(date.getFullYear(), date.getMonth(), date.getDate());
        var day = d.getDate();
        d.setMonth(d.getMonth() + months);
        if (d.getDate() < day) {
            d.setDate(0);
        }
        return d;
    }

    function endsOnForType(starts, planType) {
        if (!starts) return null;
        switch (planType) {
            case 'hourly':
                return new Date(starts.getFullYear(), starts.getMonth(), starts.getDate());
            case 'daily': {
                var day = new Date(starts.getFullYear(), starts.getMonth(), starts.getDate());
                day.setDate(day.getDate() + 1);
                return day;
            }
            case 'weekly': {
                var week = new Date(starts.getFullYear(), starts.getMonth(), starts.getDate());
                week.setDate(week.getDate() + 7);
                return week;
            }
            case 'monthly':
                return addMonths(starts, 1);
            case 'term':
                return addMonths(starts, 3);
            default:
                return null;
        }
    }

    function findPlan(planId) {
        return plans.find(function (p) { return String(p.id) === String(planId); }) || null;
    }

    function currentPlan() {
        var hidden = document.querySelector('#nurserySubscriptionForm input[name="plan_id"]');
        var planId = hidden ? hidden.value : '';
        return findPlan(planId);
    }

    function selectedHoursAmount(plan) {
        if (!plan || !hoursInput || !hoursInput.value) return null;
        var rates = Array.isArray(plan.hour_rates) ? plan.hour_rates : [];
        var match = rates.find(function (r) { return String(r.hours) === String(hoursInput.value); });
        return match ? String(match.amount_after_tax) : null;
    }

    function paintHourChips(plan, preferredHours) {
        if (!hoursChips) return;
        hoursChips.querySelectorAll('button').forEach(function (btn) {
            var active = String(btn.dataset.hours) === String(hoursInput.value);
            btn.className = active
                ? 'nursery-btn nursery-btn-primary text-xs py-1.5'
                : 'nursery-btn nursery-btn-soft text-xs py-1.5';
        });
    }

    function syncHoursOptions(plan, preferredHours) {
        if (!hoursWrap || !hoursInput || !hoursChips) return;
        var rates = (plan && Array.isArray(plan.hour_rates)) ? plan.hour_rates : [];
        hoursChips.innerHTML = '';

        if (!rates.length) {
            hoursWrap.classList.add('hidden');
            hoursInput.value = '';
            return;
        }

        hoursWrap.classList.remove('hidden');
        var pick = preferredHours != null && preferredHours !== ''
            ? String(preferredHours)
            : (rates.length === 1 ? String(rates[0].hours) : '');
        if (pick && !rates.some(function (r) { return String(r.hours) === pick; })) {
            pick = '';
        }
        hoursInput.value = pick;

        rates.forEach(function (rate) {
            var btn = document.createElement('button');
            btn.type = 'button';
            btn.dataset.hours = String(rate.hours);
            btn.textContent = rate.hours + ' ساعات';
            btn.addEventListener('click', function () {
                hoursInput.value = String(rate.hours);
                paintHourChips(plan);
                applyAmountFromHoursOrPlan(plan);
            });
            hoursChips.appendChild(btn);
        });
        paintHourChips(plan);
    }

    function applyAmountFromHoursOrPlan(plan) {
        if (!amountInput || !plan) return;
        var fromHours = selectedHoursAmount(plan);
        amountInput.value = fromHours != null ? fromHours : String(plan.amount);
    }

    function applyEndsOn(force, planOverride) {
        if (!endsInput || !startsInput) return;
        if (endsTouched && !force) return;
        var plan = planOverride || currentPlan();
        if (!plan) return;
        var starts = parseIsoDate(startsInput.value);
        if (!starts) return;
        var ends = endsOnForType(starts, plan.plan_type);
        if (ends) {
            endsInput.value = toIsoDate(ends);
        }
    }

    function applyPlan(planId, forceEnds, preferredHours) {
        var plan = findPlan(planId);
        if (!plan) return;
        syncHoursOptions(plan, preferredHours);
        applyAmountFromHoursOrPlan(plan);
        if (plan.plan_type === 'custom') {
            return;
        }
        endsTouched = false;
        applyEndsOn(forceEnds !== false, plan);
    }

    document.addEventListener('custom-select-change', function (e) {
        if (e.detail && e.detail.name === 'plan_id') {
            applyPlan(e.detail.value, true, null);
        }
    });

    if (startsInput) {
        startsInput.addEventListener('change', function () {
            applyEndsOn(true);
        });
        startsInput.addEventListener('input', function () {
            applyEndsOn(true);
        });
    }

    if (endsInput) {
        endsInput.addEventListener('change', function () {
            endsTouched = true;
        });
        endsInput.addEventListener('input', function () {
            endsTouched = true;
        });
    }

    var hidden = document.querySelector('#nurserySubscriptionForm input[name="plan_id"]');
    if (hidden && hidden.value) {
        applyPlan(hidden.value, !endsTouched, oldHours);
    }
})();
</script>
@endpush
