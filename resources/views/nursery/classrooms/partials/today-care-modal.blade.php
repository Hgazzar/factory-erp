@php
    use App\Support\NurseryChildDailyActivityCatalog;
@endphp
<div x-show="careOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/40"
     @keydown.escape.window="closeCare()">
    <div class="nursery-card w-full max-w-md p-5 space-y-4 max-h-[90vh] overflow-y-auto" @click.outside="closeCare()">
        <div class="flex items-start justify-between gap-2">
            <div>
                <h3 class="text-lg font-bold text-teal-950">تسجيل نشاط</h3>
                <p class="text-sm text-teal-800/80" x-text="careChildName"></p>
            </div>
            <button type="button" @click="closeCare()" class="nursery-btn nursery-btn-soft text-sm py-1 px-2">إغلاق</button>
        </div>

        <div class="flex flex-wrap gap-2">
            @foreach(NurseryChildDailyActivityCatalog::keys() as $typeKey)
                <button type="button"
                        class="nursery-btn text-xs py-1.5"
                        :class="careType === @js($typeKey) ? 'nursery-btn-primary' : 'nursery-btn-soft'"
                        @click="careType = @js($typeKey)">{{ NurseryChildDailyActivityCatalog::label($typeKey) }}</button>
            @endforeach
        </div>

        <form method="post" class="space-y-3" :action="'{{ url('/nursery/children') }}/' + careChildId + '/daily-activities'">
            @csrf
            <input type="hidden" name="activity_date" value="{{ now()->toDateString() }}">
            <input type="hidden" name="type" :value="careType">
            <input type="hidden" name="return_to" value="classroom_today">
            <input type="hidden" name="classroom_id" value="{{ $classroom->id }}">

            <div x-show="careType === 'meal'" class="space-y-2">
                <x-custom-select name="meal" id="today_care_meal" :options="NurseryChildDailyActivityCatalog::selectOptions('meal', 'meal')" :searchable="false" empty-label="نوع الوجبة" :in-modal="true" />
                <x-custom-select name="amount" id="today_care_amount" :options="NurseryChildDailyActivityCatalog::selectOptions('meal', 'amount')" :searchable="false" empty-label="الكمية" :in-modal="true" />
            </div>
            <div x-show="careType === 'nap'" class="grid grid-cols-2 gap-2">
                <div>
                    <label class="block text-xs text-teal-800/80 mb-1">من</label>
                        <input type="time" name="started_at" class="w-full rounded-lg border border-teal-200 px-3 py-2 text-sm" :required="careType === 'nap'">
                </div>
                <div>
                    <label class="block text-xs text-teal-800/80 mb-1">إلى</label>
                    <input type="time" name="ended_at" class="w-full rounded-lg border border-teal-200 px-3 py-2 text-sm">
                </div>
            </div>
            <div x-show="careType === 'diaper'">
                <x-custom-select name="change" id="today_care_diaper" :options="NurseryChildDailyActivityCatalog::selectOptions('diaper', 'change')" :searchable="false" empty-label="الحالة" :in-modal="true" />
            </div>
            <div x-show="careType === 'toilet'">
                <x-custom-select name="result" id="today_care_toilet" :options="NurseryChildDailyActivityCatalog::selectOptions('toilet', 'result')" :searchable="false" empty-label="النتيجة" :in-modal="true" />
            </div>
            <div x-show="careType === 'mood'">
                <x-custom-select name="mood" id="today_care_mood" :options="NurseryChildDailyActivityCatalog::selectOptions('mood', 'mood')" :searchable="false" empty-label="اختر المزاج" :in-modal="true" />
            </div>
            <div x-show="careType === 'activity'">
                <input type="text" name="title" maxlength="80" placeholder="مثال: رسم حر"
                       class="w-full rounded-lg border border-teal-200 px-3 py-2 text-sm">
            </div>
            <div x-show="careType === 'medication'" class="space-y-2">
                <input type="text" name="medication_name" maxlength="120" placeholder="اسم الدواء"
                       class="w-full rounded-lg border border-teal-200 px-3 py-2 text-sm"
                       :required="careType === 'medication'">
                <x-custom-select name="status" id="today_care_med_status" :options="NurseryChildDailyActivityCatalog::selectOptions('medication', 'status')" :searchable="false" empty-label="حالة الجرعة" :in-modal="true" />
                <div class="grid grid-cols-2 gap-2">
                    <div>
                        <label class="block text-xs text-teal-800/80 mb-1">الوقت</label>
                        <input type="time" name="given_at" class="w-full rounded-lg border border-teal-200 px-3 py-2 text-sm"
                               :required="careType === 'medication'">
                    </div>
                    <div>
                        <label class="block text-xs text-teal-800/80 mb-1">الجرعة</label>
                        <input type="text" name="dosage" maxlength="64" placeholder="اختياري"
                               class="w-full rounded-lg border border-teal-200 px-3 py-2 text-sm">
                    </div>
                </div>
            </div>
            <div>
                <textarea name="note" rows="2" maxlength="500" placeholder="ملاحظة اختيارية"
                          class="w-full rounded-lg border border-teal-200 px-3 py-2 text-sm"
                          :required="careType === 'note'"></textarea>
            </div>

            <div class="flex gap-2 justify-end">
                <button type="button" @click="closeCare()" class="nursery-btn nursery-btn-soft">إلغاء</button>
                <button type="submit" class="nursery-btn nursery-btn-primary">حفظ</button>
            </div>
        </form>
    </div>
</div>
