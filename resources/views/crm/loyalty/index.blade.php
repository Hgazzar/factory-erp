@extends('layouts.crm')

@section('title', 'خطط العضويات — CRM')

@section('breadcrumb')
    <a href="{{ route('dashboard') }}" class="text-gray-500 hover:text-indigo-600">الرئيسية</a>
    <span>›</span>
    <a href="{{ route('crm.dashboard') }}" class="text-gray-500 hover:text-indigo-600">إدارة العملاء</a>
    <span>›</span>
    <span class="text-indigo-900 font-semibold truncate">خطط العضويات</span>
@endsection

@section('content')
<div class="space-y-6" dir="rtl">
    @if(session('success'))
        <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-900">{{ session('success') }}</div>
    @endif
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 inline-flex items-center gap-2">
                خطط العضويات
                <x-info field="crm.loyalty_intro" />
            </h1>
            <p class="text-sm text-gray-500 mt-1 tabular-nums">إجمالي الخطط: {{ number_format((int) ($totalAll ?? 0)) }}@if(($totalFiltered ?? null) !== null && (int) $totalFiltered !== (int) $totalAll) | بعد التصفية: {{ number_format((int) $totalFiltered) }}@endif</p>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <a href="{{ route('crm.loyalty.create') }}" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-lg bg-blue-600 text-white text-sm font-semibold hover:bg-blue-700 transition shadow-sm no-underline">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16"><path d="M8 4a.5.5 0 0 1 .5.5V7.5H11.5a.5.5 0 0 1 0 1H8.5V11.5a.5.5 0 0 1-1 0V8.5H4.5a.5.5 0 0 1 0-1H7.5V4.5A.5.5 0 0 1 8 4z"/></svg>
                + خطة جديدة
            </a>
            <a href="{{ route('crm.loyalty.accounts.index') }}" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-lg border border-gray-300 bg-white text-gray-700 text-sm font-medium hover:bg-gray-50 transition no-underline">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16"><path d="M5 9a2 2 0 1 1 0-4 2 2 0 0 1 0 4M1 8a4 4 0 1 1 8 0A4 4 0 0 1 1 8m8.5 1.5a2.5 2.5 0 1 0 0-5 2.5 2.5 0 0 0 0 5m-8 2A3.5 3.5 0 0 1 5 8h.5a3.5 3.5 0 0 1 3.45 2.92A5.5 5.5 0 0 0 1.5 11.5"/></svg>
                سجل المشتركين
            </a>
        </div>
    </div>

    <form method="GET" action="{{ route('crm.loyalty.index') }}" class="bg-white rounded-lg border border-gray-200 shadow-sm p-4">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 items-end">
            <div class="min-w-0">
                <label for="loyalty-status" class="block text-sm font-medium text-gray-700 mb-1">التصفية</label>
                <x-searchable-select
                    name="status"
                    id="loyalty-status"
                    :options="$statusOptions ?? []"
                    :value="request('status', '')"
                    empty-label="الكل"
                    placeholder="الكل"
                    :searchable="false"
                />
            </div>
            <div class="md:col-span-3 flex items-center gap-2">
                <button type="submit" class="inline-flex items-center justify-center min-h-[2.75rem] px-4 rounded-lg bg-blue-600 text-white text-sm font-semibold hover:bg-blue-700 transition">تطبيق</button>
                <a href="{{ route('crm.loyalty.index') }}" class="inline-flex items-center justify-center min-h-[2.75rem] px-4 rounded-lg border border-gray-300 text-gray-800 text-sm font-medium hover:bg-gray-50 transition no-underline">مسح</a>
            </div>
        </div>
    </form>

    <div class="bg-white rounded-lg border border-gray-200 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="table-fixed w-full min-w-[72rem] border-collapse text-sm text-right">
                <colgroup>
                    <col class="w-[10%]">
                    <col class="w-[16%]">
                    <col class="w-[14%]">
                    <col class="w-[14%]">
                    <col class="w-[14%]">
                    <col class="w-[10%]">
                    <col class="w-[10%]">
                    <col class="w-[12%]">
                </colgroup>
                <thead class="bg-gray-50 text-gray-600 border-b border-gray-200">
                    <tr>
                        <th class="py-3 px-3 font-medium whitespace-nowrap"><span class="inline-flex items-center gap-1"><x-info field="crm.loyalty_code" /> الرمز</span></th>
                        <th class="py-3 px-3 font-medium whitespace-nowrap"><span class="inline-flex items-center gap-1"><x-info field="crm.loyalty_name" /> الاسم</span></th>
                        <th class="py-3 px-3 font-medium whitespace-nowrap"><span class="inline-flex items-center gap-1"><x-info field="crm.loyalty_points_name" /> اسم النقاط</span></th>
                        <th class="py-3 px-3 font-medium whitespace-nowrap"><span class="inline-flex items-center gap-1"><x-info field="crm.loyalty_earning_rate" /> معدل الكسب</span></th>
                        <th class="py-3 px-3 font-medium whitespace-nowrap"><span class="inline-flex items-center gap-1"><x-info field="crm.loyalty_redemption_rate" /> معدل الاستبدال</span></th>
                        <th class="py-3 px-3 font-medium whitespace-nowrap"><span class="inline-flex items-center gap-1"><x-info field="crm.loyalty_tiers_count" /> المستويات</span></th>
                        <th class="py-3 px-3 font-medium whitespace-nowrap"><span class="inline-flex items-center gap-1"><x-info field="crm.loyalty_status" /> الحالة</span></th>
                        <th class="py-3 px-3 font-medium whitespace-nowrap"><span class="inline-flex items-center gap-1"><x-info field="crm.loyalty_created_at" /> تاريخ الإنشاء</span></th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-100">
                    @forelse($programs as $program)
                        <tr class="hover:bg-gray-50/80 transition-colors">
                            <td class="py-3 px-3 tabular-nums text-gray-800">{{ $program->code }}</td>
                            <td class="py-3 px-3 font-medium text-gray-900">{{ $program->name }}</td>
                            <td class="py-3 px-3 text-gray-700">{{ $program->points_name }}</td>
                            <td class="py-3 px-3 tabular-nums text-gray-700">{{ number_format((float) $program->earning_rate, 2) }}</td>
                            <td class="py-3 px-3 tabular-nums text-gray-700">{{ number_format((float) $program->redemption_rate, 4) }}</td>
                            <td class="py-3 px-3 tabular-nums text-gray-700">{{ number_format((int) $program->tiers_count) }}</td>
                            <td class="py-3 px-3">
                                <span class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-medium border {{ $program->status === 'active' ? 'bg-emerald-50 text-emerald-700 border-emerald-100' : 'bg-gray-100 text-gray-700 border-gray-200' }}">
                                    {{ \App\Models\CrmLoyaltyProgram::statusLabels()[$program->status] ?? $program->status }}
                                </span>
                            </td>
                            <td class="py-3 px-3 text-gray-700 tabular-nums whitespace-nowrap">{{ optional($program->created_at)->format('Y-m-d') ?? '—' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="py-16 text-center text-gray-500">
                                <div class="inline-flex flex-col items-center gap-2">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="mx-auto h-10 w-10 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M11.48 3.499a.562.562 0 011.04 0l2.125 5.111a.563.563 0 00.475.345l5.518.442c.499.04.701.663.321.988l-4.204 3.527a.562.562 0 00-.163.563l1.073 5.394a.562.562 0 01-.84.606l-4.734-2.787a.562.562 0 00-.591 0L7.793 21.53a.562.562 0 01-.84-.606l1.073-5.394a.562.562 0 00-.163-.563l-4.204-3.527a.562.562 0 01.321-.988l5.518-.442a.563.563 0 00.475-.345L11.48 3.5z"/></svg>
                                    <span>لا توجد خطط بعد؛ أنشئ خطة جديدة للبدء.</span>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($programs->hasPages())
            <div class="px-4 py-3 border-t border-gray-200">
                {{ $programs->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
