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
            <span class="nursery-attendance-tab-icon" aria-hidden="true">⚡</span>
            <span class="nursery-attendance-tab-label">تسجيل سريع</span>
            <span class="nursery-attendance-tab-desc">أطفال + طاقم العمل</span>
        </a>
        <a href="{{ route('nursery.attendance.index', ['tab' => 'children', 'week' => $weekStart->toDateString()]) }}"
           class="nursery-attendance-tab {{ $tab === 'children' ? 'is-active' : '' }}">
            <span class="nursery-attendance-tab-icon" aria-hidden="true">👶</span>
            <span class="nursery-attendance-tab-label">{{ niche_label('entities.child', 'الأطفال') }}</span>
            <span class="nursery-attendance-tab-desc">متابعة أسبوعية</span>
        </a>
        @if($canManageStaff || app(\App\Support\NurseryAccess::class)->allows(\App\Support\NurseryAccess::CAP_VIEW_STAFF))
            <a href="{{ route('nursery.attendance.index', ['tab' => 'staff', 'week' => $weekStart->toDateString()]) }}"
               class="nursery-attendance-tab {{ $tab === 'staff' ? 'is-active' : '' }}">
                <span class="nursery-attendance-tab-icon" aria-hidden="true">👥</span>
                <span class="nursery-attendance-tab-label">طاقم العمل</span>
                <span class="nursery-attendance-tab-desc">متابعة أسبوعية</span>
            </a>
        @else
            <span class="nursery-attendance-tab opacity-50 cursor-not-allowed" title="يتطلب صلاحية طاقم العمل">
                <span class="nursery-attendance-tab-icon" aria-hidden="true">👥</span>
                <span class="nursery-attendance-tab-label">طاقم العمل</span>
                <span class="nursery-attendance-tab-desc">غير متاح</span>
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
