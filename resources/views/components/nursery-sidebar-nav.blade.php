@php
    use App\Support\NurseryAccess;
    $access = app(NurseryAccess::class);
@endphp
<nav class="flex flex-col gap-1">
    @if($access->allows(NurseryAccess::CAP_VIEW_DAILY))
        <a href="{{ route('nursery.dashboard') }}"
           class="module-nav-link {{ request()->routeIs('nursery.dashboard') ? 'active' : '' }}">
            <span>🏠 <x-info field="nursery.nav_dashboard" /> لوحة التحكم</span>
        </a>
        <a href="{{ route('nursery.attendance.index') }}"
           class="module-nav-link {{ request()->routeIs('nursery.attendance.*') ? 'active' : '' }}">
            <span>✅ <x-info field="nursery.nav_attendance" /> حضور وانصراف</span>
        </a>
        <a href="{{ route('nursery.children.index') }}"
           class="module-nav-link {{ request()->routeIs('nursery.children.*') ? 'active' : '' }}">
            <span>👶 <x-info field="nursery.nav_children" /> {{ niche_label('entities.child', 'الأطفال') }}</span>
        </a>
        @canFeature(\App\Support\PremiumFeatureKeys::NURSERY_PORTAL)
            @if($access->allows(NurseryAccess::CAP_VIEW_DAILY))
                <a href="{{ route('nursery.guardians.index') }}"
                   class="module-nav-link {{ request()->routeIs('nursery.guardians.*') ? 'active' : '' }}">
                    <span>👪 <x-info field="nursery.nav_guardians" /> أولياء الأمور</span>
                </a>
            @endif
        @endcanFeature
        @if($access->allows(\App\Support\NurseryAccess::CAP_VIEW_STAFF))
            <a href="{{ route('nursery.staff.index') }}"
               class="module-nav-link {{ request()->routeIs('nursery.staff.*') ? 'active' : '' }}">
                <span>👥 <x-info field="nursery.nav_staff" /> طاقم العمل</span>
            </a>
        @endif
    @endif
    @if($access->allows(NurseryAccess::CAP_MANAGE_CLASSROOMS))
        <a href="{{ route('nursery.classrooms.index') }}"
           class="module-nav-link {{ request()->routeIs('nursery.classrooms.*') ? 'active' : '' }}">
            <span>🎨 <x-info field="nursery.nav_classrooms" /> {{ niche_label('entities.classroom', 'الفصول') }}</span>
        </a>
    @endif
    @if($access->allows(NurseryAccess::CAP_VIEW_UNITS))
        <a href="{{ route('nursery.units.index') }}"
           class="module-nav-link {{ request()->routeIs('nursery.units.*') ? 'active' : '' }}">
            <span>📚 <x-info field="nursery.nav_units" /> الوحدات</span>
        </a>
    @endif
    @if($access->allows(NurseryAccess::CAP_VIEW_CALENDAR))
        <a href="{{ route('nursery.calendar.index') }}"
           class="module-nav-link {{ request()->routeIs('nursery.calendar.*') ? 'active' : '' }}">
            <span>📅 <x-info field="nursery.nav_calendar" /> التقويم</span>
        </a>
    @endif
    @if($access->allows(NurseryAccess::CAP_VIEW_SUBSCRIPTIONS))
        <a href="{{ route('nursery.subscriptions.index') }}"
           class="module-nav-link {{ request()->routeIs('nursery.subscriptions.*') ? 'active' : '' }}">
            <span>💳 <x-info field="nursery.nav_subscriptions" /> الاشتراكات</span>
        </a>
    @endif
    @if($access->allows(NurseryAccess::CAP_MANAGE_SETTINGS))
        <a href="{{ route('nursery.settings.index') }}"
           class="module-nav-link {{ request()->routeIs('nursery.settings.*') ? 'active' : '' }}">
            <span>⚙️ <x-info field="nursery.nav_settings" /> الإعدادات</span>
        </a>
    @endif
</nav>
