@extends('layouts.nursery')

@section('title', niche_label('entities.child', 'الأطفال'))
@section('topbar_subtitle', 'سجل الأطفال المسجّلين في الحضانة')

@section('content')
@php
    $canManageChildren = app(\App\Support\NurseryAccess::class)->allows(\App\Support\NurseryAccess::CAP_MANAGE_CHILDREN);
@endphp
<div class="space-y-6" dir="rtl">
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-extrabold text-orange-950">قائمة {{ niche_label('entities.child', 'الأطفال') }}</h1>
            <p class="text-sm text-orange-800/80">
                سجل الأطفال المسجّلين في الحضانة
                <x-info field="nursery.nav_children" />
            </p>
        </div>
        @if($canManageChildren)
            <a href="{{ route('nursery.children.create') }}" class="nursery-btn nursery-btn-primary">+ إضافة طفل</a>
        @endif
    </div>

    <div class="nursery-stats-row">
        <x-nursery-stat-card title="إجمالي الأطفال" :value="$listStats['total']" info="nursery.list_total_children" tone="primary" hint="كل السجلات" spark="bars" trend="up" />
        <x-nursery-stat-card title="الحسابات النشطة" :value="$listStats['active']" info="nursery.list_active_children" tone="success" hint="مسجّلون حالياً" spark="ring" trend="up" />
        <x-nursery-stat-card title="الحسابات المؤرشفة" :value="$listStats['archived']" info="nursery.list_archived_children" tone="muted" hint="غير نشطة" spark="line" trend="flat" />
    </div>

    <form method="get" class="nursery-card p-4 flex flex-wrap gap-3 items-end">
        <div class="flex-1 min-w-[180px]">
            <label class="block text-sm font-semibold text-orange-950 mb-1">
                بحث
                <x-info field="nursery.filter_attendance_person" />
            </label>
            <input type="search" name="q" value="{{ $q }}"
                   class="w-full rounded-xl border border-orange-200 px-3 py-2 text-sm"
                   placeholder="اسم الطفل، الكود، أو ولي الأمر">
        </div>
        <div class="min-w-[160px]">
            <label class="block text-sm font-semibold text-orange-950 mb-1">
                الفصل
                <x-info field="nursery.filter_classroom" />
            </label>
            <x-custom-select name="classroom_id"
                :options="array_merge(
                    [['value' => '', 'label' => 'جميع الفصول']],
                    $classrooms->map(fn ($c) => ['value' => (string) $c->id, 'label' => $c->name])->all()
                )"
                :value="(string) $classroomId"
                placeholder="الفصل"
                empty-label="جميع الفصول"
                :searchable="true" />
        </div>
        <button type="submit" class="nursery-btn nursery-btn-soft">تطبيق</button>
    </form>

    <section class="nursery-card nursery-table-card">
        <div class="nursery-table-card__toolbar">
            <div>
                <h2>قائمة الأطفال</h2>
                <p>{{ $children->total() }} سجل · صورة واسم وبيانات أساسية</p>
            </div>
        </div>
        <div class="overflow-x-auto">
            <table class="nursery-table min-w-[760px]">
                <thead>
                    <tr>
                        <th>الطفل</th>
                        <th>الكود</th>
                        <th>الفصل</th>
                        <th>ولي الأمر</th>
                        <th class="text-center">الحالة</th>
                        <th class="text-center w-14">إجراءات</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($children as $child)
                        @php
                            $isActive = ($child->status ?? '') === \App\Models\Nursery\Child::STATUS_ACTIVE;
                            $classroomName = $child->activeEnrollment?->classroom?->name ?? 'غير معين لفصل';
                        @endphp
                        <tr>
                            <td>
                                <a href="{{ route('nursery.children.show', $child) }}" class="nursery-table-name no-underline text-inherit hover:opacity-90">
                                    <x-nursery-person-avatar :name="$child->name" :src="$child->firstImageUrl()" />
                                    <span class="nursery-table-name__text">
                                        <span class="nursery-table-name__title">{{ $child->name }}</span>
                                        @if($child->date_of_birth)
                                            <span class="nursery-table-name__sub">تاريخ الميلاد: {{ $child->date_of_birth->format('Y-m-d') }}</span>
                                        @endif
                                    </span>
                                </a>
                            </td>
                            <td class="tabular-nums font-semibold text-slate-700">{{ $child->code }}</td>
                            <td>{{ $classroomName }}</td>
                            <td>
                                <span class="block font-medium text-slate-800">{{ $child->guardian?->name ?: '—' }}</span>
                                @if($child->guardian?->phone)
                                    <span class="block text-xs text-slate-500 mt-0.5" dir="ltr">{{ $child->guardian->phone }}</span>
                                @endif
                            </td>
                            <td class="text-center">
                                @if($isActive)
                                    <span class="nursery-status-pill nursery-status-pill--success">نشط</span>
                                @else
                                    <span class="nursery-status-pill nursery-status-pill--muted">مؤرشف</span>
                                @endif
                            </td>
                            <td class="text-center">
                                <x-erp-actions-dropdown :menu-id="'nursery-child-'.$child->id">
                                    <x-erp-actions-menu-item :href="route('nursery.children.show', $child)" icon="view">
                                        عرض الملف
                                    </x-erp-actions-menu-item>
                                    @if($canManageChildren)
                                        <x-erp-actions-menu-item :href="route('nursery.children.edit', $child)" icon="edit">
                                            تعديل
                                        </x-erp-actions-menu-item>
                                    @endif
                                </x-erp-actions-dropdown>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="!py-12 text-center text-orange-800/70">
                                <p class="font-medium">لا يوجد أي بيانات لعرضها</p>
                                <p class="text-sm mt-2">أضف أول طفل داخل حضانتك من هذا القسم.</p>
                                @if($canManageChildren)
                                    <a href="{{ route('nursery.children.create') }}" class="nursery-btn nursery-btn-primary mt-4 inline-flex">+ إضافة طفل</a>
                                @endif
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    <div>{{ $children->links() }}</div>
</div>
@endsection
