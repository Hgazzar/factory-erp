@php
    /** @var array<string, mixed> $board */
    $canPostChildAttendance = $canManageChildren ?? app(\App\Support\NurseryAccess::class)->allows(\App\Support\NurseryAccess::CAP_MANAGE_CHILD_ATTENDANCE);
    $childCheckInIds = $board['not_yet']->map(fn ($child) => (int) $child->id)->values()->all();
    $childCheckOutIds = $board['checked_in']->map(fn ($row) => (int) $row['child']->id)->values()->all();
@endphp

<div class="space-y-4"
     @if($canPostChildAttendance)
     x-data="{
        selected: {},
        checkInIds: @js($childCheckInIds),
        checkOutIds: @js($childCheckOutIds),
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
    @if($canPostChildAttendance && (count($childCheckInIds) > 0 || count($childCheckOutIds) > 0))
        <div class="flex flex-wrap gap-2">
            @if(count($childCheckInIds) > 0)
                <button type="button" class="nursery-btn nursery-btn-soft text-sm py-2" @click="selectAllCheckIn()">
                    تحديد الكل للحضور
                    <x-info field="nursery.child_attendance_bulk_select" />
                </button>
            @endif
            @if(count($childCheckOutIds) > 0)
                <button type="button" class="nursery-btn nursery-btn-soft text-sm py-2" @click="selectAllCheckOut()">
                    تحديد الكل للانصراف
                    <x-info field="nursery.child_attendance_bulk_select_out" />
                </button>
            @endif
            <button type="button" class="nursery-btn nursery-btn-soft text-sm py-2" x-show="count() > 0" x-cloak @click="clearSelection()">إلغاء تحديد الكل</button>
        </div>
    @endif

    <div class="grid gap-6 lg:grid-cols-3">
        <section class="nursery-card p-4 lg:col-span-1">
            <h2 class="font-bold text-amber-700 mb-2">بانتظار الحضور ({{ $board['not_yet']->count() }})</h2>
            @forelse($board['not_yet'] as $child)
                <div class="flex flex-wrap items-center justify-between gap-2 py-2 border-b border-orange-50 text-sm"
                     @if($canPostChildAttendance) :class="selected[{{ (int) $child->id }}] ? 'bg-orange-50/80' : ''" @endif>
                    <div class="flex items-center gap-2 min-w-0">
                        @if($canPostChildAttendance)
                            <button type="button"
                                    class="shrink-0 w-9 h-9 rounded-lg border border-orange-200 flex items-center justify-center bg-white"
                                    @click="toggle({{ (int) $child->id }})"
                                    :aria-pressed="!!selected[{{ (int) $child->id }}]"
                                    aria-label="تحديد {{ $child->name }}">
                                <span class="block w-4 h-4 rounded border-2 border-orange-300"
                                      :class="selected[{{ (int) $child->id }}] ? 'bg-orange-600 border-orange-600' : 'bg-white'"></span>
                            </button>
                        @endif
                        <a href="{{ route('nursery.children.show', $child) }}" class="font-medium text-orange-900 hover:underline truncate">{{ $child->name }}</a>
                    </div>
                    @if($canPostChildAttendance)
                    <form method="post" action="{{ route('nursery.attendance.check-in') }}">
                        @csrf
                        <input type="hidden" name="child_id" value="{{ $child->id }}">
                        <button type="submit" class="nursery-btn nursery-btn-primary text-xs py-1 px-2">حضور</button>
                    </form>
                    @endif
                </div>
            @empty
                <p class="text-sm text-orange-700/60">—</p>
            @endforelse
        </section>
        <section class="nursery-card p-4 lg:col-span-1">
            <h2 class="font-bold text-emerald-700 mb-2">حاضرون ({{ $board['checked_in']->count() }})</h2>
            @forelse($board['checked_in'] as $row)
                <div class="flex flex-wrap items-center justify-between gap-2 py-2 border-b border-orange-50 text-sm"
                     @if($canPostChildAttendance) :class="selected[{{ (int) $row['child']->id }}] ? 'bg-orange-50/80' : ''" @endif>
                    <div class="flex items-center gap-2 min-w-0">
                        @if($canPostChildAttendance)
                            <button type="button"
                                    class="shrink-0 w-9 h-9 rounded-lg border border-orange-200 flex items-center justify-center bg-white"
                                    @click="toggle({{ (int) $row['child']->id }})"
                                    :aria-pressed="!!selected[{{ (int) $row['child']->id }}]"
                                    aria-label="تحديد {{ $row['child']->name }}">
                                <span class="block w-4 h-4 rounded border-2 border-orange-300"
                                      :class="selected[{{ (int) $row['child']->id }}] ? 'bg-orange-600 border-orange-600' : 'bg-white'"></span>
                            </button>
                        @endif
                        <div class="min-w-0">
                            <a href="{{ route('nursery.children.show', $row['child']) }}" class="font-medium text-orange-900 hover:underline">{{ $row['child']->name }}</a>
                            <span class="text-xs text-emerald-600 ms-1">{{ $row['log']->checked_in_at?->format('H:i') }}</span>
                        </div>
                    </div>
                    @if($canPostChildAttendance)
                    <form method="post" action="{{ route('nursery.attendance.check-out') }}">
                        @csrf
                        <input type="hidden" name="child_id" value="{{ $row['child']->id }}">
                        <button type="submit" class="nursery-btn nursery-btn-soft text-xs py-1 px-2">انصراف</button>
                    </form>
                    @endif
                </div>
            @empty
                <p class="text-sm text-orange-700/60">—</p>
            @endforelse
        </section>
        <section class="nursery-card p-4 lg:col-span-1">
            <h2 class="font-bold text-sky-700 mb-2">انصرفوا ({{ $board['checked_out']->count() }})</h2>
            @forelse($board['checked_out'] as $row)
                <div class="py-2 border-b border-orange-50 text-sm">
                    <a href="{{ route('nursery.children.show', $row['child']) }}" class="font-medium text-orange-900 hover:underline">{{ $row['child']->name }}</a>
                    <span class="text-xs text-sky-600 ms-1">{{ $row['log']->checked_out_at?->format('H:i') }}</span>
                </div>
            @empty
                <p class="text-sm text-orange-700/60">—</p>
            @endforelse
        </section>
    </div>

    @if($canPostChildAttendance)
        <div x-show="count() > 0" x-cloak class="nursery-today-bulk-bar">
            <div class="nursery-today-bulk-bar__inner nursery-card px-4 py-3 flex flex-wrap items-center justify-between gap-3">
                <p class="text-sm font-semibold text-orange-950 min-w-0">
                    تم تحديد <span class="tabular-nums" x-text="count()"></span> أطفال
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
                    <template x-for="id in selectedCheckInIds()" :key="'child-board-confirm-in-'+id">
                        <input type="hidden" name="child_ids[]" :value="id">
                    </template>
                    <div class="flex gap-2 justify-end">
                        <button type="button" @click="closeBulkConfirm()" class="nursery-btn nursery-btn-soft">إلغاء</button>
                        <button type="submit" class="nursery-btn nursery-btn-primary">تأكيد الحضور</button>
                    </div>
                </form>

                <form method="post" action="{{ route('nursery.attendance.bulk-check-out') }}" class="space-y-3" x-show="bulkAction === 'check-out'">
                    @csrf
                    <template x-for="id in selectedCheckOutIds()" :key="'child-board-confirm-out-'+id">
                        <input type="hidden" name="child_ids[]" :value="id">
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
