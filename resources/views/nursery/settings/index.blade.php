@extends('layouts.nursery')

@section('title', 'إعدادات الحضانة')

@section('content')
<div class="w-full space-y-5" dir="rtl">
    <div>
        <h1 class="text-2xl font-extrabold text-orange-950">الإعدادات</h1>
        <p class="text-sm text-orange-800/80 mt-1">
            <x-info field="nursery.nav_settings" />
            إعدادات خاصة بحضانتك فقط — {{ $settings->portalDisplayName() }}
        </p>
    </div>

    @if(session('success'))
        <div class="nursery-card px-4 py-3 text-sm text-emerald-800 bg-emerald-50">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="nursery-card px-4 py-3 text-sm text-red-800 bg-red-50">{{ session('error') }}</div>
    @endif

    <div class="grid gap-5 xl:grid-cols-[minmax(0,1fr)_17rem]">
        <div class="space-y-5 min-w-0">
            <nav class="nursery-attendance-tabs" aria-label="أقسام الإعدادات">
                <a href="{{ route('nursery.settings.index', ['tab' => 'branding']) }}"
                   class="nursery-attendance-tab {{ $tab === 'branding' ? 'is-active' : '' }}">
                    <span class="nursery-attendance-tab-icon" aria-hidden="true">🖼️</span>
                    <span class="nursery-attendance-tab-label">الهوية البصرية</span>
                    <span class="nursery-attendance-tab-desc">الشعار واللوجو</span>
                </a>
                <a href="{{ route('nursery.settings.index', ['tab' => 'account']) }}"
                   class="nursery-attendance-tab {{ $tab === 'account' ? 'is-active' : '' }}">
                    <span class="nursery-attendance-tab-icon" aria-hidden="true">🏫</span>
                    <span class="nursery-attendance-tab-label">إعدادات الحساب</span>
                    <span class="nursery-attendance-tab-desc">بيانات الحضانة والمدير</span>
                </a>
                <a href="{{ route('nursery.settings.index', ['tab' => 'plans']) }}"
                   class="nursery-attendance-tab {{ $tab === 'plans' ? 'is-active' : '' }}">
                    <span class="nursery-attendance-tab-icon" aria-hidden="true">💳</span>
                    <span class="nursery-attendance-tab-label">خطط الاشتراك</span>
                    <span class="nursery-attendance-tab-desc">أسعار وضريبة</span>
                </a>
                <a href="{{ route('nursery.settings.index', ['tab' => 'shifts']) }}"
                   class="nursery-attendance-tab {{ $tab === 'shifts' ? 'is-active' : '' }}">
                    <span class="nursery-attendance-tab-icon" aria-hidden="true">🕐</span>
                    <span class="nursery-attendance-tab-label">إدارة المناوبات</span>
                    <span class="nursery-attendance-tab-desc">أوقات العمل</span>
                </a>
                <a href="{{ route('nursery.settings.index', ['tab' => 'features']) }}"
                   class="nursery-attendance-tab {{ $tab === 'features' ? 'is-active' : '' }}">
                    <span class="nursery-attendance-tab-icon" aria-hidden="true">✨</span>
                    <span class="nursery-attendance-tab-label">مزايا الحضانة</span>
                    <span class="nursery-attendance-tab-desc">بوابة وواتساب</span>
                </a>
            </nav>

            @if($tab === 'account')
                @include('nursery.settings.partials.account-tab')
            @elseif($tab === 'branding')
                @include('nursery.settings.partials.branding-tab')
            @elseif($tab === 'plans')
                @include('nursery.settings.partials.plans-tab')
            @elseif($tab === 'features')
                @include('nursery.settings.partials.features-tab')
            @else
                @include('nursery.settings.partials.shifts-tab')
            @endif
        </div>

        <aside class="space-y-4">
            <div class="nursery-card p-5 text-center">
                <div class="w-16 h-16 mx-auto rounded-2xl border-2 border-orange-200 bg-white flex items-center justify-center overflow-hidden p-1.5 mb-3 shadow-sm">
                    @if($settings->logoUrl())
                        <img src="{{ $settings->logoUrl() }}" alt="" class="max-w-full max-h-full object-contain">
                    @else
                        <div class="w-full h-full rounded-xl bg-gradient-to-br from-orange-400 to-orange-600 text-white flex items-center justify-center text-2xl">🧸</div>
                    @endif
                </div>
                <h2 class="text-lg font-extrabold text-orange-950">{{ $settings->portalDisplayName() }}</h2>
                @if($canManage)
                    <a href="{{ route('nursery.settings.index', ['tab' => 'branding']) }}" class="text-xs text-orange-600 font-semibold mt-2 inline-block hover:underline">تعديل الشعار ←</a>
                @endif
                @if($joinedAt)
                    <p class="text-xs text-orange-700/70 mt-1">تم الانضمام في {{ $joinedAt->locale('ar')->translatedFormat('j F Y') }}</p>
                @endif
            </div>
            <div class="grid grid-cols-2 gap-2">
                <div class="nursery-card p-3 text-center">
                    <p class="text-xs font-semibold text-orange-950">الأطفال</p>
                    <p class="text-xl font-extrabold text-orange-600 tabular-nums">{{ $overview['children'] }}</p>
                </div>
                <div class="nursery-card p-3 text-center">
                    <p class="text-xs font-semibold text-orange-950">طاقم العمل</p>
                    <p class="text-xl font-extrabold text-orange-600 tabular-nums">{{ $overview['staff'] }}</p>
                </div>
                <div class="nursery-card p-3 text-center">
                    <p class="text-xs font-semibold text-orange-950">الفصول</p>
                    <p class="text-xl font-extrabold text-orange-600 tabular-nums">{{ $overview['classrooms'] }}</p>
                </div>
                <div class="nursery-card p-3 text-center">
                    <p class="text-xs font-semibold text-orange-950">الوحدات</p>
                    <p class="text-xl font-extrabold text-orange-600 tabular-nums">{{ $overview['units'] }}</p>
                </div>
            </div>
        </aside>
    </div>
</div>
@endsection
