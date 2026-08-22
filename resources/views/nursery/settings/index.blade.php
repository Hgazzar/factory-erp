@extends('layouts.nursery')

@section('title', 'إعدادات الحضانة')

@section('content')
@php
    $settingsTabs = [
        [
            'key' => 'branding',
            'label' => 'الهوية البصرية',
            'desc' => 'الشعار واللوجو',
            'icon' => 'photo',
        ],
        [
            'key' => 'account',
            'label' => 'إعدادات الحساب',
            'desc' => 'بيانات الحضانة والمدير',
            'icon' => 'building',
        ],
        [
            'key' => 'plans',
            'label' => 'خطط الاشتراك',
            'desc' => 'أسعار وضريبة',
            'icon' => 'card',
        ],
        [
            'key' => 'shifts',
            'label' => 'إدارة المناوبات',
            'desc' => 'أوقات العمل',
            'icon' => 'clock',
        ],
        [
            'key' => 'features',
            'label' => 'مزايا الحضانة',
            'desc' => 'بوابة وواتساب',
            'icon' => 'sparkles',
        ],
    ];
@endphp
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
            <nav class="nursery-settings-tabs" aria-label="أقسام الإعدادات">
                @foreach($settingsTabs as $settingsTab)
                    <a href="{{ route('nursery.settings.index', ['tab' => $settingsTab['key']]) }}"
                       class="nursery-settings-tab {{ $tab === $settingsTab['key'] ? 'is-active' : '' }}">
                        <span class="nursery-settings-tab-icon" aria-hidden="true">
                            @switch($settingsTab['icon'])
                                @case('photo')
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159m-1.5-1.5l1.409-1.409a2.25 2.25 0 013.182 0l2.909 2.909M3.75 21h16.5A2.25 2.25 0 0022.5 18.75V5.25A2.25 2.25 0 0020.25 3H3.75A2.25 2.25 0 001.5 5.25v13.5A2.25 2.25 0 003.75 21zm10.5-11.25h.008v.008h-.008V9.75zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0z"/></svg>
                                    @break
                                @case('building')
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 21h19.5m-18-18v18m10.5-18v18m6-13.5V21M6.75 6.75h.75m-.75 3h.75m-.75 3h.75m3-6h.75m-.75 3h.75m-.75 3h.75M6.75 21v-3.375c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21M3 3h12"/></svg>
                                    @break
                                @case('card')
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6m-6 2.25h3m-3.75 3h15a2.25 2.25 0 002.25-2.25V6.75A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25v10.5A2.25 2.25 0 004.5 19.5z"/></svg>
                                    @break
                                @case('clock')
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                    @break
                                @case('sparkles')
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09zM18.259 8.715L18 9.75l-.259-1.035a3.375 3.375 0 00-2.455-2.456L14.25 6l1.036-.259a3.375 3.375 0 002.455-2.456L18 2.25l.259 1.035a3.375 3.375 0 002.456 2.456L21.75 6l-1.035.259a3.375 3.375 0 00-2.456 2.456zM16.894 20.567L16.5 21.75l-.394-1.183a2.25 2.25 0 00-1.423-1.423L13.5 18.75l1.183-.394a2.25 2.25 0 001.423-1.423l.394-1.183.394 1.183a2.25 2.25 0 001.423 1.423l1.183.394-1.183.394a2.25 2.25 0 00-1.423 1.423z"/></svg>
                                    @break
                            @endswitch
                        </span>
                        <span>
                            <span class="nursery-settings-tab-label">{{ $settingsTab['label'] }}</span>
                            <span class="nursery-settings-tab-desc">{{ $settingsTab['desc'] }}</span>
                        </span>
                    </a>
                @endforeach
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
                        <div class="w-full h-full rounded-xl bg-gradient-to-br from-orange-400 to-orange-600 text-white flex items-center justify-center text-2xl font-bold">ن</div>
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
