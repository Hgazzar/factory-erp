@extends('layouts.nursery')

@section('title', niche_label('entities.child', 'الأطفال'))

@section('content')
<div class="space-y-6" dir="rtl">
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-extrabold text-orange-950">قائمة {{ niche_label('entities.child', 'الأطفال') }}</h1>
            <p class="text-sm text-orange-800/80">
                سجل الأطفال المسجّلين في الحضانة
                <x-info field="nursery.nav_children" />
            </p>
        </div>
        @if(app(\App\Support\NurseryAccess::class)->allows(\App\Support\NurseryAccess::CAP_MANAGE_CHILDREN))
            <a href="{{ route('nursery.children.create') }}" class="nursery-btn nursery-btn-primary">+ إضافة طفل</a>
        @endif
    </div>

    <div class="grid gap-3 sm:grid-cols-3">
        <div class="nursery-card p-4 text-center">
            <p class="text-sm font-semibold text-orange-950">
                إجمالي الأطفال
                <x-info field="nursery.list_total_children" />
            </p>
            <p class="text-2xl font-extrabold text-orange-600 tabular-nums">{{ $listStats['total'] }}</p>
        </div>
        <div class="nursery-card p-4 text-center">
            <p class="text-sm font-semibold text-orange-950">
                الحسابات النشطة
                <x-info field="nursery.list_active_children" />
            </p>
            <p class="text-2xl font-extrabold text-emerald-600 tabular-nums">{{ $listStats['active'] }}</p>
        </div>
        <div class="nursery-card p-4 text-center">
            <p class="text-sm font-semibold text-orange-950">
                الحسابات المؤرشفة
                <x-info field="nursery.list_archived_children" />
            </p>
            <p class="text-2xl font-extrabold text-gray-500 tabular-nums">{{ $listStats['archived'] }}</p>
        </div>
    </div>

    <form method="get" class="nursery-card p-4 flex flex-wrap gap-3 items-end">
        <div class="flex-1 min-w-[180px]">
            <label class="block text-sm font-semibold text-orange-950 mb-1">
                بحث
                <x-info field="nursery.filter_attendance_person" />
            </label>
            <input type="search" name="q" value="{{ $q }}"
                   class="w-full rounded-lg border border-orange-200 px-3 py-2 text-sm"
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

    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
        @forelse($children as $child)
            <a href="{{ route('nursery.children.show', $child) }}" class="nursery-child-card block no-underline text-inherit">
                <div class="flex items-start justify-between gap-2">
                    <div>
                        <p class="font-bold text-orange-950 text-lg">{{ $child->name }}</p>
                        <p class="text-xs text-orange-800/70 mt-1">كود: {{ $child->code }}</p>
                    </div>
                    <span class="text-2xl" aria-hidden="true">👶</span>
                </div>
                <p class="text-sm text-orange-900/80 mt-2">
                    الفصل: {{ $child->activeEnrollment?->classroom?->name ?? 'غير معين لفصل' }}
                </p>
                <p class="text-xs text-orange-700/70 mt-1">ولي الأمر: {{ $child->guardian?->name }} · {{ $child->guardian?->phone }}</p>
            </a>
        @empty
            <div class="nursery-card p-8 text-center sm:col-span-2 lg:col-span-3">
                <p class="text-orange-800/80 font-medium">لا يوجد أي بيانات لعرضها</p>
                <p class="text-sm text-orange-700/70 mt-2">أضف أول طفل داخل حضانتك من هذا القسم.</p>
                @if(app(\App\Support\NurseryAccess::class)->allows(\App\Support\NurseryAccess::CAP_MANAGE_CHILDREN))
                    <a href="{{ route('nursery.children.create') }}" class="nursery-btn nursery-btn-primary mt-4">+ إضافة طفل</a>
                @endif
            </div>
        @endforelse
    </div>

    <div>{{ $children->links() }}</div>
</div>
@endsection
