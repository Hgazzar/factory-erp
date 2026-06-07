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

    <div class="grid gap-3 sm:grid-cols-3">
        <div class="nursery-card p-4 text-center">
            <p class="text-sm font-semibold text-orange-950">
                إجمالي الفصول
                <x-info field="nursery.list_total_classrooms" />
            </p>
            <p class="text-2xl font-extrabold text-orange-600 tabular-nums">{{ $listStats['total'] }}</p>
        </div>
        <div class="nursery-card p-4 text-center">
            <p class="text-sm font-semibold text-orange-950">
                الفصول النشطة
                <x-info field="nursery.list_active_classrooms" />
            </p>
            <p class="text-2xl font-extrabold text-emerald-600 tabular-nums">{{ $listStats['active'] }}</p>
        </div>
        <div class="nursery-card p-4 text-center">
            <p class="text-sm font-semibold text-orange-950">
                الفصول المؤرشفة
                <x-info field="nursery.list_archived_classrooms" />
            </p>
            <p class="text-2xl font-extrabold text-gray-500 tabular-nums">{{ $listStats['archived'] }}</p>
        </div>
    </div>

    <section class="nursery-card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-right min-w-[640px]">
                <thead>
                    <tr class="border-b border-orange-100 bg-orange-50/80">
                        <th class="px-4 py-3 font-bold text-orange-950">
                            اسم الفصل
                            <x-info field="nursery.classroom_name" />
                        </th>
                        <th class="px-4 py-3 font-bold text-orange-950">
                            السعة
                            <x-info field="nursery.classroom_capacity" />
                        </th>
                        <th class="px-4 py-3 font-bold text-orange-950">
                            الفئة العمرية
                            <x-info field="nursery.classroom_age_groups" />
                        </th>
                        <th class="px-4 py-3 font-bold text-orange-950">
                            الأطفال في الفصل
                            <x-info field="nursery.classroom_enrolled_children" />
                        </th>
                        <th class="px-4 py-3 font-bold text-orange-950">
                            الحالة
                            <x-info field="nursery.classroom_status" />
                        </th>
                        @if($canManage)
                            <th class="px-4 py-3 font-bold text-orange-950 w-28">إجراءات</th>
                        @endif
                    </tr>
                </thead>
                <tbody>
                    @forelse($items as $room)
                        <tr class="border-b border-orange-50 hover:bg-orange-50/40">
                            <td class="px-4 py-3 font-semibold text-orange-950">{{ $room->name }}</td>
                            <td class="px-4 py-3 tabular-nums">{{ $room->capacity ?? '—' }} طفل</td>
                            <td class="px-4 py-3 text-orange-900/90 max-w-md">
                                {{ \App\Support\NurseryClassroomAgeGroups::labelsFor($room->age_groups) }}
                            </td>
                            <td class="px-4 py-3 text-orange-950">
                                <span class="inline-flex items-center gap-1.5 flex-wrap">
                                    <span class="font-semibold tabular-nums">{{ $room->active_children_count }}</span>
                                    @if($room->capacity)
                                        <span class="text-orange-800/70 tabular-nums">/ {{ $room->capacity }}</span>
                                    @endif
                                    <span class="text-xs text-orange-700/75">مسجّلون حالياً</span>
                                </span>
                            </td>
                            <td class="px-4 py-3">
                                @if($room->is_active)
                                    <span class="text-emerald-700 font-medium">نشط</span>
                                @else
                                    <span class="text-gray-500 font-medium">مؤرشف</span>
                                @endif
                            </td>
                            @if($canManage)
                                <td class="px-4 py-3">
                                    <a href="{{ route('nursery.classrooms.edit', $room) }}" class="nursery-btn nursery-btn-soft text-xs py-1.5">تعديل</a>
                                </td>
                            @endif
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ $canManage ? 6 : 5 }}" class="px-4 py-12 text-center">
                                <p class="text-orange-800/80 font-medium">لا يوجد أي فصول لعرضها!</p>
                                <p class="text-sm text-orange-700/70 mt-2">أضف أول فصل لحضانتك داخل هذا القسم.</p>
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
