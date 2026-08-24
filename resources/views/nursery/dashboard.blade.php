@extends('layouts.nursery')

@section('title', 'لوحة التحكم — '.niche_module_label('nursery'))
@section('topbar_subtitle', 'ملخص يوم الحضانة والأرقام الأساسية')

@section('content')
@php
    $access = app(\App\Support\NurseryAccess::class);
    $canViewUnits = $access->allows(\App\Support\NurseryAccess::CAP_VIEW_UNITS);
    $canViewCalendar = $access->allows(\App\Support\NurseryAccess::CAP_VIEW_CALENDAR);
    $canViewSubs = $access->allows(\App\Support\NurseryAccess::CAP_VIEW_SUBSCRIPTIONS);
    $canViewStaff = $access->allows(\App\Support\NurseryAccess::CAP_VIEW_STAFF);
    $canViewChildren = $access->allows(\App\Support\NurseryAccess::CAP_MANAGE_CHILDREN)
        || $access->allows(\App\Support\NurseryAccess::CAP_VIEW_DAILY);
    $canViewClassrooms = $access->allows(\App\Support\NurseryAccess::CAP_MANAGE_CLASSROOMS)
        || $access->allows(\App\Support\NurseryAccess::CAP_VIEW_DAILY);
    $canViewAttendance = $access->allows(\App\Support\NurseryAccess::CAP_MANAGE_CHILD_ATTENDANCE)
        || $access->allows(\App\Support\NurseryAccess::CAP_VIEW_DAILY);
