@extends('layouts.nursery')

@section('title', niche_label('entities.classroom', 'الفصول'))

@section('content')
<div class="w-full space-y-6" dir="rtl">
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-extrabold text-orange-950">{{ niche_label('entities.classroom', 'الفصول') }}</h1>
            <p class="text-sm text-orange-800/80">
                إدارة {{ niche_label('entities.classroom', 'الفصول') }}
                <x-info field="nursery.nav_classrooms" />
            </p>
        </div>
        @if($canManage)
            <a href="{{ route('nursery.classrooms.create') }}" class="nursery-btn nursery-btn-primary">+ إضافة فصل</a>
        @endif
    </div>

    <div class="nursery-stats-row">
        <x-nursery-stat-card title="إجمالي الفصول" :value="$listStats['total']" info="nursery.list_total_classrooms" tone="primary" hint="كل الفصول" spark="bars"
            :percent="$spark['total']['percent']" :trend="$spark['total']['trend']" />
        <x-nursery-stat-card title="الفصول النشطة" :value="$listStats['active']" info="nursery.list_active_classrooms" tone="success" hint="جاهزة للعمل" spark="ring"
            :percent="$spark['active']['percent']" :trend="$spark['active']['trend']" />
        <x-nursery-stat-card title="الفصول المؤرشفة" :value="$listStats['archived']" info="nursery.list_archived_classrooms" tone="muted" hint="غير نشطة" spark="line"
            :percent="$spark['archived']['percent']" :trend="$spark['archived']['trend']" />
    </div>

    <section class="nursery-card nursery-table-card">
        <div class="nursery-table-card__toolbar">
            <div>
                <h2>قائمة الفصول</h2>
                <p>السعة والعمر والأطفال المسجّلون</p>
            </div>
        </div>
        <div class="overflow-x-auto">
            <table class="nursery-table min-w-[640px]">
                <thead>
                    <tr>
                        <th>
                            اسم الفصل
                            <x-info field="nursery.classroom_name" />
                        </th>
                        <th>
                            السعة
                            <x-info field="nursery.classroom_capacity" />
                        </th>
                        <th>
                            الفئة العمرية
                            <x-info field="nursery.classroom_age_groups" />
                        </th>
                        <th>
                            الأطفال في الفصل
                            <x-info field="nursery.classroom_enrolled_children" />
                        </th>
                        <th class="text-center">
                            الحالة
                            <x-info field="nursery.classroom_status" />
                        </th>
                        <th class="text-center w-14">إجراءات</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($items as $room)
                        <tr>
                            <td>
                                <span class="nursery-table-name__title">{{ $room->name }}</span>
                            </td>
                            <td class="tabular-nums font-semibold text-slate-700">{{ $room->capacity ?? '—' }} طفل</td>
                            <td class="max-w-md">
                                {{ \App\Support\NurseryClassroomAgeGroups::labelsFor($room->age_groups) }}
                            </td>
                            <td>
                                <span class="inline-flex items-center gap-1.5 flex-wrap">
                                    <span class="font-semibold tabular-nums text-slate-800">{{ $room->active_children_count }}</span>
                                    @if($room->capacity)
                                        <span class="text-slate-500 tabular-nums">/ {{ $room->capacity }}</span>
                                    @endif
                                    <span class="text-xs text-slate-400">مسجّلون حالياً</span>
                                </span>
                            </td>
                            <td class="text-center">
                                @if($room->is_active)
                                    <span class="nursery-status-pill nursery-status-pill--success">نشط</span>
                                @else
                                    <span class="nursery-status-pill nursery-status-pill--muted">مؤرشف</span>
                                @endif
                            </td>
                            <td class="text-center">
                                @if($room->is_active || $canManage)
                                    <x-erp-actions-dropdown :menu-id="'nursery-classroom-'.$room->id">
                                        @if($room->is_active)
                                            <x-erp-actions-menu-item :href="route('nursery.classrooms.today', $room)" icon="today">
                                                يوم الفصل
                                            </x-erp-actions-menu-item>
                                        @endif
                                        @if($canManage)
                                            <x-erp-actions-menu-item :href="route('nursery.classrooms.edit', $room)" icon="edit">
                                                تعديل
                                            </x-erp-actions-menu-item>
                                        @endif
                                    </x-erp-actions-dropdown>
                                @else
                                    <span class="text-slate-400">—</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                                <td colspan="6" class="!py-12 text-center text-orange-800/70">
                                <p class="font-medium">لا يوجد أي فصول لعرضها!</p>
                                <p class="text-sm mt-2">أضف أول فصل لحضانتك داخل هذا القسم.</p>
                                @if($canManage)
                                    <a href="{{ route('nursery.classrooms.create') }}" class="nursery-btn nursery-btn-primary mt-4 inline-flex">+ إضافة فصل</a>
                                @endif
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
</div>
@endsection
