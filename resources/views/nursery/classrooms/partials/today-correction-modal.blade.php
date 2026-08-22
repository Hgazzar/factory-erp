<div x-show="correctOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/40"
     @keydown.escape.window="closeCorrect()">
    <div class="nursery-card w-full max-w-md p-5 space-y-4 max-h-[90vh] overflow-y-auto" @click.outside="closeCorrect()">
        <div class="flex items-start justify-between gap-2">
            <div>
                <h3 class="text-lg font-bold text-orange-950">
                    تصحيح الحضور
                    <x-info field="nursery.classroom_today_correct" />
                </h3>
                <p class="text-sm text-orange-800/80" x-text="correctChildName"></p>
            </div>
            <button type="button" @click="closeCorrect()" class="nursery-btn nursery-btn-soft text-sm py-1 px-2">إغلاق</button>
        </div>

        <form method="post" class="space-y-3" :action="'{{ url('/nursery/attendance') }}/' + correctLogId + '/correct'">
            @csrf
            @method('PATCH')

            <div class="grid grid-cols-2 gap-2">
                <div>
                    <label class="block text-xs text-orange-800/80 mb-1">حضور</label>
                    <input type="time" name="checked_in_at" class="w-full rounded-lg border border-orange-200 px-3 py-2 text-sm"
                           x-model="correctIn">
                </div>
                <div>
                    <label class="block text-xs text-orange-800/80 mb-1">انصراف</label>
                    <input type="time" name="checked_out_at" class="w-full rounded-lg border border-orange-200 px-3 py-2 text-sm"
                           x-model="correctOut">
                </div>
            </div>

            <div>
                <label class="block text-xs text-orange-800/80 mb-1">الحالة</label>
                <input type="hidden" name="status" :value="correctStatus">
                <div class="flex flex-wrap gap-2">
                    @foreach($correctionStatusOptions as $opt)
                        <button type="button"
                                class="nursery-btn text-xs py-1.5"
                                :class="correctStatus === @js($opt['value']) ? 'nursery-btn-primary' : 'nursery-btn-soft'"
                                @click="correctStatus = @js($opt['value'])">{{ $opt['label'] }}</button>
                    @endforeach
                </div>
            </div>

            <div>
                <label class="block text-xs text-orange-800/80 mb-1">
                    سبب التصحيح
                    <x-info field="nursery.attendance_correction_reason" />
                </label>
                <input type="text" name="reason" maxlength="255" class="w-full rounded-lg border border-orange-200 px-3 py-2 text-sm"
                       placeholder="اختياري">
            </div>

            <div class="flex gap-2 justify-end">
                <button type="button" @click="closeCorrect()" class="nursery-btn nursery-btn-soft">إلغاء</button>
                <button type="submit" class="nursery-btn nursery-btn-primary">حفظ التصحيح</button>
            </div>
        </form>
    </div>
</div>
