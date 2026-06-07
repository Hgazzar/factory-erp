@extends('layouts.nursery')

@section('title', 'طاقم العمل')

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

    <div class="grid gap-3 sm:grid-cols-3">
        <div class="nursery-card p-4 text-center">
            <p class="text-sm font-bold text-orange-950">إجمالي الطاقم <x-info field="nursery.list_total_staff" /></p>
            <p class="text-2xl font-extrabold text-orange-600 tabular-nums">{{ $listStats['total'] }}</p>
        </div>
        <div class="nursery-card p-4 text-center">
            <p class="text-sm font-bold text-orange-950">الحسابات النشطة <x-info field="nursery.list_active_staff" /></p>
            <p class="text-2xl font-extrabold text-emerald-600 tabular-nums">{{ $listStats['active'] }}</p>
        </div>
        <div class="nursery-card p-4 text-center">
            <p class="text-sm font-bold text-orange-950">المؤرشفون <x-info field="nursery.list_archived_staff" /></p>
            <p class="text-2xl font-extrabold text-gray-500 tabular-nums">{{ $listStats['archived'] }}</p>
        </div>
    </div>

    <form method="get" class="nursery-card p-4 grid gap-3 sm:grid-cols-2 lg:grid-cols-4 items-end">
        <div class="lg:col-span-2">
            <label class="block text-sm font-semibold text-orange-950 mb-1">بحث <x-info field="nursery.staff_search" /></label>
            <input type="search" name="q" value="{{ $q }}" placeholder="الاسم، البريد، الجوال، الكود"
                   class="w-full rounded-lg border border-orange-200 px-3 py-2 text-sm">
        </div>
        <div>
            <label class="block text-sm font-semibold text-orange-950 mb-1">الدور الوظيفي <x-info field="nursery.staff_job_role" /></label>
            <x-custom-select name="job_role" :options="$jobRoleOptions" :value="$jobRole" :searchable="true" />
        </div>
        <div>
            <label class="block text-sm font-semibold text-orange-950 mb-1">الحالة <x-info field="nursery.staff_status" /></label>
            <x-custom-select name="status"
                :options="[['value' => '', 'label' => 'الجميع'], ['value' => 'active', 'label' => 'نشط'], ['value' => 'inactive', 'label' => 'مؤرشف']]"
                :value="$status" :searchable="false" />
        </div>
        <button type="submit" class="nursery-btn nursery-btn-soft sm:col-span-2 lg:col-span-4">تطبيق</button>
    </form>

    <section class="nursery-card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm min-w-[720px]">
                <thead>
                    <tr class="bg-orange-50/80 border-b border-orange-100">
                        <th class="px-4 py-3 text-right font-bold text-orange-950">الموظف</th>
                        <th class="px-4 py-3 text-right font-bold text-orange-950">الدور</th>
                        <th class="px-4 py-3 text-right font-bold text-orange-950">التواصل</th>
                        <th class="px-4 py-3 text-right font-bold text-orange-950">الحالة</th>
                        @if($canManage)<th class="px-4 py-3 w-24"></th>@endif
                    </tr>
                </thead>
                <tbody>
                    @forelse($items as $emp)
                        <tr class="border-b border-orange-50 hover:bg-orange-50/50">
                            <td class="px-4 py-3">
                                <p class="font-semibold text-orange-950">{{ $emp->name }}</p>
                                <p class="text-xs text-orange-700/70">{{ $emp->code }}</p>
                            </td>
                            <td class="px-4 py-3">{{ config('nursery_job_roles.roles')[$emp->nursery_job_role] ?? '—' }}</td>
                            <td class="px-4 py-3 text-xs" dir="ltr">{{ $emp->mobile }}<br>{{ $emp->email }}</td>
                            <td class="px-4 py-3">{{ $emp->status === 'active' ? 'نشط' : 'مؤرشف' }}</td>
                            @if($canManage)
                                <td class="px-4 py-3"><a href="{{ route('nursery.staff.edit', $emp) }}" class="nursery-btn nursery-btn-soft text-xs py-1">تعديل</a></td>
                            @endif
                        </tr>
                    @empty
                        <tr><td colspan="{{ $canManage ? 5 : 4 }}" class="px-4 py-10 text-center text-orange-800/70">لا يوجد موظفون. @if($canManage)<a href="{{ route('nursery.staff.create') }}" class="text-orange-600 font-semibold underline">إضافة موظف</a>@endif</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
    <div>{{ $items->links() }}</div>
</div>
@endsection
