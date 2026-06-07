@extends('layouts.nursery')

@section('title', 'أولياء الأمور')

@section('content')
<div class="w-full space-y-6" dir="rtl">
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-extrabold text-orange-950">أولياء الأمور</h1>
            <p class="text-sm text-orange-800/80">
                <x-info field="nursery.guardians_admin_intro" />
                إدارة بوابة أولياء الأمور والدعوات
            </p>
        </div>
        @if($portalEnabled)
            <a href="{{ route('nursery.dashboard') }}#portal" class="nursery-btn nursery-btn-soft text-sm">رابط البوابة العام</a>
        @endif
    </div>

    @unless($portalEnabled)
        <div class="nursery-card p-4 border-amber-200 bg-amber-50/60 text-sm text-amber-950">
            ميزة بوابة أولياء الأمور غير مفعّلة.
            <a href="{{ route('nursery.settings.index', ['tab' => 'features']) }}" class="font-semibold text-orange-700 underline">فعّلها من إعدادات الحضانة → مزايا الحضانة</a>
        </div>
    @endunless

    <div class="grid gap-3 sm:grid-cols-3">
        <div class="nursery-card p-4 text-center">
            <p class="text-sm font-bold text-orange-950">إجمالي أولياء الأمور <x-info field="nursery.guardians_total" /></p>
            <p class="text-2xl font-extrabold text-orange-600 tabular-nums">{{ $listStats['total'] }}</p>
        </div>
        <div class="nursery-card p-4 text-center">
            <p class="text-sm font-bold text-orange-950">بوابة مفعّلة <x-info field="nursery.guardians_portal_active" /></p>
            <p class="text-2xl font-extrabold text-emerald-600 tabular-nums">{{ $listStats['portal_active'] }}</p>
        </div>
        <div class="nursery-card p-4 text-center">
            <p class="text-sm font-bold text-orange-950">دخلوا البوابة <x-info field="nursery.guardians_logged_in" /></p>
            <p class="text-2xl font-extrabold text-sky-600 tabular-nums">{{ $listStats['logged_in'] }}</p>
        </div>
    </div>

    <form method="get" class="nursery-card p-4 flex flex-wrap gap-3 items-end">
        <div class="flex-1 min-w-[200px]">
            <label class="block text-sm font-semibold text-orange-950 mb-1">
                بحث
                <x-info field="nursery.guardians_search" />
            </label>
            <input type="search" name="q" value="{{ $q }}"
                   placeholder="اسم ولي الأمر أو رقم الجوال"
                   class="w-full rounded-lg border border-orange-200 px-3 py-2 text-sm">
        </div>
        <button type="submit" class="nursery-btn nursery-btn-soft">تطبيق</button>
        @if($q !== '')
            <a href="{{ route('nursery.guardians.index') }}" class="nursery-btn nursery-btn-soft">مسح</a>
        @endif
    </form>

    <section class="nursery-card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm min-w-[960px]">
                <thead>
                    <tr class="bg-orange-50/80 border-b border-orange-100">
                        <th class="px-4 py-3 text-right font-bold text-orange-950">ولي الأمر <x-info field="nursery.guardian_name" /></th>
                        <th class="px-4 py-3 text-right font-bold text-orange-950">الجوال <x-info field="nursery.guardian_phone" /></th>
                        <th class="px-4 py-3 text-right font-bold text-orange-950">البريد <x-info field="nursery.guardian_email" /></th>
                        <th class="px-4 py-3 text-right font-bold text-orange-950">الأطفال <x-info field="nursery.guardians_children_count" /></th>
                        <th class="px-4 py-3 text-right font-bold text-orange-950">البوابة <x-info field="nursery.guardians_portal_status" /></th>
                        <th class="px-4 py-3 text-right font-bold text-orange-950">آخر دخول <x-info field="nursery.guardians_last_login" /></th>
                        <th class="px-4 py-3 text-right font-bold text-orange-950 w-56">إجراءات</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($guardians as $guardian)
                        @php
                            $portalActive = trim((string) $guardian->portal_access_token) !== '';
                        @endphp
                        <tr class="border-b border-orange-50 hover:bg-orange-50/50">
                            <td class="px-4 py-3">
                                <a href="{{ route('nursery.guardians.show', $guardian) }}"
                                   class="font-semibold text-orange-950 hover:text-orange-600 hover:underline">
                                    {{ $guardian->name }}
                                </a>
                            </td>
                            <td class="px-4 py-3 tabular-nums" dir="ltr">{{ $guardian->phone ?: '—' }}</td>
                            <td class="px-4 py-3 text-xs" dir="ltr">{{ $guardian->email ?: '—' }}</td>
                            <td class="px-4 py-3 text-center tabular-nums">{{ $guardian->children_count }}</td>
                            <td class="px-4 py-3">
                                @if($portalActive)
                                    <span class="inline-flex items-center rounded-full bg-emerald-100 px-2.5 py-0.5 text-xs font-bold text-emerald-800">مفعّل</span>
                                @else
                                    <span class="inline-flex items-center rounded-full bg-gray-100 px-2.5 py-0.5 text-xs font-bold text-gray-600">غير مفعّل</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-xs text-orange-800/80">
                                {{ $guardian->portal_last_login_at?->format('Y-m-d H:i') ?? '—' }}
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex flex-wrap gap-1.5">
                                    <a href="{{ route('nursery.guardians.show', $guardian) }}"
                                       class="nursery-btn nursery-btn-soft text-xs py-1 px-2">الأطفال</a>
                                    @if($canManage && $portalEnabled)
                                        <form method="post" action="{{ route('nursery.guardians.portal-invite', $guardian) }}" class="inline">
                                            @csrf
                                            <button type="submit" class="nursery-btn nursery-btn-soft text-xs py-1 px-2">إعادة الدعوة</button>
                                        </form>
                                        @if($portalActive)
                                            <form method="post"
                                                  action="{{ route('nursery.guardians.revoke-portal', $guardian) }}"
                                                  class="inline"
                                                  onsubmit="return confirm('إلغاء وصول هذا ولي الأمر للبوابة؟');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="nursery-btn nursery-btn-soft text-xs py-1 px-2 text-red-700 border-red-200">إلغاء الوصول</button>
                                            </form>
                                        @endif
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-10 text-center text-orange-800/70">
                                لا يوجد أولياء أمور مسجّلون.
                                @if($canManage)
                                    <a href="{{ route('nursery.children.create') }}" class="text-orange-600 font-semibold underline">سجّل طفلاً جديداً</a>
                                @endif
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    <div>{{ $guardians->links() }}</div>
</div>
@endsection
