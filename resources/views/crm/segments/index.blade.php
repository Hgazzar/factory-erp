@extends('layouts.crm')

@section('title', 'شرائح العملاء — CRM')

@section('breadcrumb')
    <a href="{{ route('dashboard') }}" class="text-gray-500 hover:text-indigo-600">الرئيسية</a>
    <span>›</span>
    <a href="{{ route('crm.dashboard') }}" class="text-gray-500 hover:text-indigo-600">إدارة العملاء</a>
    <span>›</span>
    <span class="text-indigo-900 font-semibold truncate">شرائح العملاء</span>
@endsection

@section('content')
<div class="space-y-6" dir="rtl">
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div class="min-w-0">
            <h1 class="text-2xl font-bold text-gray-900 flex flex-wrap items-center gap-2">
                شرائح العملاء
                <span class="inline-flex items-center shrink-0"><x-info field="crm.segments_intro" /></span>
            </h1>
            <p class="text-sm text-gray-500 mt-1">تصنيف ديناميكي للعملاء حسب معايير CRM وربطه بقاعدة البيانات.</p>
        </div>
        <a href="{{ route('crm.segments.create') }}" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-lg bg-blue-600 text-white text-sm font-semibold hover:bg-blue-700 transition shadow-sm no-underline">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16"><path d="M8 4a.5.5 0 0 1 .5.5V7.5H11.5a.5.5 0 0 1 0 1H8.5V11.5a.5.5 0 0 1-1 0V8.5H4.5a.5.5 0 0 1 0-1H7.5V4.5A.5.5 0 0 1 8 4z"/></svg>
            شريحة جديدة
        </a>
    </div>

    <form method="GET" action="{{ route('crm.segments.index') }}" class="bg-white rounded-lg border border-gray-200 shadow-sm p-4">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 items-end">
            <div class="md:col-span-2 min-w-0">
                <label for="segments-q" class="block text-sm font-medium text-gray-700 mb-1">بحث</label>
                <input id="segments-q" type="search" name="q" value="{{ request('q') }}" placeholder="الاسم أو الرمز..." class="block w-full min-h-[2.75rem] rounded-lg border border-gray-300 py-2.5 px-3 text-sm text-gray-900 placeholder:text-gray-400 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
            </div>
            <div class="min-w-0">
                <label for="segments-type" class="block text-sm font-medium text-gray-700 mb-1">النوع</label>
                <x-searchable-select
                    name="type"
                    id="segments-type"
                    :options="$segmentTypeOptions ?? []"
                    :value="request('type', '')"
                    empty-label="الكل"
                    placeholder="اختر النوع"
                    :searchable="false"
                />
            </div>
            <div class="min-w-0">
                <label for="segments-status" class="block text-sm font-medium text-gray-700 mb-1">الحالة</label>
                <x-searchable-select
                    name="status"
                    id="segments-status"
                    :options="$segmentStatusOptions ?? []"
                    :value="request('status', '')"
                    empty-label="الكل"
                    placeholder="اختر الحالة"
                    :searchable="false"
                />
            </div>
            <div class="md:col-span-4 flex items-center gap-2">
                <button type="submit" class="inline-flex items-center justify-center min-h-[2.75rem] px-4 rounded-lg bg-blue-600 text-white text-sm font-semibold hover:bg-blue-700 transition">تطبيق</button>
                <a href="{{ route('crm.segments.index') }}" class="inline-flex items-center justify-center min-h-[2.75rem] px-4 rounded-lg border border-gray-300 text-gray-800 text-sm font-medium hover:bg-gray-50 transition no-underline">مسح</a>
            </div>
        </div>
    </form>

    <div class="bg-white rounded-lg border border-gray-200 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="table-fixed w-full min-w-[64rem] border-collapse text-sm text-right">
                <colgroup>
                    <col class="w-[12%]">
                    <col class="w-[20%]">
                    <col class="w-[14%]">
                    <col class="w-[10%]">
                    <col class="w-[10%]">
                    <col class="w-[17%]">
                    <col class="w-[17%]">
                    <col class="w-[10%]">
                </colgroup>
                <thead class="bg-gray-50 text-gray-600 border-b border-gray-200">
                    <tr>
                        <th class="py-3 px-3 font-medium whitespace-nowrap"><span class="inline-flex items-center gap-1"><x-info field="crm.segments_code" /> الرمز</span></th>
                        <th class="py-3 px-3 font-medium whitespace-nowrap"><span class="inline-flex items-center gap-1"><x-info field="crm.segments_name" /> الاسم</span></th>
                        <th class="py-3 px-3 font-medium whitespace-nowrap"><span class="inline-flex items-center gap-1"><x-info field="crm.segments_type" /> النوع</span></th>
                        <th class="py-3 px-3 font-medium whitespace-nowrap"><span class="inline-flex items-center gap-1"><x-info field="crm.segments_members" /> الأعضاء</span></th>
                        <th class="py-3 px-3 font-medium whitespace-nowrap"><span class="inline-flex items-center gap-1"><x-info field="crm.segments_status" /> الحالة</span></th>
                        <th class="py-3 px-3 font-medium whitespace-nowrap"><span class="inline-flex items-center gap-1"><x-info field="crm.segments_updated_at" /> آخر تحديث</span></th>
                        <th class="py-3 px-3 font-medium whitespace-nowrap"><span class="inline-flex items-center gap-1"><x-info field="crm.segments_created_at" /> تاريخ الإنشاء</span></th>
                        <th class="py-3 px-3 font-medium whitespace-nowrap text-center"><span class="inline-flex items-center gap-1"><x-info field="crm.segments_actions" /> الإجراءات</span></th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-100">
                    @forelse($segments as $segment)
                        <tr class="hover:bg-gray-50/80 transition-colors">
                            <td class="py-3 px-3 tabular-nums text-gray-800">{{ $segment->code }}</td>
                            <td class="py-3 px-3 font-medium text-gray-900">
                                <span class="inline-flex items-center gap-2">
                                    <span class="inline-flex h-3 w-3 rounded-full border border-gray-200" style="background-color: {{ $segment->color ?? '#2563EB' }}"></span>
                                    <span>{{ $segment->name }}</span>
                                </span>
                            </td>
                            <td class="py-3 px-3 text-gray-700">{{ \App\Models\CrmSegment::typeLabels()[$segment->type] ?? $segment->type }}</td>
                            <td class="py-3 px-3 tabular-nums">
                                @if((int) $segment->customers_count === 0)
                                    <span class="inline-flex items-center rounded-full bg-gray-100 text-gray-500 border border-gray-200 px-2.5 py-1 text-xs font-medium">لا يوجد أعضاء</span>
                                @else
                                    <span class="text-gray-800">{{ number_format((int) $segment->customers_count) }}</span>
                                @endif
                            </td>
                            <td class="py-3 px-3">
                                @php $status = \App\Models\CrmSegment::statusLabels()[$segment->status] ?? $segment->status; @endphp
                                <span class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-medium border
                                    {{ $segment->status === 'active' ? 'bg-emerald-50 text-emerald-700 border-emerald-100' : ($segment->status === 'archived' ? 'bg-gray-100 text-gray-700 border-gray-200' : 'bg-amber-50 text-amber-700 border-amber-100') }}">
                                    {{ $status }}
                                </span>
                            </td>
                            <td class="py-3 px-3 text-gray-700 whitespace-nowrap">
                                @php
                                    $lastUpdated = $segment->last_refreshed_at ?? $segment->updated_at;
                                @endphp
                                @if($lastUpdated)
                                    <div class="text-xs text-gray-500">{{ $lastUpdated->format('H:i Y-m-d') }}</div>
                                    <div class="text-xs text-gray-700">{{ $lastUpdated->diffForHumans() }}</div>
                                @else
                                    —
                                @endif
                            </td>
                            <td class="py-3 px-3 text-gray-700 tabular-nums whitespace-nowrap">{{ optional($segment->created_at)->format('Y-m-d H:i') ?? '—' }}</td>
                            <td class="py-3 px-3 text-center">
                                <form method="POST" action="{{ route('crm.segments.refresh-members', $segment) }}" class="inline-flex js-refresh-segment-form" data-segment-name="{{ $segment->name }}">
                                    @csrf
                                    <button type="submit" class="inline-flex items-center justify-center w-9 h-9 rounded-lg border border-gray-300 bg-white text-gray-600 hover:bg-gray-50 hover:text-blue-700 transition" title="تحديث الأعضاء" aria-label="تحديث الأعضاء">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16"><path d="M8 3a5 5 0 1 0 4.546 2.914.5.5 0 1 1 .908-.418A6 6 0 1 1 8 2v1z"/><path d="M8 0a.5.5 0 0 1 .5.5V2h2a.5.5 0 0 1 0 1H8A.5.5 0 0 1 7.5 2.5v-2A.5.5 0 0 1 8 0z"/></svg>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="py-14 text-center text-gray-500">لا توجد شرائح حتى الآن.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($segments->hasPages())
            <div class="px-4 py-3 border-t border-gray-200">
                {{ $segments->links() }}
            </div>
        @endif
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.js-refresh-segment-form').forEach(function (form) {
        form.addEventListener('submit', function (e) {
            e.preventDefault();
            if (typeof Swal === 'undefined') {
                form.submit();
                return;
            }
            Swal.fire({
                title: 'تحديث أعضاء الشريحة؟',
                text: 'سيتم إعادة فحص قاعدة بيانات العملاء وربطهم بناءً على معايير الشريحة.',
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'نعم، حدّث الآن',
                cancelButtonText: 'إلغاء',
                reverseButtons: true,
            }).then(function (result) {
                if (result.isConfirmed) {
                    form.submit();
                }
            });
        });
    });
});
</script>
@endpush
