@extends('layouts.crm')

@section('title', 'خط أنابيب الفرص — CRM — '.config('app.name'))

@section('breadcrumb')
    <a href="{{ route('dashboard') }}" class="text-gray-500 hover:text-indigo-600">الرئيسية</a>
    <span>›</span>
    <a href="{{ route('crm.dashboard') }}" class="text-gray-500 hover:text-indigo-600">إدارة العملاء</a>
    <span>›</span>
    <a href="{{ route('crm.opportunities.index') }}" class="text-gray-500 hover:text-indigo-600">الفرص</a>
    <span>›</span>
    <span class="text-indigo-900 font-semibold truncate">عرض خط الأنابيب</span>
@endsection

@section('content')
@php
    $stageDefs = config('crm_opportunities.stages', []);
@endphp
<div class="space-y-6" dir="rtl">
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <div class="flex flex-wrap items-center gap-2">
                <h1 class="text-2xl font-bold text-gray-900">عرض خط الأنابيب</h1>
                <span class="inline-flex items-center shrink-0"><x-info field="crm.opportunities_pipeline_intro" /></span>
            </div>
        </div>
        <a href="{{ route('crm.opportunities.index') }}" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-lg border border-gray-300 bg-white text-gray-700 text-sm font-medium hover:bg-gray-50 transition">العودة للجدول</a>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 xl:grid-cols-5 gap-4">
        @foreach($stageDefs as $def)
            @php
                $key = $def['value'] ?? '';
                $label = $def['label'] ?? $key;
                $items = $grouped[$key] ?? collect();
                $badge = \App\Models\CrmOpportunity::badgeClassesForStage($key);
            @endphp
            <div class="bg-white rounded-lg border border-gray-200 shadow-sm flex flex-col min-h-[16rem] overflow-hidden">
                <div class="px-3 py-3 border-b border-gray-100 bg-gray-50/80">
                    <span class="inline-flex px-2.5 py-1 rounded-lg text-sm font-semibold {{ $badge }}">{{ $label }}</span>
                    <span class="text-sm text-gray-600 mr-2 tabular-nums font-medium">({{ $items->count() }})</span>
                </div>
                <div class="p-3 space-y-2 flex-1 overflow-y-auto max-h-[calc(100vh-14rem)]">
                    @forelse($items as $opp)
                        <div class="rounded-lg border border-gray-100 bg-gray-50/60 p-3 text-sm">
                            <p class="font-semibold text-gray-900 mb-1">{{ $opp->title }}</p>
                            <p class="text-sm text-gray-500 font-mono mb-2">{{ $opp->opportunity_number }}</p>
                            @if($opp->customer)
                                <a href="{{ route('crm.customers.show', $opp->customer) }}" class="text-sm text-blue-600 hover:underline">{{ $opp->customer->display_name }}</a>
                            @endif
                            <p class="text-sm text-gray-600 mt-2 tabular-nums">مرجّح: {{ number_format((float) $opp->weighted_value, 2) }}</p>
                        </div>
                    @empty
                        <p class="text-sm text-gray-400 text-center py-6 mb-0">لا توجد فرص</p>
                    @endforelse
                </div>
            </div>
        @endforeach
    </div>
</div>
@endsection
