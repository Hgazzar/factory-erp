@extends('layouts.nursery')

@section('title', 'طاقم العمل')
@section('topbar_subtitle', 'إدارة الموظفين وصلاحياتهم')

@section('content')
<div class="w-full space-y-6" dir="rtl">
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-extrabold text-orange-950">طاقم العمل</h1>
            <p class="text-sm text-orange-800/80"><x-info field="nursery.nav_staff" /> إدارة الموظفين وصلاحياتهم</p>
        </div>
        @if($canManage)
            <a href="{{ route('nursery.staff.create') }}" class="nursery-btn nursery-btn-primary">+ إضافة موظف</a>
        @endif
    </div>

    <div class="nursery-stats-row">
        <x-nursery-stat-card title="إجمالي الطاقم" :value="$listStats['total']" info="nursery.list_total_staff" tone="primary" hint="كل الموظفين" spark="bars"
            :percent="$spark['total']['percent']" :trend="$spark['total']['trend']" />
        <x-nursery-stat-card title="الحسابات النشطة" :value="$listStats['active']" info="nursery.list_active_staff" tone="success" hint="يعملون حالياً" spark="ring"
            :percent="$spark['active']['percent']" :trend="$spark['active']['trend']" />
        <x-nursery-stat-card title="المؤرشفون" :value="$listStats['archived']" info="nursery.list_archived_staff" tone="muted" hint="غير نشطين" spark="line"
            :percent="$spark['archived']['percent']" :trend="$spark['archived']['trend']" />
    </div>

    <form method="get" class="nursery-card p-4 grid gap-3 sm:grid-cols-2 lg:grid-cols-4 items-end">
        <div class="lg:col-span-2">
            <label class="block text-sm font-semibold text-orange-950 mb-1">بحث <x-info field="nursery.staff_search" /></label>
            <input type="search" name="q" value="{{ $q }}" placeholder="الاسم، البريد، الجوال، الكود"
                   class="w-full rounded-xl border border-orange-200 px-3 py-2 text-sm">
        </div>
        <div>
            <label class="block text-sm font-semibold text-orange-950 mb-1">الدور الوظيفي <x-info field="nursery.staff_job_role" /></label>
            <x-custom-select name="job_role" :options="$jobRoleOptions" :value="$jobRole" :searchable="true" :fixed-panel="true" empty-label="— كل الأدوار —" />
        </div>
        <div>
            <label class="block text-sm font-semibold text-orange-950 mb-1">الحالة <x-info field="nursery.staff_status" /></label>
            <x-custom-select name="status"
                :options="[['value' => 'active', 'label' => 'نشط'], ['value' => 'inactive', 'label' => 'مؤرشف']]"
                :value="$status" :searchable="false" :fixed-panel="true" empty-label="الجميع" />
        </div>
        <button type="submit" class="nursery-btn nursery-btn-soft sm:col-span-2 lg:col-span-4">تطبيق</button>
    </form>

    <section class="nursery-card nursery-table-card">
        <div class="nursery-table-card__toolbar">
            <div>
                <h2>قائمة الموظفين</h2>
                <p>{{ $items->total() }} سجل · صورة واسم ودور وظيفي</p>
            </div>
        </div>
        <div class="overflow-x-auto">
            <table class="nursery-table min-w-[820px]">
                <thead>
                    <tr>
                        <th>الموظف</th>
                        <th>الكود</th>
                        <th>الدور</th>
                        <th>التواصل</th>
                        <th class="text-center">الحالة</th>
                        @if($canManage)
                            <th class="text-center w-14">إجراءات</th>
                        @endif
                    </tr>
                </thead>
                <tbody>
                    @forelse($items as $emp)
                        @php
                            $roleLabel = config('nursery_job_roles.roles')[$emp->nursery_job_role] ?? '—';
                            $isActive = $emp->status === 'active';
                        @endphp
                        <tr>
                            <td>
                                <div class="nursery-table-name">
                                    <x-nursery-person-avatar :name="$emp->name" :src="$emp->firstImageUrl()" />
                                    <span class="nursery-table-name__text">
                                        <span class="nursery-table-name__title">{{ $emp->name }}</span>
                                        <span class="nursery-table-name__sub">{{ $roleLabel }}</span>
                                    </span>
                                </div>
                            </td>
                            <td class="tabular-nums font-semibold text-slate-700">{{ $emp->code }}</td>
                            <td>{{ $roleLabel }}</td>
                            <td>
                                @if($emp->mobile || $emp->email)
                                    @if($emp->mobile)
                                        <span class="block text-sm text-slate-800" dir="ltr">{{ $emp->mobile }}</span>
                                    @endif
                                    @if($emp->email)
                                        <span class="block text-xs text-slate-500 mt-0.5" dir="ltr">{{ $emp->email }}</span>
                                    @endif
                                @else
                                    —
                                @endif
                            </td>
                            <td class="text-center">
                                @if($isActive)
                                    <span class="nursery-status-pill nursery-status-pill--success">نشط</span>
                                @else
                                    <span class="nursery-status-pill nursery-status-pill--muted">مؤرشف</span>
                                @endif
                            </td>
                            @if($canManage)
                                <td class="text-center">
                                    <x-erp-actions-dropdown :menu-id="'nursery-staff-'.$emp->id">
                                        <x-erp-actions-menu-item :href="route('nursery.staff.edit', $emp)" icon="edit">
                                            تعديل
                                        </x-erp-actions-menu-item>
                                    </x-erp-actions-dropdown>
                                </td>
                            @endif
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ $canManage ? 6 : 5 }}" class="!py-12 text-center text-orange-800/70">
                                لا يوجد موظفون.
                                @if($canManage)
                                    <a href="{{ route('nursery.staff.create') }}" class="text-orange-600 font-semibold underline">إضافة موظف</a>
                                @endif
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
    <div>{{ $items->links() }}</div>
</div>
@endsection
