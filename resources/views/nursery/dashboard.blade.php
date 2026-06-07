@extends('layouts.nursery')

@section('title', 'لوحة التحكم — '.niche_module_label('nursery'))

@section('content')
<div class="w-full space-y-6" dir="rtl">
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-extrabold text-orange-950">لوحة التحكم</h1>
            <p class="mt-1 text-sm text-orange-800/80">
                ملخص يوم الحضانة والأرقام الأساسية
                <x-info field="nursery.dashboard_intro" />
            </p>
            <p class="text-xs text-orange-700/70 mt-1">تاريخ اليوم: {{ $board['date'] }}</p>
        </div>
        <a href="{{ route('nursery.attendance.index') }}" class="nursery-btn nursery-btn-primary">
            تسجيل حضور سريع
        </a>
    </div>

    <section>
        <h2 class="text-base font-bold text-orange-950 mb-3">أرقام المؤسسة</h2>
        <div class="grid gap-3 grid-cols-2 sm:grid-cols-3 lg:grid-cols-5">
            <div class="nursery-card p-4 text-center">
                <div class="flex items-center justify-center gap-1 flex-wrap mb-2">
                    <span class="text-sm font-bold text-orange-950">الأطفال النشطون</span>
                    <x-info field="nursery.stat_active_children" />
                </div>
                <p class="text-2xl font-extrabold text-orange-600 tabular-nums leading-none">{{ $overview['children'] }}</p>
            </div>
            <div class="nursery-card p-4 text-center">
                <div class="flex items-center justify-center gap-1 flex-wrap mb-2">
                    <span class="text-sm font-bold text-orange-950">طاقم العمل</span>
                    <x-info field="nursery.stat_staff" />
                </div>
                <p class="text-2xl font-extrabold text-orange-600 tabular-nums leading-none">{{ $overview['staff'] }}</p>
            </div>
            <div class="nursery-card p-4 text-center">
                <div class="flex items-center justify-center gap-1 flex-wrap mb-2">
                    <span class="text-sm font-bold text-orange-950">الفصول النشطة</span>
                    <x-info field="nursery.stat_classrooms" />
                </div>
                <p class="text-2xl font-extrabold text-orange-600 tabular-nums leading-none">{{ $overview['classrooms'] }}</p>
            </div>
            <div class="nursery-card p-4 text-center">
                <div class="flex items-center justify-center gap-1 flex-wrap mb-2">
                    <span class="text-sm font-bold text-orange-950">الوحدات النشطة</span>
                    <x-info field="nursery.stat_units" />
                </div>
                <p class="text-2xl font-extrabold text-orange-600 tabular-nums leading-none">{{ $overview['units'] }}</p>
                @if(app(\App\Support\NurseryAccess::class)->allows(\App\Support\NurseryAccess::CAP_VIEW_UNITS))
                    <a href="{{ route('nursery.units.index') }}" class="text-xs text-orange-600 font-semibold mt-2 inline-block hover:underline">عرض الوحدات</a>
                @endif
            </div>
            <div class="nursery-card p-4 text-center">
                <div class="flex items-center justify-center gap-1 flex-wrap mb-2">
                    <span class="text-sm font-bold text-orange-950">أحداث الأسبوع</span>
                    <x-info field="nursery.stat_activities" />
                </div>
                <p class="text-2xl font-extrabold text-orange-600 tabular-nums leading-none">{{ $overview['activities'] }}</p>
                @if(app(\App\Support\NurseryAccess::class)->allows(\App\Support\NurseryAccess::CAP_VIEW_CALENDAR))
                    <a href="{{ route('nursery.calendar.index') }}" class="text-xs text-orange-600 font-semibold mt-2 inline-block hover:underline">التقويم</a>
                @endif
            </div>
        </div>
    </section>

    @canFeature(\App\Support\PremiumFeatureKeys::NURSERY_PORTAL)
        <section id="portal" class="nursery-card p-5 border-orange-200 bg-orange-50/30">
            <h2 class="text-base font-bold text-orange-950 mb-2">بوابة أولياء الأمور <x-info field="nursery.portal_admin_intro" /></h2>
            @if(!empty($portalUrl))
                <p class="text-sm text-orange-800/80 mb-3 break-all" dir="ltr">{{ $portalUrl }}</p>
                <div class="flex flex-wrap gap-2 items-start">
                    <a href="{{ $portalUrl }}" target="_blank" rel="noopener" class="nursery-btn nursery-btn-primary text-sm">فتح البوابة</a>
                    <a href="{{ route('nursery.portal.qr-download') }}" class="nursery-btn nursery-btn-soft text-sm">تحميل QR</a>
                    @if(app(\App\Support\NurseryAccess::class)->allows(\App\Support\NurseryAccess::CAP_MANAGE_CHILDREN))
                        <a href="{{ route('nursery.guardians.index') }}" class="nursery-btn nursery-btn-soft text-sm">إدارة أولياء الأمور</a>
                    @endif
                    @if(($canManage ?? false) && empty($settings->logoUrl()))
                        <a href="{{ route('nursery.settings.index', ['tab' => 'branding']) }}" class="nursery-btn nursery-btn-soft text-sm">رفع شعار الحضانة</a>
                    @endif
                    @if(!empty($qrDataUri))
                        <img src="{{ $qrDataUri }}" alt="QR بوابة أولياء الأمور" class="h-28 w-28 rounded-lg border border-orange-200 bg-white p-1">
                    @endif
                </div>
            @else
                <p class="text-sm text-amber-800">فعّل slug المستأجر من إعدادات Super Admin لعرض رابط البوابة.</p>
            @endif
        </section>
    @endcanFeature

    @if(app(\App\Support\NurseryAccess::class)->allows(\App\Support\NurseryAccess::CAP_VIEW_SUBSCRIPTIONS))
        <section>
            <h2 class="text-base font-bold text-orange-950 mb-3">الاشتراكات <x-info field="nursery.dashboard_subscriptions" /></h2>
            <div class="grid gap-3 sm:grid-cols-3">
                <div class="nursery-card p-4 text-center">
                    <p class="text-sm font-semibold text-amber-800 mb-1">غير مدفوعة <x-info field="nursery.dashboard_unpaid_subs" /></p>
                    <p class="text-2xl font-extrabold text-amber-600 tabular-nums">{{ $subscriptionKpis['unpaid_active'] }}</p>
                </div>
                <div class="nursery-card p-4 text-center">
                    <p class="text-sm font-semibold text-orange-800 mb-1">تنتهي قريباً <x-info field="nursery.dashboard_expiring_subs" /></p>
                    <p class="text-2xl font-extrabold text-orange-600 tabular-nums">{{ $subscriptionKpis['expiring_soon'] }}</p>
                </div>
                <div class="nursery-card p-4 text-center">
                    <a href="{{ route('nursery.subscriptions.index') }}" class="nursery-btn nursery-btn-soft text-sm w-full">عرض الاشتراكات</a>
                </div>
            </div>
        </section>
    @endif

    <section>
        <h2 class="text-base font-bold text-orange-950 mb-3">حضور اليوم</h2>
        <div class="grid gap-4 sm:grid-cols-3">
            <div class="nursery-card p-4 text-center">
                <div class="flex items-center justify-center gap-1 flex-wrap mb-2">
                    <span class="text-sm font-bold text-emerald-800">حضور اليوم</span>
                    <x-info field="nursery.stat_present_today" />
                </div>
                <p class="text-3xl font-extrabold text-emerald-600 tabular-nums leading-none">{{ $stats['present_today'] }}</p>
                <p class="text-xs text-emerald-700/80 mt-2">داخل الحضانة الآن</p>
            </div>
            <div class="nursery-card p-4 text-center">
                <div class="flex items-center justify-center gap-1 flex-wrap mb-2">
                    <span class="text-sm font-bold text-amber-800">لم يُسجَّل حضورهم</span>
                    <x-info field="nursery.stat_waiting_today" />
                </div>
                <p class="text-3xl font-extrabold text-amber-600 tabular-nums leading-none">{{ $stats['waiting_today'] }}</p>
                <p class="text-xs text-amber-700/80 mt-2">بانتظار تسجيل الحضور</p>
            </div>
            <div class="nursery-card p-4 text-center">
                <div class="flex items-center justify-center gap-1 flex-wrap mb-2">
                    <span class="text-sm font-bold text-sky-800">انصراف اليوم</span>
                    <x-info field="nursery.stat_left_today" />
                </div>
                <p class="text-3xl font-extrabold text-sky-600 tabular-nums leading-none">{{ $stats['left_today'] }}</p>
                <p class="text-xs text-sky-700/80 mt-2">غادروا الحضانة اليوم</p>
            </div>
        </div>
    </section>

    <div class="grid gap-6 lg:grid-cols-2">
        <section class="nursery-card p-4">
            <h2 class="text-lg font-bold text-orange-950 mb-1">لم يحضروا بعد</h2>
            <p class="text-xs text-orange-800/70 mb-3">عدد الأطفال: {{ $board['not_yet']->count() }}</p>
            @forelse($board['not_yet'] as $child)
                <div class="flex items-center justify-between gap-2 py-2 border-b border-orange-100 last:border-0">
                    <div>
                        <p class="font-semibold text-orange-950">{{ $child->name }}</p>
                        <p class="text-xs text-orange-800/70">
                            الفصل: {{ $child->activeEnrollment?->classroom?->name ?? 'غير معين لفصل' }}
                        </p>
                    </div>
                    <form method="post" action="{{ route('nursery.attendance.check-in') }}">
                        @csrf
                        <input type="hidden" name="child_id" value="{{ $child->id }}">
                        <button type="submit" class="nursery-btn nursery-btn-primary text-sm py-1 px-3">تسجيل حضور</button>
                    </form>
                </div>
            @empty
                <p class="text-sm text-orange-800/60">كل الأطفال سجّلوا حضوراً 🎉</p>
            @endforelse
        </section>

        <section class="nursery-card p-4">
            <h2 class="text-lg font-bold text-orange-950 mb-1">داخل الحضانة</h2>
            <p class="text-xs text-orange-800/70 mb-3">عدد الأطفال: {{ $board['checked_in']->count() }}</p>
            @forelse($board['checked_in'] as $row)
                <div class="flex items-center justify-between gap-2 py-2 border-b border-orange-100 last:border-0">
                    <div>
                        <p class="font-semibold text-orange-950">{{ $row['child']->name }}</p>
                        <p class="text-xs text-emerald-700">وقت الحضور: {{ $row['log']->checked_in_at?->format('H:i') }}</p>
                    </div>
                    <form method="post" action="{{ route('nursery.attendance.check-out') }}">
                        @csrf
                        <input type="hidden" name="child_id" value="{{ $row['child']->id }}">
                        <button type="submit" class="nursery-btn nursery-btn-soft text-sm py-1 px-3">تسجيل انصراف</button>
                    </form>
                </div>
            @empty
                <p class="text-sm text-orange-800/60">لا يوجد أطفال داخل الحضانة.</p>
            @endforelse
        </section>
    </div>
</div>
@endsection