@endphp
<div class="w-full space-y-6" dir="rtl">
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-extrabold text-teal-950">لوحة التحكم</h1>
            <p class="mt-1 text-sm text-teal-800/80">
                ملخص يوم الحضانة والأرقام الأساسية
                <x-info field="nursery.dashboard_intro" />
            </p>
            <p class="text-xs text-teal-700/70 mt-1">تاريخ اليوم: {{ $board['date'] }}</p>
        </div>
        <a href="{{ route('nursery.attendance.index') }}" class="nursery-btn nursery-btn-primary">
            تسجيل حضور سريع
        </a>
    </div>

    <section class="nursery-stats-row">
        <x-nursery-stat-card title="الأطفال النشطون" :value="$overview['children']" info="nursery.stat_active_children" tone="primary" hint="مسجّلون حالياً" spark="bars"
            :percent="$spark['children']['percent']" :trend="$spark['children']['trend']"
            :href="$canViewChildren ? route('nursery.children.index') : null" :link-label="$canViewChildren ? 'الأطفال' : null" />
        <x-nursery-stat-card title="طاقم العمل" :value="$overview['staff']" info="nursery.stat_staff" tone="info" hint="موظفون نشطون" spark="line"
            :percent="$spark['staff']['percent']" :trend="$spark['staff']['trend']"
            :href="$canViewStaff ? route('nursery.staff.index') : null" :link-label="$canViewStaff ? 'الطاقم' : null" />
        <x-nursery-stat-card title="الفصول النشطة" :value="$overview['classrooms']" info="nursery.stat_classrooms" tone="success" hint="فصول جاهزة" spark="ring"
            :percent="$spark['classrooms']['percent']" :trend="$spark['classrooms']['trend']"
            :href="$canViewClassrooms ? route('nursery.classrooms.index') : null" :link-label="$canViewClassrooms ? 'الفصول' : null" />
    </section>

    <section class="nursery-stats-row">
        <x-nursery-stat-card title="حضور اليوم" :value="$stats['present_today']" info="nursery.stat_present_today" tone="success" hint="داخل الحضانة الآن" spark="ring"
            :percent="$spark['present_today']['percent']" :trend="$spark['present_today']['trend']"
            :href="$canViewAttendance ? route('nursery.attendance.index') : null" :link-label="$canViewAttendance ? 'الحضور' : null" />
        <x-nursery-stat-card title="بانتظار التسجيل" :value="$stats['waiting_today']" info="nursery.stat_waiting_today" tone="warning" hint="لم يُسجَّل حضورهم" spark="bars"
            :percent="$spark['waiting_today']['percent']" :trend="$spark['waiting_today']['trend']"
            :href="$canViewAttendance ? route('nursery.attendance.index', ['tab' => 'register']) : null" :link-label="$canViewAttendance ? 'تسجيل' : null" />
        <x-nursery-stat-card title="انصراف اليوم" :value="$stats['left_today']" info="nursery.stat_left_today" tone="info" hint="غادروا الحضانة" spark="line"
            :percent="$spark['left_today']['percent']" :trend="$spark['left_today']['trend']"
            :href="$canViewAttendance ? route('nursery.attendance.index') : null" :link-label="$canViewAttendance ? 'الحضور' : null" />
    </section>

    @if($canViewSubs)
        <section class="nursery-stats-row">
            <x-nursery-stat-card title="غير مدفوعة" :value="$subscriptionKpis['unpaid_active']" info="nursery.dashboard_unpaid_subs" tone="warning" hint="بانتظار الدفع" spark="bars"
                :percent="$spark['unpaid_active']['percent']" :trend="$spark['unpaid_active']['trend']"
                :href="route('nursery.subscriptions.index')" link-label="الاشتراكات" />
            <x-nursery-stat-card title="تنتهي قريباً" :value="$subscriptionKpis['expiring_soon']" info="nursery.dashboard_expiring_subs" tone="primary" hint="تحتاج تجديد" spark="line"
                :percent="$spark['expiring_soon']['percent']" :trend="$spark['expiring_soon']['trend']"
                :href="route('nursery.subscriptions.index')" link-label="متابعة" />
            <x-nursery-stat-card title="الوحدات النشطة" :value="$overview['units']" info="nursery.stat_units" tone="warning" hint="منهج نشط" spark="bars"
                :percent="$spark['units']['percent']" :trend="$spark['units']['trend']"
                :href="$canViewUnits ? route('nursery.units.index') : null" :link-label="$canViewUnits ? 'الوحدات' : null" />
        </section>
    @else
        <section class="nursery-stats-row">
            <x-nursery-stat-card title="الوحدات النشطة" :value="$overview['units']" info="nursery.stat_units" tone="warning" hint="منهج نشط" spark="bars"
                :percent="$spark['units']['percent']" :trend="$spark['units']['trend']"
                :href="$canViewUnits ? route('nursery.units.index') : null" :link-label="$canViewUnits ? 'الوحدات' : null" />
            <x-nursery-stat-card title="أحداث الأسبوع" :value="$overview['activities']" info="nursery.stat_activities" tone="muted" hint="هذا الأسبوع" spark="line"
                :percent="$spark['activities']['percent']" :trend="$spark['activities']['trend']"
                :href="$canViewCalendar ? route('nursery.calendar.index') : null" :link-label="$canViewCalendar ? 'التقويم' : null" />
        </section>
    @endif

    @canFeature(\App\Support\PremiumFeatureKeys::NURSERY_PORTAL)
        <section id="portal" class="nursery-card p-5">
            <div class="nursery-panel-head">
                <div class="nursery-panel-head__meta">
                    <span class="nursery-panel-head__icon" aria-hidden="true">
                        <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15m3 0l3-3m0 0l-3-3m3 3H9"/></svg>
                    </span>
                    <div>
                        <h2 class="nursery-panel-head__title">
                            بوابة أولياء الأمور
                            <x-info field="nursery.portal_admin_intro" />
                        </h2>
                        <p class="nursery-panel-head__sub">رابط الدخول ورمز QR لأولياء الأمور</p>
                    </div>
                </div>
            </div>

            @if(!empty($portalUrl))
                <div class="flex flex-col sm:flex-row sm:items-stretch gap-4">
                    @if(!empty($qrDataUri))
                        <div class="shrink-0 flex flex-col items-center gap-2 rounded-2xl border border-teal-100 bg-teal-50/40 p-3 self-start">
                            <img src="{{ $qrDataUri }}" alt="QR بوابة أولياء الأمور" class="h-24 w-24 sm:h-28 sm:w-28 rounded-md">
                            <a href="{{ route('nursery.portal.qr-download') }}" class="nursery-btn nursery-btn-soft text-xs py-1.5 px-3 w-full justify-center">تحميل QR</a>
                        </div>
                    @endif

                    <div class="min-w-0 flex-1 flex flex-col gap-3" x-data="{ copied: false }">
                        <div class="rounded-2xl border border-teal-100 bg-teal-50/30 p-3">
                            <p class="text-[11px] font-semibold text-teal-800/70 mb-2">رابط البوابة</p>
                            <div class="flex flex-wrap items-center gap-2 rounded-xl border border-teal-100 bg-white px-3 py-2">
                                <p class="min-w-0 flex-1 text-sm text-teal-950 font-medium break-all leading-relaxed" dir="ltr">{{ $portalUrl }}</p>
                                <button type="button"
                                        class="nursery-btn nursery-btn-soft text-xs py-2 px-3 shrink-0 relative"
                                        title="نسخ الرابط"
                                        @click="navigator.clipboard.writeText(@js($portalUrl)).then(() => { copied = true; setTimeout(() => copied = false, 1500); }).catch(() => {})">
                                    <span x-show="!copied">نسخ الرابط</span>
                                    <span x-show="copied" x-cloak class="text-emerald-700 font-semibold">تم النسخ ✓</span>
                                </button>
                            </div>
                        </div>

                        <div class="flex flex-wrap gap-2">
                            <a href="{{ $portalUrl }}" target="_blank" rel="noopener" class="nursery-btn nursery-btn-primary text-sm px-4 py-2">فتح البوابة</a>
                            @if(empty($qrDataUri))
                                <a href="{{ route('nursery.portal.qr-download') }}" class="nursery-btn nursery-btn-soft text-sm px-4 py-2">تحميل QR</a>
                            @endif
                            @if(app(\App\Support\NurseryAccess::class)->allows(\App\Support\NurseryAccess::CAP_MANAGE_CHILDREN))
                                <a href="{{ route('nursery.guardians.index') }}" class="nursery-btn nursery-btn-soft text-sm px-4 py-2">إدارة أولياء الأمور</a>
                            @endif
                            @if(($canManage ?? false) && empty($settings->logoUrl()))
                                <a href="{{ route('nursery.settings.index', ['tab' => 'branding']) }}" class="nursery-btn nursery-btn-soft text-sm px-4 py-2">رفع شعار الحضانة</a>
                            @endif
                        </div>
                    </div>
                </div>
            @else
                <p class="text-sm text-amber-800">فعّل slug المستأجر من إعدادات Super Admin لعرض رابط البوابة.</p>
            @endif
        </section>
    @endcanFeature

    @if(! empty($storeOnlinePanel))
        <x-store.online-metrics-panel :panel="$storeOnlinePanel" />
    @endif

    <div class="grid gap-6 lg:grid-cols-2 xl:grid-cols-3">
        <section class="nursery-card p-5 lg:col-span-1">
            <div class="nursery-panel-head">
                <div class="nursery-panel-head__meta">
                    <span class="nursery-panel-head__icon" aria-hidden="true">
                        <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </span>
                    <div>
                        <h2 class="nursery-panel-head__title">لم يحضروا بعد</h2>
                        <p class="nursery-panel-head__sub">عدد الأطفال: {{ $board['not_yet']->count() }}</p>
                    </div>
                </div>
            </div>
            @forelse($board['not_yet'] as $child)
                <div class="nursery-list-row">
                    <x-nursery-person-avatar :name="$child->name" />
                    <div class="min-w-0 flex-1">
                        <p class="font-semibold text-teal-950 truncate">{{ $child->name }}</p>
                        <p class="text-xs text-teal-800/70 truncate">
                            الفصل: {{ $child->activeEnrollment?->classroom?->name ?? 'غير معين لفصل' }}
                        </p>
                    </div>
                    @if($canManageChildAttendance)
                    <form method="post" action="{{ route('nursery.attendance.check-in') }}" class="shrink-0">
                        @csrf
                        <input type="hidden" name="child_id" value="{{ $child->id }}">
                        <button type="submit" class="nursery-btn nursery-btn-primary text-sm py-1.5 px-3">تسجيل حضور</button>
                    </form>
                    @endif
                </div>
            @empty
                <p class="text-sm text-teal-800/60 py-6 text-center">كل الأطفال سجّلوا حضوراً</p>
            @endforelse
        </section>

        <section class="nursery-card p-5 lg:col-span-1">
            <div class="nursery-panel-head">
                <div class="nursery-panel-head__meta">
                    <span class="nursery-panel-head__icon" style="background:#ECFDF5;color:#059669;" aria-hidden="true">
                        <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </span>
                    <div>
                        <h2 class="nursery-panel-head__title">داخل الحضانة</h2>
                        <p class="nursery-panel-head__sub">عدد الأطفال: {{ $board['checked_in']->count() }}</p>
                    </div>
                </div>
            </div>
            @forelse($board['checked_in'] as $row)
                <div class="nursery-list-row">
                    <x-nursery-person-avatar :name="$row['child']->name" class="bg-emerald-100 text-emerald-700" />
                    <div class="min-w-0 flex-1">
                        <p class="font-semibold text-teal-950 truncate">{{ $row['child']->name }}</p>
                        <p class="text-xs text-emerald-700">وقت الحضور: {{ $row['log']->checked_in_at?->format('H:i') }}</p>
                    </div>
                    @if($canManageChildAttendance)
                    <form method="post" action="{{ route('nursery.attendance.check-out') }}" class="shrink-0">
                        @csrf
                        <input type="hidden" name="child_id" value="{{ $row['child']->id }}">
                        <button type="submit" class="nursery-btn nursery-btn-soft text-sm py-1.5 px-3">تسجيل انصراف</button>
                    </form>
                    @endif
                </div>
            @empty
                <p class="text-sm text-teal-800/60 py-6 text-center">لا يوجد أطفال داخل الحضانة.</p>
            @endforelse
        </section>

        <section class="nursery-card p-5 lg:col-span-1">
            <div class="nursery-panel-head">
                <div class="nursery-panel-head__meta">
                    <span class="nursery-panel-head__icon" style="background:#EFF6FF;color:#2563EB;" aria-hidden="true">
                        <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15M12 9l-3 3m0 0l3 3m-3-3h12.75"/></svg>
                    </span>
                    <div>
                        <h2 class="nursery-panel-head__title">انصرفوا اليوم</h2>
                        <p class="nursery-panel-head__sub">عدد الأطفال: {{ $board['checked_out']->count() }}</p>
                    </div>
                </div>
            </div>
            @forelse($board['checked_out'] as $row)
                <div class="nursery-list-row">
                    <x-nursery-person-avatar :name="$row['child']->name" class="bg-sky-100 text-sky-700" />
                    <div class="min-w-0 flex-1">
                        <p class="font-semibold text-teal-950 truncate">{{ $row['child']->name }}</p>
                        <p class="text-xs text-sky-700">
                            انصراف: {{ $row['log']->checked_out_at?->format('H:i') ?? '—' }}
                        </p>
                    </div>
                </div>
            @empty
                <p class="text-sm text-teal-800/60 py-6 text-center">لا يوجد انصراف مسجّل بعد.</p>
            @endforelse
        </section>
    </div>
</div>
@endsection
