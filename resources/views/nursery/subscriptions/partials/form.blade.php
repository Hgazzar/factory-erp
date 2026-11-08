@php
    $plansJson = collect($planOptions)->map(fn ($p) => [
        'id' => $p['value'],
        'amount' => $p['amount_after_tax'],
        'plan_type' => $p['plan_type'] ?? 'custom',
        'hour_rates' => $p['hour_rates'] ?? [],
    ])->values();
    $childrenJson = collect($childOptions)->map(fn ($c) => [
        'id' => $c['value'],
        'name' => $c['label'] ?? '',
        'guardian_name' => $c['guardian_name'] ?? '',
        'guardian_phone' => $c['guardian_phone'] ?? '',
        'invite_url' => $c['invite_url'] ?? '',
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
        <div x-show="showDiscount" x-cloak class="mt-2 space-y-2">
            <div>
                <label class="block text-sm font-semibold text-teal-950 mb-1">قيمة الخصم <x-info field="nursery.sub_discount" /></label>
                <input type="number" name="discount_amount" id="subscription_discount_amount" step="0.01" min="0"
                       value="{{ old('discount_amount', 0) }}"
                       class="w-full rounded-lg border border-teal-200 px-3 py-2" dir="ltr">
            </div>
            <div class="rounded-lg border border-teal-100 bg-teal-50/60 px-3 py-2">
                <p class="text-xs font-semibold text-teal-800 mb-0.5">
                    المبلغ بعد الخصم
                    <x-info field="nursery.sub_amount" />
                </p>
                <p id="subscription_final_amount" class="text-base font-bold tabular-nums text-teal-950" dir="ltr">—</p>
            </div>
            <div id="subscription_discount_whatsapp_wrap" class="hidden space-y-2 rounded-lg border border-emerald-100 bg-emerald-50/50 px-3 py-2">
                <label class="block text-xs font-semibold text-emerald-900">
                    رسالة واتساب لولي الأمر
                    <x-info field="nursery.sub_discount_whatsapp" />
                </label>
                <textarea id="subscription_discount_whatsapp_message" rows="5"
                          class="w-full rounded-lg border border-emerald-200 bg-white px-3 py-2 text-sm"
                          placeholder="اكتب أو عدّل الرسالة هنا قبل فتح واتساب…"></textarea>
                <p id="subscription_discount_whatsapp_hint" class="text-[11px] text-emerald-800/80"></p>
                <a id="subscription_discount_whatsapp_btn" href="#" target="_blank" rel="noopener"
                   class="nursery-btn nursery-btn-primary text-xs py-1.5 inline-flex items-center gap-1 pointer-events-none opacity-50">
                    فتح واتساب
                </a>
            </div>
        </div>
    </div>

    <div id="subscription_guardian_whatsapp_wrap" class="hidden space-y-2 rounded-xl border border-emerald-100 bg-emerald-50/40 p-3">
        <p class="text-sm font-bold text-emerald-950">
            واتساب ولي الأمر
            <x-info field="nursery.sub_guardian_whatsapp" />
        </p>
        <textarea id="subscription_guardian_whatsapp_message" rows="5"
                  class="w-full rounded-lg border border-emerald-200 bg-white px-3 py-2 text-sm"
                  placeholder="اكتب رسالتك هنا — رابط البوابة يُضاف تلقائياً…"></textarea>
        <p id="subscription_guardian_whatsapp_hint" class="text-[11px] text-emerald-800/80"></p>
        <a id="subscription_guardian_whatsapp_btn" href="#" target="_blank" rel="noopener"
           class="nursery-btn nursery-btn-primary text-xs py-1.5 inline-flex items-center gap-1 pointer-events-none opacity-50">
            فتح واتساب
        </a>
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
    var children = @json($childrenJson);
    var amountInput = document.getElementById('subscription_amount_after_tax');
    var discountInput = document.getElementById('subscription_discount_amount');
    var finalAmountEl = document.getElementById('subscription_final_amount');
    var startsInput = document.getElementById('subscription_starts_on');
    var endsInput = document.getElementById('subscription_ends_on');
    var hoursWrap = document.getElementById('subscription_hours_wrap');
    var hoursInput = document.getElementById('subscription_hours');
    var hoursChips = document.getElementById('subscription_hours_chips');
    var discountWaWrap = document.getElementById('subscription_discount_whatsapp_wrap');
    var discountWaMessage = document.getElementById('subscription_discount_whatsapp_message');
    var discountWaHint = document.getElementById('subscription_discount_whatsapp_hint');
    var discountWaBtn = document.getElementById('subscription_discount_whatsapp_btn');
    var guardianWaWrap = document.getElementById('subscription_guardian_whatsapp_wrap');
    var guardianWaMessage = document.getElementById('subscription_guardian_whatsapp_message');
    var guardianWaHint = document.getElementById('subscription_guardian_whatsapp_hint');
    var guardianWaBtn = document.getElementById('subscription_guardian_whatsapp_btn');
    var endsTouched = {{ old('ends_on') ? 'true' : 'false' }};
    var oldHours = @json(old('hours'));
    var discountDraftTouched = false;
    var guardianDraftTouched = false;

    function money(n) {
        return Number(n || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }

    function waDigits(phone) {
        var digits = String(phone || '').replace(/\D+/g, '');
        if (!digits) return null;
        if (digits.indexOf('00') === 0) digits = digits.slice(2);
        if (digits.charAt(0) === '0') digits = '966' + digits.replace(/^0+/, '');
        else if (digits.indexOf('966') !== 0 && digits.length === 9) digits = '966' + digits;
        return digits || null;
    }

    function waUrl(phone, text) {
        var digits = waDigits(phone);
        if (!digits) return null;
        var url = 'https://wa.me/' + digits;
        if (text) url += '?text=' + encodeURIComponent(text);
        return url;
    }

    function currentChild() {
        var hidden = document.querySelector('#nurserySubscriptionForm input[name="child_id"]');
        var id = hidden ? hidden.value : '';
        return children.find(function (c) { return String(c.id) === String(id); }) || null;
    }

    function portalBlock(child) {
        var inviteUrl = child && child.invite_url ? child.invite_url : '';
        if (!inviteUrl) return '';
        return '\nرابط دخول بوابة ولي الأمر:\n' + inviteUrl
            + '\nبعد فتح الرابط أدخل رقم جوالك المسجّل لدى الحضانة.';
    }

    function discountDraft(child) {
        var amount = parseFloat(amountInput && amountInput.value !== '' ? amountInput.value : '0') || 0;
        var discount = parseFloat(discountInput && discountInput.value !== '' ? discountInput.value : '0') || 0;
        var final = Math.max(0, Math.round((amount - discount) * 100) / 100);
        var guardian = (child && child.guardian_name) ? child.guardian_name : 'ولي الأمر';
        var childName = (child && child.name) ? child.name : 'طفلك';
        return 'مرحباً ' + guardian + '،\n'
            + 'تم تطبيق خصم ' + money(discount) + ' ر.س على اشتراك ' + childName + '.\n'
            + 'المبلغ بعد الخصم: ' + money(final) + ' ر.س.'
            + portalBlock(child)
            + '\n\n— يمكنك تعديل الرسالة أو إضافة ملاحظة قبل الإرسال —';
    }

    function guardianDraft(child) {
        var guardian = (child && child.guardian_name) ? child.guardian_name : 'ولي الأمر';
        return 'مرحباً ' + guardian + '،\n\n'
            + portalBlock(child)
            + '\n\n— اكتب رسالتك في المساحة الفارغة أعلاه إن رغبت، ثم أرسل —';
    }

    function setWaButton(btn, url) {
        if (!btn) return;
        if (url) {
            btn.href = url;
            btn.classList.remove('pointer-events-none', 'opacity-50');
        } else {
            btn.href = '#';
            btn.classList.add('pointer-events-none', 'opacity-50');
        }
    }

    function syncWhatsAppDrafts() {
        var child = currentChild();
        var phone = child ? child.guardian_phone : '';
        var hasPhone = !!waDigits(phone);
        var discount = parseFloat(discountInput && discountInput.value !== '' ? discountInput.value : '0') || 0;

        if (guardianWaWrap) {
            if (hasPhone) {
                guardianWaWrap.classList.remove('hidden');
                if (guardianWaMessage && !guardianDraftTouched) {
                    guardianWaMessage.value = guardianDraft(child);
                }
                if (guardianWaHint) {
                    guardianWaHint.textContent = 'الرقم: ' + phone + ' — عدّل الرسالة ثم افتح واتساب.';
                }
                setWaButton(guardianWaBtn, waUrl(phone, guardianWaMessage ? guardianWaMessage.value : ''));
            } else {
                guardianWaWrap.classList.add('hidden');
                setWaButton(guardianWaBtn, null);
            }
        }

        if (discountWaWrap) {
            if (hasPhone && discount > 0) {
                discountWaWrap.classList.remove('hidden');
                if (discountWaMessage && !discountDraftTouched) {
                    discountWaMessage.value = discountDraft(child);
                }
                if (discountWaHint) {
                    discountWaHint.textContent = 'رسالة الخصم تتضمن رابط البوابة — عدّلها قبل الإرسال.';
                }
                setWaButton(discountWaBtn, waUrl(phone, discountWaMessage ? discountWaMessage.value : ''));
            } else {
                discountWaWrap.classList.add('hidden');
                setWaButton(discountWaBtn, null);
            }
        }
    }

    function syncFinalAmount() {
        if (!finalAmountEl) return;
        var amount = parseFloat(amountInput && amountInput.value !== '' ? amountInput.value : '0') || 0;
        var discount = parseFloat(discountInput && discountInput.value !== '' ? discountInput.value : '0') || 0;
        var final = Math.max(0, Math.round((amount - discount) * 100) / 100);
        finalAmountEl.textContent = final.toLocaleString('en-US', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        });
        syncWhatsAppDrafts();
    }

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
        syncFinalAmount();
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
        if (e.detail && e.detail.name === 'child_id') {
            discountDraftTouched = false;
            guardianDraftTouched = false;
            syncWhatsAppDrafts();
        }
    });

    if (discountWaMessage) {
        discountWaMessage.addEventListener('input', function () {
            discountDraftTouched = true;
            syncWhatsAppDrafts();
        });
    }
    if (guardianWaMessage) {
        guardianWaMessage.addEventListener('input', function () {
            guardianDraftTouched = true;
            syncWhatsAppDrafts();
        });
    }

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

    if (amountInput) {
        amountInput.addEventListener('input', syncFinalAmount);
        amountInput.addEventListener('change', syncFinalAmount);
    }
    if (discountInput) {
        discountInput.addEventListener('input', syncFinalAmount);
        discountInput.addEventListener('change', syncFinalAmount);
    }

    var hidden = document.querySelector('#nurserySubscriptionForm input[name="plan_id"]');
    if (hidden && hidden.value) {
        applyPlan(hidden.value, !endsTouched, oldHours);
    } else {
        syncFinalAmount();
    }
})();
</script>
@endpush
