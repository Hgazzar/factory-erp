@php
    /** @var array<string, mixed> $staffBoard */
    $canPostStaffAttendance = $canManageStaff ?? app(\App\Support\NurseryAccess::class)->allows(\App\Support\NurseryAccess::CAP_MANAGE_STAFF_ATTENDANCE);
    $staffCheckInIds = $staffBoard['not_yet']->map(fn ($employee) => (int) $employee->id)->values()->all();
    $staffCheckOutIds = $staffBoard['checked_in']->map(fn ($row) => (int) $row['employee']->id)->values()->all();
@endphp

<div class="space-y-4"
     @if($canPostStaffAttendance)
     x-data="{
        selected: {},
        checkInIds: @js($staffCheckInIds),
        checkOutIds: @js($staffCheckOutIds),
        toggle(id) { this.selected = { ...this.selected, [id]: !this.selected[id] }; },
        selectedIds() { return Object.keys(this.selected).filter((key) => this.selected[key]).map(Number); },
        count() { return this.selectedIds().length; },
        selectedCheckInIds() { return this.checkInIds.filter((id) => this.selected[id]); },
        selectedCheckOutIds() { return this.checkOutIds.filter((id) => this.selected[id]); },
        selectAllCheckIn() {
            const next = {};
            this.checkInIds.forEach((id) => { next[id] = true; });
            this.selected = next;
        },
        selectAllCheckOut() {
            const next = {};
            this.checkOutIds.forEach((id) => { next[id] = true; });
            this.selected = next;
        },
        clearSelection() { this.selected = {}; },
        bulkConfirmOpen: false,
        bulkAction: 'check-in',
        openBulkConfirm(action) {
            this.bulkAction = action === 'check-out' ? 'check-out' : 'check-in';
            if (this.bulkAction === 'check-in' && this.selectedCheckInIds().length < 1) return;
            if (this.bulkAction === 'check-out' && this.selectedCheckOutIds().length < 1) return;
            this.bulkConfirmOpen = true;
        },
        closeBulkConfirm() { this.bulkConfirmOpen = false; }
     }"
     :class="count() > 0 ? 'pb-4' : ''"
     @endif
