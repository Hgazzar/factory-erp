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

    <div class="grid gap-3 sm:grid-cols-3">
        <div class="nursery-card p-4 text-center">
            <p class="text-sm font-semibold text-orange-950">
                إجمالي الوحدات
                <x-info field="nursery.list_total_units" />
            </p>
            <p class="text-2xl font-extrabold text-orange-600 tabular-nums">{{ $listStats['total'] }}</p>
        </div>
        <div class="nursery-card p-4 text-center">
            <p class="text-sm font-semibold text-orange-950">
                الوحدات النشطة
                <x-info field="nursery.list_active_units" />
            </p>
            <p class="text-2xl font-extrabold text-emerald-600 tabular-nums">{{ $listStats['active'] }}</p>
        </div>
        <div class="nursery-card p-4 text-center">
            <p class="text-sm font-semibold text-orange-950">
                الوحدات المؤرشفة
                <x-info field="nursery.list_archived_units" />
            </p>
            <p class="text-2xl font-extrabold text-gray-500 tabular-nums">{{ $listStats['archived'] }}</p>
        </div>
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

    <section class="nursery-card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-right min-w-[640px]">
                <thead>
                    <tr class="border-b border-orange-100 bg-orange-50/80">
                        <th class="px-4 py-3 font-bold text-orange-950">
                            اسم الوحدة
                            <x-info field="nursery.unit_name" />
                        </th>
                        <th class="px-4 py-3 font-bold text-orange-950">
                            الفئة العمرية
                            <x-info field="nursery.unit_age_groups" />
                        </th>
                        <th class="px-4 py-3 font-bold text-orange-950">
                            الأهداف
                            <x-info field="nursery.unit_goals" />
                        </th>
                        <th class="px-4 py-3 font-bold text-orange-950">
                            الحالة
                            <x-info field="nursery.unit_status" />
                        </th>
                        @if($canManage)
                            <th class="px-4 py-3 font-bold text-orange-950 w-28">إجراءات</th>
                        @endif
                    </tr>
                </thead>
                <tbody>
                    @forelse($items as $item)
                        <tr class="border-b border-orange-50 hover:bg-orange-50/40">
                            <td class="px-4 py-3 font-semibold text-orange-950">{{ $item->name }}</td>
                            <td class="px-4 py-3 text-orange-900/90 max-w-xs">
                                {{ \App\Support\NurseryClassroomAgeGroups::labelsFor($item->age_groups) }}
                            </td>
                            <td class="px-4 py-3 text-orange-900/90">
                                <span class="tabular-nums font-semibold">{{ count($item->goalLines()) }}</span>
                                <span class="text-xs text-orange-700/75">هدف</span>
                            </td>
                            <td class="px-4 py-3">
                                @if($item->is_active)
                                    <span class="text-emerald-700 font-medium">نشطة</span>
                                @else
                                    <span class="text-gray-500 font-medium">مؤرشفة</span>
                                @endif
                            </td>
                            @if($canManage)
                                <td class="px-4 py-3">
                                    <a href="{{ route('nursery.units.edit', $item) }}" class="nursery-btn nursery-btn-soft text-xs py-1.5">تعديل</a>
                                </td>
                            @endif
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ $canManage ? 5 : 4 }}" class="px-4 py-12 text-center">
                                <p class="text-orange-800/80 font-medium">لا توجد وحدات لعرضها!</p>
                                <p class="text-sm text-orange-700/70 mt-2">أضف أول وحدة منهجية لحضانتك.</p>
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
            <div class="px-4 py-3 border-t border-orange-100">{{ $items->links() }}</div>
        @endif
    </section>
</div>
@endsection
