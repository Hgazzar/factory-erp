@php
    use App\Support\NurseryChildDailyActivityCatalog;
    $activityDate = $activityDate ?? now()->toDateString();
    $canManageChildActivity = $canManageChildActivity ?? false;
    $todaysActivities = $todaysActivities ?? collect();
    $dailySummary = $dailySummary ?? [];
@endphp

<section class="nursery-card p-4 space-y-4">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <h2 class="font-bold text-orange-950">
                يوم الطفل
                <x-info field="nursery.child_daily_activity" />
            </h2>
            <p class="text-sm text-orange-800/80 mt-1">تسجيل سريع لما حدث اليوم — يظهر لولي الأمر ما هو مخصّص للبوابة فقط.</p>
        </div>
        <form method="get" action="{{ route('nursery.children.show', $child) }}" class="flex items-end gap-2">
            <div>
                <label class="block text-xs font-semibold text-orange-950 mb-1">التاريخ <x-info field="nursery.child_daily_activity_date" /></label>
                <input type="date" name="date" value="{{ $activityDate }}" max="{{ now()->toDateString() }}"
                       class="rounded-lg border border-orange-200 px-3 py-2 text-sm">
            </div>
            <button type="submit" class="nursery-btn nursery-btn-soft text-sm py-2">عرض</button>
        </form>
    </div>

    @if($dailySummary !== [])
        <div class="rounded-xl bg-orange-50 border border-orange-100 p-3 space-y-2 text-sm">
            @foreach($dailySummary as $group)
                <p>
                    <span class="font-bold text-orange-950">{{ $group['label'] }}:</span>
                    <span class="text-orange-800">{{ implode(' · ', $group['lines']) }}</span>
                </p>
            @endforeach
        </div>
    @endif

    @if($canManageChildActivity && $child->isActive())
        <div class="grid gap-3 sm:grid-cols-2">
            <form method="post" action="{{ route('nursery.children.daily-activities.store', $child) }}" class="rounded-xl border border-orange-100 p-3 space-y-2">
                @csrf
                <input type="hidden" name="activity_date" value="{{ $activityDate }}">
                <input type="hidden" name="type" value="meal">
                <p class="text-sm font-bold text-orange-950">وجبة <x-info field="nursery.child_daily_meal" /></p>
                <x-custom-select name="meal" id="daily_meal_kind" :options="NurseryChildDailyActivityCatalog::selectOptions('meal', 'meal')" :searchable="false" empty-label="نوع الوجبة" />
                <x-custom-select name="amount" id="daily_meal_amount" :options="NurseryChildDailyActivityCatalog::selectOptions('meal', 'amount')" :searchable="false" empty-label="الكمية" />
                <input type="text" name="note" maxlength="500" placeholder="ملاحظة اختيارية"
                       class="w-full rounded-lg border border-orange-200 px-3 py-2 text-sm">
                <button type="submit" class="nursery-btn nursery-btn-primary text-xs py-1.5 w-full">حفظ الوجبة</button>
            </form>

            <form method="post" action="{{ route('nursery.children.daily-activities.store', $child) }}" class="rounded-xl border border-orange-100 p-3 space-y-2">
                @csrf
                <input type="hidden" name="activity_date" value="{{ $activityDate }}">
                <input type="hidden" name="type" value="nap">
                <p class="text-sm font-bold text-orange-950">قيلولة <x-info field="nursery.child_daily_nap" /></p>
                <div class="grid grid-cols-2 gap-2">
                    <div>
                        <label class="block text-xs text-orange-800/80 mb-1">من</label>
                        <input type="time" name="started_at" required class="w-full rounded-lg border border-orange-200 px-3 py-2 text-sm">
                    </div>
                    <div>
                        <label class="block text-xs text-orange-800/80 mb-1">إلى</label>
                        <input type="time" name="ended_at" class="w-full rounded-lg border border-orange-200 px-3 py-2 text-sm">
                    </div>
                </div>
                <input type="text" name="note" maxlength="500" placeholder="ملاحظة اختيارية"
                       class="w-full rounded-lg border border-orange-200 px-3 py-2 text-sm">
                <button type="submit" class="nursery-btn nursery-btn-primary text-xs py-1.5 w-full">حفظ القيلولة</button>
            </form>

            <form method="post" action="{{ route('nursery.children.daily-activities.store', $child) }}" class="rounded-xl border border-orange-100 p-3 space-y-2">
                @csrf
                <input type="hidden" name="activity_date" value="{{ $activityDate }}">
                <input type="hidden" name="type" value="diaper">
                <p class="text-sm font-bold text-orange-950">حفاض <x-info field="nursery.child_daily_diaper" /></p>
                <x-custom-select name="change" id="daily_diaper_change" :options="NurseryChildDailyActivityCatalog::selectOptions('diaper', 'change')" :searchable="false" empty-label="الحالة" />
                <button type="submit" class="nursery-btn nursery-btn-primary text-xs py-1.5 w-full">حفظ</button>
            </form>

            <form method="post" action="{{ route('nursery.children.daily-activities.store', $child) }}" class="rounded-xl border border-orange-100 p-3 space-y-2">
                @csrf
                <input type="hidden" name="activity_date" value="{{ $activityDate }}">
                <input type="hidden" name="type" value="toilet">
                <p class="text-sm font-bold text-orange-950">الحمام <x-info field="nursery.child_daily_toilet" /></p>
                <x-custom-select name="result" id="daily_toilet_result" :options="NurseryChildDailyActivityCatalog::selectOptions('toilet', 'result')" :searchable="false" empty-label="النتيجة" />
                <button type="submit" class="nursery-btn nursery-btn-primary text-xs py-1.5 w-full">حفظ</button>
            </form>

            <form method="post" action="{{ route('nursery.children.daily-activities.store', $child) }}" class="rounded-xl border border-orange-100 p-3 space-y-2">
                @csrf
                <input type="hidden" name="activity_date" value="{{ $activityDate }}">
                <input type="hidden" name="type" value="mood">
                <p class="text-sm font-bold text-orange-950">المزاج <x-info field="nursery.child_daily_mood" /></p>
                <x-custom-select name="mood" id="daily_mood" :options="NurseryChildDailyActivityCatalog::selectOptions('mood', 'mood')" :searchable="false" empty-label="اختر المزاج" />
                <button type="submit" class="nursery-btn nursery-btn-primary text-xs py-1.5 w-full">حفظ المزاج</button>
            </form>

            <form method="post" action="{{ route('nursery.children.daily-activities.store', $child) }}" class="rounded-xl border border-orange-100 p-3 space-y-2">
                @csrf
                <input type="hidden" name="activity_date" value="{{ $activityDate }}">
                <input type="hidden" name="type" value="activity">
                <p class="text-sm font-bold text-orange-950">نشاط <x-info field="nursery.child_daily_play" /></p>
                <input type="text" name="title" maxlength="80" required placeholder="مثال: رسم حر"
                       class="w-full rounded-lg border border-orange-200 px-3 py-2 text-sm">
                <input type="text" name="note" maxlength="500" placeholder="ملاحظة اختيارية"
                       class="w-full rounded-lg border border-orange-200 px-3 py-2 text-sm">
                <button type="submit" class="nursery-btn nursery-btn-primary text-xs py-1.5 w-full">حفظ النشاط</button>
            </form>

            @if($child->relationLoaded('medications') ? $child->medications->isNotEmpty() : $child->medications()->exists())
                @php
                    $medicationOptions = $child->medications->map(fn ($m) => [
                        'value' => (string) $m->id,
                        'label' => trim($m->name.($m->dosage ? ' — '.$m->dosage : '')),
                    ])->values()->all();
                @endphp
                <form method="post" action="{{ route('nursery.children.daily-activities.store', $child) }}"
                      class="rounded-xl border border-amber-200 bg-amber-50/40 p-3 space-y-2 sm:col-span-2">
                    @csrf
                    <input type="hidden" name="activity_date" value="{{ $activityDate }}">
                    <input type="hidden" name="type" value="medication">
                    <p class="text-sm font-bold text-orange-950">جرعة دواء <x-info field="nursery.child_daily_medication" /></p>
                    <div>
                        <label class="block text-xs text-orange-800/80 mb-1">الدواء <x-info field="nursery.child_daily_medication_pick" /></label>
                        <x-custom-select name="medication_id" id="daily_medication_id" :options="$medicationOptions" :searchable="false" empty-label="اختر الدواء" />
                    </div>
                    <div>
                        <label class="block text-xs text-orange-800/80 mb-1">الحالة <x-info field="nursery.child_daily_medication_status" /></label>
                        <x-custom-select name="status" id="daily_medication_status" :options="NurseryChildDailyActivityCatalog::selectOptions('medication', 'status')" :searchable="false" empty-label="حالة الجرعة" />
                    </div>
                    <div class="grid grid-cols-2 gap-2">
                        <div>
                            <label class="block text-xs text-orange-800/80 mb-1">الوقت <x-info field="nursery.child_daily_medication_time" /></label>
                            <input type="time" name="given_at" required class="w-full rounded-lg border border-orange-200 px-3 py-2 text-sm">
                        </div>
                        <div>
                            <label class="block text-xs text-orange-800/80 mb-1">الجرعة المعطاة <x-info field="nursery.child_daily_medication_dosage" /></label>
                            <input type="text" name="dosage" maxlength="64" placeholder="اختياري إن اختلفت"
                                   class="w-full rounded-lg border border-orange-200 px-3 py-2 text-sm">
                        </div>
                    </div>
                    <input type="text" name="note" maxlength="500" placeholder="ملاحظة لولي الأمر (اختياري)"
                           class="w-full rounded-lg border border-orange-200 px-3 py-2 text-sm">
                    <button type="submit" class="nursery-btn nursery-btn-primary text-xs py-1.5 w-full">حفظ جرعة الدواء</button>
                </form>
            @endif

            <form method="post" action="{{ route('nursery.children.daily-activities.store', $child) }}" class="rounded-xl border border-orange-100 p-3 space-y-2 sm:col-span-2">
                @csrf
                <input type="hidden" name="activity_date" value="{{ $activityDate }}">
                <input type="hidden" name="type" value="note">
                <p class="text-sm font-bold text-orange-950">ملاحظة المعلمة <x-info field="nursery.child_daily_note" /></p>
                <textarea name="note" rows="2" maxlength="500" required placeholder="ملاحظة داخلية…"
                          class="w-full rounded-lg border border-orange-200 px-3 py-2 text-sm"></textarea>
                <label class="flex items-center gap-2 text-sm text-orange-900">
                    <input type="checkbox" name="is_parent_visible" value="1" class="rounded border-orange-300 text-orange-600">
                    إظهار لولي الأمر
                    <x-info field="nursery.child_daily_parent_visible" />
                </label>
                <button type="submit" class="nursery-btn nursery-btn-primary text-xs py-1.5">حفظ الملاحظة</button>
            </form>
        </div>
    @elseif(! $child->isActive())
        <p class="text-sm text-orange-800/70">الطفل مؤرشف — لا يمكن تسجيل يوم جديد.</p>
    @endif

    <div>
        <h3 class="text-sm font-bold text-orange-950 mb-2">سجل {{ $activityDate }}</h3>
        <ul class="space-y-2">
            @forelse($todaysActivities as $item)
                <li class="flex flex-wrap items-start justify-between gap-2 rounded-lg border border-orange-50 bg-white px-3 py-2 text-sm">
                    <div class="min-w-0">
                        <p class="font-semibold text-orange-950">{{ $item->typeLabel() }}</p>
                        <p class="text-orange-800/90">{{ $item->summaryLine() }}</p>
                        <p class="text-xs text-orange-700/70 mt-0.5">
                            {{ $item->recorded_at?->format('H:i') }}
                            @if($item->recorder?->name) · {{ $item->recorder->name }} @endif
                            @if($item->is_parent_visible)
                                · ظاهر لولي الأمر
                            @else
                                · داخلي
                            @endif
                        </p>
                    </div>
                    @if($canManageChildActivity)
                        <div class="flex gap-1 shrink-0">
                            @if($item->type === 'note')
                                <form method="post" action="{{ route('nursery.children.daily-activities.update', [$child, $item]) }}">
                                    @csrf @method('PATCH')
                                    <input type="hidden" name="note" value="{{ $item->note }}">
                                    <input type="hidden" name="is_parent_visible" value="{{ $item->is_parent_visible ? '0' : '1' }}">
                                    <button type="submit" class="nursery-btn nursery-btn-soft text-xs py-1 px-2">
                                        {{ $item->is_parent_visible ? 'إخفاء' : 'إظهار' }}
                                    </button>
                                </form>
                            @endif
                            <form method="post" action="{{ route('nursery.children.daily-activities.destroy', [$child, $item]) }}" onsubmit="return confirm('حذف هذا السجل؟')">
                                @csrf @method('DELETE')
                                <button type="submit" class="nursery-btn nursery-btn-soft text-xs py-1 px-2 text-red-700">حذف</button>
                            </form>
                        </div>
                    @endif
                </li>
            @empty
                <li class="text-sm text-orange-700/60">لا يوجد سجل لهذا اليوم.</li>
            @endforelse
        </ul>
    </div>
</section>
