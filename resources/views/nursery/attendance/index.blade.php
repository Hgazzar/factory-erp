@extends('layouts.nursery')

@section('title', 'حضور وانصراف — '.niche_module_label('nursery'))

@section('content')
@php
    use App\Models\Nursery\AttendanceWeekdaySetting;
    use App\Models\Nursery\LeaveRecord;
    use App\Support\NurseryWeekdays;
    $weekdayOptions = NurseryWeekdays::selectOptions();
    $prevWeek = $weekStart->copy()->subWeek()->toDateString();
    $nextWeek = $weekStart->copy()->addWeek()->toDateString();
    $weekLabel = $weekStart->copy()->locale('ar')->translatedFormat('j F').' – '.$weekStart->copy()->endOfWeek(Carbon\Carbon::SATURDAY)->locale('ar')->translatedFormat('j F Y');
@endphp
<div class="w-full space-y-5" dir="rtl"
     x-data="{
        modal: null,
        leaveChildId: '',
        leaveEmployeeId: '',
        reportChildId: '',
        reportEmployeeId: '',
        openModal(name) { this.modal = name; },
        closeModal() { this.modal = null; }
     }">
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-extrabold text-orange-950">الحضور والانصراف</h1>
            <p class="text-sm text-orange-800/80 mt-1"><x-info field="nursery.nav_attendance" /> تسجيل يومي ومتابعة أسبوعية</p>
        </div>
    </div>

    @if(session('success'))
        <div class="nursery-card px-4 py-3 text-sm text-emerald-800 bg-emerald-50">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="nursery-card px-4 py-3 text-sm text-red-800 bg-red-50">{{ session('error') }}</div>
    @endif

    <nav class="nursery-attendance-tabs" aria-label="أقسام الحضور والانصراف">
        <a href="{{ route('nursery.attendance.index', ['tab' => 'register']) }}"
           class="nursery-attendance-tab {{ $tab === 'register' ? 'is-active' : '' }}">
            <span class="nursery-attendance-tab-icon" aria-hidden="true">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 13.5l10.5-11.25L12 10.5h8.25L9.75 21.75 12 13.5H3.75z"/></svg>
            </span>
            <span>
                <span class="nursery-attendance-tab-label">تسجيل سريع</span>
                <span class="nursery-attendance-tab-desc">أطفال + طاقم العمل</span>
            </span>
        </a>
        <a href="{{ route('nursery.attendance.index', ['tab' => 'children', 'week' => $weekStart->toDateString()]) }}"
           class="nursery-attendance-tab {{ $tab === 'children' ? 'is-active' : '' }}">
            <span class="nursery-attendance-tab-icon" aria-hidden="true">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z"/></svg>
            </span>
            <span>
                <span class="nursery-attendance-tab-label">{{ niche_label('entities.child', 'الأطفال') }}</span>
                <span class="nursery-attendance-tab-desc">متابعة أسبوعية</span>
            </span>
        </a>
        @if($canManageStaff || app(\App\Support\NurseryAccess::class)->allows(\App\Support\NurseryAccess::CAP_VIEW_STAFF))
            <a href="{{ route('nursery.attendance.index', ['tab' => 'staff', 'week' => $weekStart->toDateString()]) }}"
               class="nursery-attendance-tab {{ $tab === 'staff' ? 'is-active' : '' }}">
                <span class="nursery-attendance-tab-icon" aria-hidden="true">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M18 18.72a9.094 9.094 0 003.741-.479 3 3 0 00-4.682-2.72m.94 3.198l.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0112 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 016 18.719m12 0a5.971 5.971 0 00-.941-3.197m0 0A5.995 5.995 0 0012 12.75a5.995 5.995 0 00-5.058 2.772m0 0a3 3 0 00-4.681 2.72 8.986 8.986 0 003.74.477"/></svg>
                </span>
                <span>
                    <span class="nursery-attendance-tab-label">طاقم العمل</span>
                    <span class="nursery-attendance-tab-desc">متابعة أسبوعية</span>
                </span>
            </a>
        @else
            <span class="nursery-attendance-tab opacity-50 cursor-not-allowed" title="يتطلب صلاحية طاقم العمل">
                <span class="nursery-attendance-tab-icon" aria-hidden="true">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M18 18.72a9.094 9.094 0 003.741-.479 3 3 0 00-4.682-2.72m.94 3.198l.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0112 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 016 18.719m12 0a5.971 5.971 0 00-.941-3.197m0 0A5.995 5.995 0 0012 12.75a5.995 5.995 0 00-5.058 2.772m0 0a3 3 0 00-4.681 2.72 8.986 8.986 0 003.74.477"/></svg>
                </span>
                <span>
                    <span class="nursery-attendance-tab-label">طاقم العمل</span>
                    <span class="nursery-attendance-tab-desc">غير متاح</span>
                </span>
            </span>
        @endif
    </nav>

    @if($tab === 'register')
        @include('nursery.attendance.partials.register-tab')
    @elseif($tab === 'children')
        @include('nursery.attendance.partials.weekly-tab', [
            'scope' => LeaveRecord::SCOPE_CHILDREN,
            'grid' => $childrenGrid,
            'weekdaySetting' => $childrenWeekdays,
            'canManage' => $canManageChildren,
            'searchPlaceholder' => 'ابحث باسم الطفل...',
            'showClassroomFilter' => true,
        ])
    @elseif($tab === 'staff')
        @include('nursery.attendance.partials.weekly-tab', [
            'scope' => LeaveRecord::SCOPE_STAFF,
            'grid' => $staffGrid,
            'weekdaySetting' => $staffWeekdays,
            'canManage' => $canManageStaff,
            'searchPlaceholder' => 'ابحث باسم طاقم العمل...',
            'showClassroomFilter' => false,
        ])
    @endif

    @include('nursery.attendance.partials.modals')
</div>
@endsection
