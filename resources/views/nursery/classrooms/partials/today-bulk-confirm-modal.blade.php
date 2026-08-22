<div x-show="bulkConfirmOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/40"
     @keydown.escape.window="closeBulkConfirm()">
    <div class="nursery-card w-full max-w-md p-5 space-y-4" @click.outside="closeBulkConfirm()">
        <div class="flex items-start justify-between gap-2">
            <div>
                <h3 class="text-lg font-bold text-orange-950" x-text="bulkAction === 'check-out' ? 'تأكيد الانصراف' : 'تأكيد الحضور'"></h3>
                <p class="text-sm text-orange-800/80 mt-1"
                   x-text="bulkAction === 'check-out'
                       ? ('هل تريد تسجيل انصراف ' + selectedCheckOutIds().length + ' طفلًا؟')
                       : ('هل تريد تسجيل حضور ' + selectedCheckInIds().length + ' طفلًا؟')"></p>
            </div>
            <button type="button" @click="closeBulkConfirm()" class="nursery-btn nursery-btn-soft text-sm py-1 px-2">إغلاق</button>
        </div>

        <form method="post" action="{{ route('nursery.attendance.bulk-check-in') }}" class="space-y-3" x-show="bulkAction === 'check-in'">
            @csrf
            <input type="hidden" name="classroom_id" value="{{ $classroom->id }}">
            <template x-for="id in selectedCheckInIds()" :key="'confirm-in-'+id">
                <input type="hidden" name="child_ids[]" :value="id">
            </template>
            <div class="flex gap-2 justify-end">
                <button type="button" @click="closeBulkConfirm()" class="nursery-btn nursery-btn-soft">إلغاء</button>
                <button type="submit" class="nursery-btn nursery-btn-primary">تأكيد الحضور</button>
            </div>
        </form>

        <form method="post" action="{{ route('nursery.attendance.bulk-check-out') }}" class="space-y-3" x-show="bulkAction === 'check-out'">
            @csrf
            <input type="hidden" name="classroom_id" value="{{ $classroom->id }}">
            <template x-for="id in selectedCheckOutIds()" :key="'confirm-out-'+id">
                <input type="hidden" name="child_ids[]" :value="id">
            </template>
            <div class="flex gap-2 justify-end">
                <button type="button" @click="closeBulkConfirm()" class="nursery-btn nursery-btn-soft">إلغاء</button>
                <button type="submit" class="nursery-btn nursery-btn-primary">تأكيد الانصراف</button>
            </div>
        </form>
    </div>
</div>