>
    @if($canPostStaffAttendance && (count($staffCheckInIds) > 0 || count($staffCheckOutIds) > 0))
        <div class="flex flex-wrap gap-2">
            @if(count($staffCheckInIds) > 0)
                <button type="button" class="nursery-btn nursery-btn-soft text-sm py-2" @click="selectAllCheckIn()">
                    تحديد الكل للحضور
                    <x-info field="nursery.staff_attendance_bulk_select" />
                </button>
            @endif
            @if(count($staffCheckOutIds) > 0)
                <button type="button" class="nursery-btn nursery-btn-soft text-sm py-2" @click="selectAllCheckOut()">
                    تحديد الكل للانصراف
                    <x-info field="nursery.staff_attendance_bulk_select" />
                </button>
            @endif
            <button type="button" class="nursery-btn nursery-btn-soft text-sm py-2" x-show="count() > 0" x-cloak @click="clearSelection()">إلغاء تحديد الكل</button>
        </div>
    @endif

    <div class="grid gap-6 lg:grid-cols-3">
        <section class="nursery-card p-4 lg:col-span-1">
            <h2 class="font-bold text-amber-700 mb-2">بانتظار الحضور ({{ $staffBoard['not_yet']->count() }})</h2>
            @forelse($staffBoard['not_yet'] as $employee)
                <div class="flex flex-wrap items-center justify-between gap-2 py-2 border-b border-teal-50 text-sm"
                     @if($canPostStaffAttendance) :class="selected[{{ (int) $employee->id }}] ? 'bg-teal-50/80' : ''" @endif>
                    <div class="flex items-center gap-2 min-w-0">
                        @if($canPostStaffAttendance)
                            <button type="button"
                                    class="shrink-0 w-9 h-9 rounded-lg border border-teal-200 flex items-center justify-center bg-white"
                                    @click="toggle({{ (int) $employee->id }})"
                                    :aria-pressed="!!selected[{{ (int) $employee->id }}]"
                                    aria-label="تحديد {{ $employee->name }}">
                                <span class="block w-4 h-4 rounded border-2 border-teal-300"
                                      :class="selected[{{ (int) $employee->id }}] ? 'bg-teal-600 border-teal-600' : 'bg-white'"></span>
                            </button>
                        @endif
                        <span class="font-medium">{{ $employee->name }}</span>
                    </div>
                    @if($canPostStaffAttendance)
                    <form method="post" action="{{ route('nursery.attendance.staff.check-in') }}">
                        @csrf
                        <input type="hidden" name="employee_id" value="{{ $employee->id }}">
                        <button type="submit" class="nursery-btn nursery-btn-primary text-xs py-1 px-2">حضور</button>
                    </form>
                    @endif
                </div>
            @empty
                <p class="text-sm text-teal-700/60">—</p>
            @endforelse
        </section>
        <section class="nursery-card p-4 lg:col-span-1">
            <h2 class="font-bold text-emerald-700 mb-2">حاضرون ({{ $staffBoard['checked_in']->count() }})</h2>
            @forelse($staffBoard['checked_in'] as $row)
                <div class="flex flex-wrap items-center justify-between gap-2 py-2 border-b border-teal-50 text-sm"
                     @if($canPostStaffAttendance) :class="selected[{{ (int) $row['employee']->id }}] ? 'bg-teal-50/80' : ''" @endif>
                    <div class="flex items-center gap-2 min-w-0">
                        @if($canPostStaffAttendance)
                            <button type="button"
                                    class="shrink-0 w-9 h-9 rounded-lg border border-teal-200 flex items-center justify-center bg-white"
                                    @click="toggle({{ (int) $row['employee']->id }})"
                                    :aria-pressed="!!selected[{{ (int) $row['employee']->id }}]"
                                    aria-label="تحديد {{ $row['employee']->name }}">
                                <span class="block w-4 h-4 rounded border-2 border-teal-300"
                                      :class="selected[{{ (int) $row['employee']->id }}] ? 'bg-teal-600 border-teal-600' : 'bg-white'"></span>
                            </button>
                        @endif
                        <div>
                            <span class="font-medium">{{ $row['employee']->name }}</span>
                            <span class="text-xs text-emerald-600 ms-1">{{ $row['log']->checked_in_at?->format('H:i') }}</span>
                        </div>
                    </div>
                    @if($canPostStaffAttendance)
                    <form method="post" action="{{ route('nursery.attendance.staff.check-out') }}">
                        @csrf
                        <input type="hidden" name="employee_id" value="{{ $row['employee']->id }}">
                        <button type="submit" class="nursery-btn nursery-btn-soft text-xs py-1 px-2">انصراف</button>
                    </form>
                    @endif
                </div>
            @empty
                <p class="text-sm text-teal-700/60">—</p>
            @endforelse
        </section>
        <section class="nursery-card p-4 lg:col-span-1">
            <h2 class="font-bold text-sky-700 mb-2">انصرفوا ({{ $staffBoard['checked_out']->count() }})</h2>
            @forelse($staffBoard['checked_out'] as $row)
                <div class="py-2 border-b border-teal-50 text-sm">
                    <span class="font-medium">{{ $row['employee']->name }}</span>
                    <span class="text-xs text-sky-600 ms-1">{{ $row['log']->checked_out_at?->format('H:i') }}</span>
                </div>
            @empty
                <p class="text-sm text-teal-700/60">—</p>
            @endforelse
        </section>
    </div>

    @if($canPostStaffAttendance)
        <div x-show="count() > 0" x-cloak class="nursery-today-bulk-bar">
            <div class="nursery-today-bulk-bar__inner nursery-card px-4 py-3 flex flex-wrap items-center justify-between gap-3">
                <p class="text-sm font-semibold text-teal-950 min-w-0">
                    تم تحديد <span class="tabular-nums" x-text="count()"></span> موظفين
                </p>
                <div class="flex flex-wrap items-center gap-2 shrink-0">
                    <button type="button"
                            class="nursery-btn nursery-btn-primary px-5 py-2.5 text-sm"
                            x-show="selectedCheckInIds().length > 0"
                            @click="openBulkConfirm('check-in')">
                        تسجيل حضور (<span class="tabular-nums" x-text="selectedCheckInIds().length"></span>)
                    </button>
                    <button type="button"
                            class="nursery-btn nursery-btn-primary px-5 py-2.5 text-sm"
                            x-show="selectedCheckOutIds().length > 0"
                            @click="openBulkConfirm('check-out')">
                        تسجيل انصراف (<span class="tabular-nums" x-text="selectedCheckOutIds().length"></span>)
                    </button>
                </div>
            </div>
        </div>

        <div x-show="bulkConfirmOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/40"
             @keydown.escape.window="closeBulkConfirm()">
            <div class="nursery-card w-full max-w-md p-5 space-y-4" @click.outside="closeBulkConfirm()">
                <div class="flex items-start justify-between gap-2">
                    <div>
                        <h3 class="text-lg font-bold text-teal-950" x-text="bulkAction === 'check-out' ? 'تأكيد الانصراف' : 'تأكيد الحضور'"></h3>
                        <p class="text-sm text-teal-800/80 mt-1"
                           x-text="bulkAction === 'check-out'
                               ? ('هل تريد تسجيل انصراف ' + selectedCheckOutIds().length + ' موظفًا؟')
                               : ('هل تريد تسجيل حضور ' + selectedCheckInIds().length + ' موظفًا؟')"></p>
                    </div>
                    <button type="button" @click="closeBulkConfirm()" class="nursery-btn nursery-btn-soft text-sm py-1 px-2">إغلاق</button>
                </div>

                <form method="post" action="{{ route('nursery.attendance.staff.bulk-check-in') }}" class="space-y-3" x-show="bulkAction === 'check-in'">
                    @csrf
                    <template x-for="id in selectedCheckInIds()" :key="'staff-confirm-in-'+id">
                        <input type="hidden" name="employee_ids[]" :value="id">
                    </template>
                    <div class="flex gap-2 justify-end">
                        <button type="button" @click="closeBulkConfirm()" class="nursery-btn nursery-btn-soft">إلغاء</button>
                        <button type="submit" class="nursery-btn nursery-btn-primary">تأكيد الحضور</button>
                    </div>
                </form>

                <form method="post" action="{{ route('nursery.attendance.staff.bulk-check-out') }}" class="space-y-3" x-show="bulkAction === 'check-out'">
                    @csrf
                    <template x-for="id in selectedCheckOutIds()" :key="'staff-confirm-out-'+id">
                        <input type="hidden" name="employee_ids[]" :value="id">
                    </template>
                    <div class="flex gap-2 justify-end">
                        <button type="button" @click="closeBulkConfirm()" class="nursery-btn nursery-btn-soft">إلغاء</button>
                        <button type="submit" class="nursery-btn nursery-btn-primary">تأكيد الانصراف</button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>
