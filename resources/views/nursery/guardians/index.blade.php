@extends('layouts.nursery')

@section('title', 'أولياء الأمور')

@section('content')
<div class="w-full space-y-6" dir="rtl">
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-extrabold text-teal-950">أولياء الأمور</h1>
            <p class="text-sm text-teal-800/80">
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
            <a href="{{ route('nursery.settings.index', ['tab' => 'features']) }}" class="font-semibold text-teal-700 underline">فعّلها من إعدادات الحضانة → مزايا الحضانة</a>
        </div>
    @endunless

    <div class="nursery-stats-row">
        <x-nursery-stat-card title="إجمالي أولياء الأمور" :value="$listStats['total']" info="nursery.guardians_total" tone="primary" hint="كل السجلات" spark="bars"
            :percent="$spark['total']['percent']" :trend="$spark['total']['trend']" />
        <x-nursery-stat-card title="بوابة مفعّلة" :value="$listStats['portal_active']" info="nursery.guardians_portal_active" tone="success" hint="لديهم وصول" spark="ring"
            :percent="$spark['portal_active']['percent']" :trend="$spark['portal_active']['trend']" />
        <x-nursery-stat-card title="دخلوا البوابة" :value="$listStats['logged_in']" info="nursery.guardians_logged_in" tone="info" hint="سجّلوا دخولاً" spark="line"
            :percent="$spark['logged_in']['percent']" :trend="$spark['logged_in']['trend']" />
    </div>

    <form method="get" class="nursery-card p-4 flex flex-wrap gap-3 items-end">
        <div class="flex-1 min-w-[200px]">
            <label class="block text-sm font-semibold text-teal-950 mb-1">
                بحث
                <x-info field="nursery.guardians_search" />
            </label>
            <input type="search" name="q" value="{{ $q }}"
                   placeholder="اسم ولي الأمر أو رقم الجوال"
                   class="w-full rounded-lg border border-teal-200 px-3 py-2 text-sm">
        </div>
        <button type="submit" class="nursery-btn nursery-btn-soft">تطبيق</button>
        @if($q !== '')
            <a href="{{ route('nursery.guardians.index') }}" class="nursery-btn nursery-btn-soft">مسح</a>
        @endif
    </form>

    <section class="nursery-card nursery-table-card">
        <div class="nursery-table-card__toolbar">
            <div>
                <h2>قائمة أولياء الأمور</h2>
                <p>{{ $guardians->total() }} سجل · بيانات التواصل وحالة البوابة</p>
            </div>
        </div>
        <div class="overflow-x-auto">
            <table class="nursery-table min-w-[960px]">
                <thead>
                    <tr>
                        <th>ولي الأمر <x-info field="nursery.guardian_name" /></th>
                        <th>الجوال <x-info field="nursery.guardian_phone" /></th>
                        <th>البريد <x-info field="nursery.guardian_email" /></th>
                        <th class="text-center">الأطفال <x-info field="nursery.guardians_children_count" /></th>
                        <th class="text-center">البوابة <x-info field="nursery.guardians_portal_status" /></th>
                        <th>آخر دخول <x-info field="nursery.guardians_last_login" /></th>
                        <th class="text-center w-14">إجراءات</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($guardians as $guardian)
                        @php
                            $portalActive = trim((string) $guardian->portal_access_token) !== '';
                        @endphp
                        <tr>
                            <td>
                                <a href="{{ route('nursery.guardians.show', $guardian) }}" class="nursery-table-name no-underline text-inherit hover:opacity-90">
                                    <x-nursery-person-avatar :name="$guardian->name" />
                                    <span class="nursery-table-name__text">
                                        <span class="nursery-table-name__title">{{ $guardian->name }}</span>
                                    </span>
                                </a>
                            </td>
                            <td class="tabular-nums" dir="ltr">{{ $guardian->phone ?: '—' }}</td>
                            <td class="text-xs" dir="ltr">{{ $guardian->email ?: '—' }}</td>
                            <td class="text-center tabular-nums font-semibold">{{ $guardian->children_count }}</td>
                            <td class="text-center">
                                @if($portalActive)
                                    <span class="nursery-status-pill nursery-status-pill--success">مفعّل</span>
                                @else
                                    <span class="nursery-status-pill nursery-status-pill--muted">غير مفعّل</span>
                                @endif
                            </td>
                            <td class="text-xs text-slate-500">
                                {{ $guardian->portal_last_login_at?->format('Y-m-d H:i') ?? '—' }}
                            </td>
                            <td class="text-center">
                                <x-erp-actions-dropdown :menu-id="'nursery-guardian-'.$guardian->id">
                                    <x-erp-actions-menu-item :href="route('nursery.guardians.show', $guardian)" icon="children">
                                        الأطفال
                                    </x-erp-actions-menu-item>
                                    @if($canManage && $portalEnabled)
                                        <form method="post" action="{{ route('nursery.guardians.portal-invite', $guardian) }}" class="m-0">
                                            @csrf
                                            <x-erp-actions-menu-item type="submit" icon="invite">
                                                إعادة الدعوة
                                            </x-erp-actions-menu-item>
                                        </form>
                                        @if($portalActive)
                                            <div class="mx-2 my-2 border-t border-gray-100"></div>
                                            <form method="post"
                                                  action="{{ route('nursery.guardians.revoke-portal', $guardian) }}"
                                                  class="m-0">
                                                @csrf
                                                @method('DELETE')
                                                <x-erp-actions-menu-item type="submit" icon="revoke" :danger="true"
                                                    confirm="إلغاء وصول هذا ولي الأمر للبوابة؟">
                                                    إلغاء الوصول
                                                </x-erp-actions-menu-item>
                                            </form>
                                        @endif
                                    @endif
                                </x-erp-actions-dropdown>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="!py-12 text-center text-teal-800/70">
                                لا يوجد أولياء أمور مسجّلون.
                                @if($canManage)
                                    <a href="{{ route('nursery.children.create') }}" class="text-teal-600 font-semibold underline">سجّل طفلاً جديداً</a>
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
