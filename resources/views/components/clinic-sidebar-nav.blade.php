@php
    use App\Support\ClinicAccess;
    $clinicAccess = app(ClinicAccess::class);
@endphp
<nav class="flex flex-col gap-1">
    @if($clinicAccess->allows(ClinicAccess::CAP_VIEW_APPOINTMENTS))
    <a href="{{ route('clinic.dashboard') }}"
       class="module-nav-link {{ request()->routeIs('clinic.dashboard') ? 'active' : '' }}">
        <span><x-info field="clinic.nav_dashboard" /> لوحة العيادة</span>
    </a>
    <a href="{{ route('clinic.appointments.index') }}"
       class="module-nav-link {{ request()->routeIs('clinic.appointments.*') ? 'active' : '' }}">
        <span><x-info field="clinic.nav_appointments" /> الحجوزات</span>
    </a>
    @endif
    @if($clinicAccess->allows(ClinicAccess::CAP_VIEW_CLINICAL))
    <a href="{{ route('clinic.patients.index') }}"
       class="module-nav-link {{ request()->routeIs('clinic.patients.*') ? 'active' : '' }}">
        <span><x-info field="clinic.nav_patients" /> {{ niche_label('entities.customer', 'المرضى') }}</span>
    </a>
    <a href="{{ route('clinic.prescriptions.index') }}"
       class="module-nav-link {{ request()->routeIs('clinic.prescriptions.*') ? 'active' : '' }}">
        <span><x-info field="clinic.nav_prescriptions" /> الروشتات</span>
    </a>
    @endif
    @if($clinicAccess->allows(ClinicAccess::CAP_MANAGE_SERVICES))
    <a href="{{ route('clinic.services.index') }}"
       class="module-nav-link {{ request()->routeIs('clinic.services.*') ? 'active' : '' }}">
        <span><x-info field="clinic.nav_services" /> دليل الخدمات</span>
    </a>
    <a href="{{ route('clinic.doctor-schedules.index') }}"
       class="module-nav-link {{ request()->routeIs('clinic.doctor-schedules.*') || request()->routeIs('clinic.blocked-slots.*') ? 'active' : '' }}">
        <span><x-info field="clinic.nav_schedules" /> جداول الأطباء</span>
    </a>
    @endif
    @if($clinicAccess->isTenantOwner())
    <a href="{{ route('clinic.settings.index', ['tab' => 'branding']) }}"
       class="module-nav-link {{ request()->routeIs('clinic.settings.*') ? 'active' : '' }}">
        <span><x-info field="clinic.nav_settings" /> الهوية البصرية</span>
    </a>
    @endif
</nav>
