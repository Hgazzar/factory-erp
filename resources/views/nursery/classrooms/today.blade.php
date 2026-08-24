@extends('layouts.nursery')

@section('title', 'يوم الفصل — '.$classroom->name)

@section('content')
@php
    $checkInIds = $roster->where('state', 'not_yet')->map(fn ($row) => (int) $row['child']->id)->values()->all();
    $checkOutIds = $roster->where('state', 'present')->map(fn ($row) => (int) $row['child']->id)->values()->all();
@endphp
<div class="w-full space-y-5" dir="rtl"
     x-data="{
        careOpen: false,
        careChildId: null,
        careChildName: '',
        careType: 'meal',
        openCare(id, name, type) {
            this.careChildId = id;
            this.careChildName = name;
            this.careType = type;
            this.careOpen = true;
        },
        closeCare() { this.careOpen = false; },
        selected: {},
        checkInIds: @json($checkInIds),
        checkOutIds: @json($checkOutIds),
        toggle(id) {
            this.selected = { ...this.selected, [id]: !this.selected[id] };
        },
        selectedIds() {
            return Object.keys(this.selected).filter((key) => this.selected[key]).map(Number);
        },
        count() { return this.selectedIds().length; },
        selectedCheckInIds() {
            return this.checkInIds.filter((id) => this.selected[id]);
        },
        selectedCheckOutIds() {
            return this.checkOutIds.filter((id) => this.selected[id]);
        },
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
        closeBulkConfirm() { this.bulkConfirmOpen = false; },
        correctOpen: false,
        correctLogId: null,
        correctChildName: '',
        correctIn: '',
        correctOut: '',
        correctStatus: 'present',
        openCorrect(logId, name, inTime, outTime, status) {
            this.correctLogId = logId;
            this.correctChildName = name;
            this.correctIn = inTime || '';
            this.correctOut = outTime || '';
            this.correctStatus = status === 'absent' ? 'absent' : 'present';
            this.correctOpen = true;
        },
        closeCorrect() { this.correctOpen = false; }
     }"
     :class="count() > 0 ? 'pb-4' : ''">
    {{-- Future: Care | Learning toggle. Learning stays a separate system — do not implement here. --}}
    <div data-nursery-today-pane="care" class="space-y-5">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div>
                <h1 class="text-2xl font-extrabold text-teal-950">يوم الفصل</h1>
                <p class="text-sm text-teal-800/80 mt-1">
                    {{ $classroom->name }}
                    <x-info field="nursery.classroom_today" />
                </p>
            </div>
            <a href="{{ route('nursery.classrooms.index') }}" class="nursery-btn nursery-btn-soft">← الفصول</a>
        </div>

        <form method="get" action="{{ route('nursery.classrooms.today.redirect') }}" class="nursery-card p-4 flex flex-wrap gap-3 items-end">
            <div class="flex-1 min-w-[180px]">
                <label class="block text-sm font-semibold text-teal-950 mb-1">الفصل</label>
                <x-custom-select name="classroom_id" :options="$classroomOptions"
                    :value="(string) $classroom->id" placeholder="اختر الفصل" :searchable="count($classroomOptions) > 6" />
            </div>
            <button type="submit" class="nursery-btn nursery-btn-soft">عرض</button>
        </form>

        <div class="grid gap-3 grid-cols-2 sm:grid-cols-3 lg:grid-cols-5">
            <div class="nursery-card px-3 py-3.5 text-center">
                <p class="text-xs font-semibold text-emerald-800/90">حضور</p>
                <p class="mt-1 text-xl font-extrabold text-emerald-600 tabular-nums">{{ $board['checked_in']->count() }}</p>
            </div>
            <div class="nursery-card px-3 py-3.5 text-center">
                <p class="text-xs font-semibold text-amber-800/90">لم يصلوا</p>
                <p class="mt-1 text-xl font-extrabold text-amber-600 tabular-nums">{{ $board['not_yet']->count() }}</p>
            </div>
            <div class="nursery-card px-3 py-3.5 text-center">
                <p class="text-xs font-semibold text-sky-800/90">انصرفوا</p>
                <p class="mt-1 text-xl font-extrabold text-sky-600 tabular-nums">{{ $board['checked_out']->count() }}</p>
            </div>
            <div class="nursery-card px-3 py-3.5 text-center">
                <p class="text-xs font-semibold text-teal-950/90">السعة <x-info field="nursery.classroom_capacity" /></p>
                <p class="mt-1 text-xl font-extrabold text-teal-600 tabular-nums">{{ $capacity ?? '—' }}</p>
            </div>
            <div class="nursery-card px-3 py-3.5 text-center col-span-2 sm:col-span-1">
                <p class="text-xs font-semibold text-teal-950/90">النسبة <x-info field="nursery.classroom_today_ratio" /></p>
                @if($capacity)
                    <p class="mt-1 text-xl font-extrabold text-teal-600 tabular-nums">{{ $enrolled }}/{{ $capacity }}</p>
                @else
                    <p class="mt-1 text-xl font-extrabold text-teal-600 tabular-nums">{{ $enrolled }}</p>
                @endif
                @if($classroom->teacher?->name)
                    <p class="text-[11px] text-teal-700/70 mt-1 truncate">{{ $classroom->teacher->name }}</p>
                @endif
            </div>
        </div>

        <section class="space-y-3">
            <div class="flex flex-wrap items-center justify-between gap-2">
                <h2 class="text-base sm:text-lg font-bold text-teal-950">
                    الأطفال
                    <x-info field="nursery.classroom_today_roster" />
                </h2>
                @if($canManageChildAttendance && (count($checkInIds) > 0 || count($checkOutIds) > 0))
                    <div class="flex flex-wrap gap-2">
                        @if(count($checkInIds) > 0)
                            <button type="button" class="nursery-btn nursery-btn-soft text-xs sm:text-sm py-1.5 px-3" @click="selectAllCheckIn()">
                                تحديد الكل للحضور
                                <x-info field="nursery.classroom_today_select" />
                            </button>
                        @endif
                        @if(count($checkOutIds) > 0)
                            <button type="button" class="nursery-btn nursery-btn-soft text-xs sm:text-sm py-1.5 px-3" @click="selectAllCheckOut()">
                                تحديد الكل للانصراف
                                <x-info field="nursery.classroom_today_select_out" />
                            </button>
                        @endif
                        <button type="button" class="nursery-btn nursery-btn-soft text-xs sm:text-sm py-1.5 px-3" x-show="count() > 0" x-cloak @click="clearSelection()">إلغاء تحديد الكل</button>
                    </div>
                @endif
            </div>
            @forelse($roster as $row)
                @php
                    $child = $row['child'];
                    $log = $row['log'];
                    $state = $row['state'];
                    $isLate = (bool) ($row['is_late'] ?? false);
                    $isEarly = (bool) ($row['is_early'] ?? false);
                    $selectable = $canManageChildAttendance && $state !== 'checked_out';
                    $correctStatus = $log?->status === 'absent' ? 'absent' : 'present';
                @endphp
                <article class="nursery-child-card"
                         :class="selected[{{ (int) $child->id }}] ? 'border-teal-400 bg-teal-50/40' : ''">
                    <div class="flex flex-wrap items-center gap-3">
                        @if($selectable)
                            <button type="button"
                                    class="shrink-0 w-9 h-9 rounded-lg border border-teal-200 flex items-center justify-center bg-white"
                                    :class="selected[{{ (int) $child->id }}] ? 'bg-teal-50 border-teal-400' : ''"
                                    @click="toggle({{ (int) $child->id }})"
                                    :aria-pressed="!!selected[{{ (int) $child->id }}]"
                                    aria-label="تحديد {{ $child->name }}">
                                <span class="block w-4 h-4 rounded border-2 border-teal-300 bg-white"
                                      :class="selected[{{ (int) $child->id }}] ? 'bg-teal-600 border-teal-600' : 'border-teal-300 bg-white'"></span>
                            </button>
                        @endif

                        <div class="min-w-0 flex-1">
                            <div class="flex flex-wrap items-center gap-x-2 gap-y-1">
                                <a href="{{ route('nursery.children.show', $child) }}" class="font-bold text-teal-950 text-base hover:underline">{{ $child->name }}</a>
                                @if($child->code)
                                    <span class="text-[11px] text-teal-800/55 tabular-nums">{{ $child->code }}</span>
                                @endif
                            </div>
                            @if($state === 'checked_out' && $log?->checked_in_at)
                                <p class="text-xs text-teal-800/70 mt-0.5">
                                    حضور {{ $log->checked_in_at->format('H:i') }}
                                    @if($log->checked_out_at) · انصراف {{ $log->checked_out_at->format('H:i') }} @endif
                                    @if($isLate) · <span class="font-semibold text-amber-700">متأخر</span> @endif
                                    @if($isEarly) · <span class="font-semibold text-teal-800">مغادرة مبكرة</span> @endif
                                </p>
                            @elseif($state === 'present' && $isLate)
                                <p class="text-xs font-semibold text-amber-700 mt-0.5">
                                    متأخر
                                    <x-info field="nursery.classroom_today_late" />
                                </p>
                            @endif
                        </div>

                        <span @class([
                            'shrink-0 inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold',
                            'bg-amber-50 text-amber-800 border border-amber-100' => $state === 'not_yet',
                            'bg-emerald-50 text-emerald-800 border border-emerald-100' => $state === 'present',
                            'bg-sky-50 text-sky-800 border border-sky-100' => $state === 'checked_out',
                        ])>
                            @if($state === 'not_yet') لم يحضر
                            @elseif($state === 'present') حاضر{{ $log?->checked_in_at ? ' '.$log->checked_in_at->format('H:i') : '' }}
                            @else انصرف{{ $log?->checked_out_at ? ' '.$log->checked_out_at->format('H:i') : '' }}
                            @endif
                        </span>
                    </div>

                    @if($canManageChildAttendance || $canManageChildActivity)
                        <div class="mt-3 pt-3 border-t border-teal-100/80 flex flex-wrap items-center gap-2">
                            @if($canManageChildAttendance)
                                @if($state === 'not_yet')
                                    <form method="post" action="{{ route('nursery.attendance.check-in') }}" class="shrink-0">
                                        @csrf
                                        <input type="hidden" name="child_id" value="{{ $child->id }}">
                                        <button type="submit" class="nursery-btn nursery-btn-primary min-w-[6.5rem] px-5 py-2.5 text-sm">حضور</button>
                                    </form>
                                @elseif($state === 'present')
                                    <form method="post" action="{{ route('nursery.attendance.check-out') }}" class="shrink-0">
                                        @csrf
                                        <input type="hidden" name="child_id" value="{{ $child->id }}">
                                        <button type="submit" class="nursery-btn nursery-btn-soft min-w-[6.5rem] px-5 py-2.5 text-sm">انصراف</button>
                                    </form>
                                @else
                                    <span class="inline-flex items-center rounded-lg border border-teal-100 bg-teal-50/60 px-3 py-2 text-xs font-semibold text-teal-800/75">تم الانصراف</span>
                                @endif

                                @if($log)
                                    <button type="button" class="nursery-btn nursery-btn-soft text-xs py-2 px-3"
                                            @click="openCorrect({{ (int) $log->id }}, @js($child->name), @js($log->checked_in_at?->format('H:i')), @js($log->checked_out_at?->format('H:i')), @js($correctStatus))">
                                        تصحيح
                                        <x-info field="nursery.classroom_today_correct" />
                                    </button>
                                @endif
                            @endif

                            @if($canManageChildActivity)
                                <div class="flex flex-wrap gap-1.5 {{ $canManageChildAttendance ? 'ms-auto' : '' }}">
                                    <button type="button" class="nursery-btn nursery-btn-soft text-xs py-1.5 px-2.5" @click="openCare({{ (int) $child->id }}, @js($child->name), 'meal')">وجبة</button>
                                    <button type="button" class="nursery-btn nursery-btn-soft text-xs py-1.5 px-2.5" @click="openCare({{ (int) $child->id }}, @js($child->name), 'mood')">مزاج</button>
                                    <button type="button" class="nursery-btn nursery-btn-soft text-xs py-1.5 px-2.5" @click="openCare({{ (int) $child->id }}, @js($child->name), 'diaper')">حفاض</button>
                                    <button type="button" class="nursery-btn nursery-btn-soft text-xs py-1.5 px-2.5" @click="openCare({{ (int) $child->id }}, @js($child->name), 'nap')">المزيد</button>
                                </div>
                            @endif
                        </div>
                    @endif
                </article>
            @empty
                <div class="nursery-card p-8 text-center">
                    <p class="text-teal-800/80 font-medium">لا يوجد أطفال مسجّلون في هذا الفصل.</p>
                </div>
            @endforelse
        </section>
    </div>

    @if($canManageChildAttendance)
        <div x-show="count() > 0" x-cloak class="nursery-today-bulk-bar">
            <div class="nursery-today-bulk-bar__inner nursery-card px-4 py-3 flex flex-wrap items-center justify-between gap-3">
                <p class="text-sm font-semibold text-teal-950 min-w-0">
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

        @include('nursery.classrooms.partials.today-bulk-confirm-modal')
        @include('nursery.classrooms.partials.today-correction-modal')
    @endif

    @if($canManageChildActivity)
        @include('nursery.classrooms.partials.today-care-modal')
    @endif
</div>
@endsection
