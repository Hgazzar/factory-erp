@extends('layouts.nursery')

@section('title', 'الوحدات')

@section('content')
<div class="w-full space-y-6" dir="rtl">
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-extrabold text-orange-950">الوحدات</h1>
            <p class="text-sm text-orange-800/80">
                وحدات المنهج والأهداف التعليمية
                <x-info field="nursery.nav_units" />
            </p>
        </div>
        @if($canManage)
            <a href="{{ route('nursery.units.create') }}" class="nursery-btn nursery-btn-primary">+ إضافة وحدة</a>
        @endif
    </div>

    <div class="nursery-stats-row">
        <x-nursery-stat-card title="إجمالي الوحدات" :value="$listStats['total']" info="nursery.list_total_units" tone="primary" hint="كل الوحدات" spark="bars"
            :percent="$spark['total']['percent']" :trend="$spark['total']['trend']" />
        <x-nursery-stat-card title="الوحدات النشطة" :value="$listStats['active']" info="nursery.list_active_units" tone="success" hint="منهج نشط" spark="ring"
            :percent="$spark['active']['percent']" :trend="$spark['active']['trend']" />
        <x-nursery-stat-card title="الوحدات المؤرشفة" :value="$listStats['archived']" info="nursery.list_archived_units" tone="muted" hint="غير نشطة" spark="line"
            :percent="$spark['archived']['percent']" :trend="$spark['archived']['trend']" />
    </div>

    <form method="get" class="nursery-card p-4 flex flex-wrap gap-3 items-end">
        <input type="hidden" name="tab" value="{{ $tab }}">
        <div class="flex-1 min-w-[12rem]">
            <label class="block text-sm font-semibold text-orange-950 mb-1">
                بحث
                <x-info field="nursery.unit_search" />
            </label>
            <input type="search" name="q" value="{{ $q }}" placeholder="اسم الوحدة"
                   class="w-full rounded-lg border border-orange-200 px-3 py-2 text-sm">
        </div>
        <div class="w-full sm:w-48">
            <label class="block text-sm font-semibold text-orange-950 mb-1">
                الترتيب
                <x-info field="nursery.unit_sort" />
            </label>
            <x-custom-select name="sort"
                :options="[['value' => 'newest', 'label' => 'الأحدث أولاً'], ['value' => 'oldest', 'label' => 'الأقدم أولاً']]"
                :value="$sort"
                :searchable="false" />
        </div>
        <button type="submit" class="nursery-btn nursery-btn-soft">تطبيق</button>
    </form>

    <div class="flex flex-wrap gap-2">
        <a href="{{ route('nursery.units.index', array_merge(request()->except('tab', 'page'), ['tab' => 'active'])) }}"
           class="nursery-btn text-sm py-2 {{ $tab === 'active' ? 'nursery-btn-primary' : 'nursery-btn-soft' }}">
            نشطة ({{ $listStats['active'] }})
        </a>
        <a href="{{ route('nursery.units.index', array_merge(request()->except('tab', 'page'), ['tab' => 'archived'])) }}"
           class="nursery-btn text-sm py-2 {{ $tab === 'archived' ? 'nursery-btn-primary' : 'nursery-btn-soft' }}">
            مؤرشفة ({{ $listStats['archived'] }})
        </a>
    </div>

    <section class="nursery-card nursery-table-card">
        <div class="nursery-table-card__toolbar">
            <div>
                <h2>قائمة الوحدات</h2>
                <p>{{ $tab === 'archived' ? 'الوحدات المؤرشفة' : 'الوحدات النشطة' }} · الأهداف والفئة العمرية</p>
            </div>
        </div>
        <div class="overflow-x-auto">
            <table class="nursery-table min-w-[640px]">
                <thead>
                    <tr>
                        <th>
                            اسم الوحدة
                            <x-info field="nursery.unit_name" />
                        </th>
                        <th>
                            الفئة العمرية
                            <x-info field="nursery.unit_age_groups" />
                        </th>
                        <th>
                            الأهداف
                            <x-info field="nursery.unit_goals" />
                        </th>
                        <th class="text-center">
                            الحالة
                            <x-info field="nursery.unit_status" />
                        </th>
                        @if($canManage)
                            <th class="text-center w-14">إجراءات</th>
                        @endif
                    </tr>
                </thead>
                <tbody>
                    @forelse($items as $item)
                        <tr>
                            <td>
                                <span class="nursery-table-name__title">{{ $item->name }}</span>
                            </td>
                            <td class="max-w-xs">
                                {{ \App\Support\NurseryClassroomAgeGroups::labelsFor($item->age_groups) }}
                            </td>
                            <td>
                                <span class="tabular-nums font-semibold text-slate-800">{{ count($item->goalLines()) }}</span>
                                <span class="text-xs text-slate-400">هدف</span>
                            </td>
                            <td class="text-center">
                                @if($item->is_active)
                                    <span class="nursery-status-pill nursery-status-pill--success">نشطة</span>
                                @else
                                    <span class="nursery-status-pill nursery-status-pill--muted">مؤرشفة</span>
                                @endif
                            </td>
                            @if($canManage)
                                <td class="text-center">
                                    <x-erp-actions-dropdown :menu-id="'nursery-unit-'.$item->id">
                                        <x-erp-actions-menu-item :href="route('nursery.units.edit', $item)" icon="edit">
                                            تعديل
                                        </x-erp-actions-menu-item>
                                    </x-erp-actions-dropdown>
                                </td>
                            @endif
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ $canManage ? 5 : 4 }}" class="!py-12 text-center text-orange-800/70">
                                <p class="font-medium">لا توجد وحدات لعرضها!</p>
                                <p class="text-sm mt-2">أضف أول وحدة منهجية لحضانتك.</p>
                                @if($canManage)
                                    <a href="{{ route('nursery.units.create') }}" class="nursery-btn nursery-btn-primary mt-4 inline-flex">+ إضافة وحدة</a>
                                @endif
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($items->hasPages())
            <div class="px-4 py-3 border-t border-slate-100">{{ $items->links() }}</div>
        @endif
    </section>
</div>
@endsection
