@extends('layouts.app')

@section('title', 'أهداف المبيعات - ' . config('app.name'))

@section('breadcrumb')
    <a href="{{ route('dashboard') }}" class="text-gray-500 hover:text-indigo-600">الرئيسية</a>
    <span>›</span>
    <a href="{{ route('sales.dashboard') }}" class="text-gray-500 hover:text-indigo-600">المبيعات</a>
    <span>›</span>
    <span class="text-indigo-900 font-semibold">أهداف المبيعات</span>
@endsection

@section('content')
<div class="max-w-full">

    <div class="flex flex-wrap items-center justify-between gap-4 mb-6">
        <h1 class="text-2xl font-bold text-gray-900">أهداف المبيعات</h1>
        <a href="{{ route('sales.targets.create') }}" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-lg text-white font-medium text-sm transition shadow-sm" style="background: #2563eb;">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" viewBox="0 0 16 16"><path d="M8 4a.5.5 0 0 1 .5.5v3h3a.5.5 0 0 1 0 1h-3v3a.5.5 0 0 1-1 0v-3h-3a.5.5 0 0 1 0-1h3v-3A.5.5 0 0 1 8 4z"/></svg>
            هدف جديد
        </a>
    </div>

    {{-- البطاقات --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-4 flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-500 mb-0.5">الأهداف النشطة</p>
                <p class="text-2xl font-bold text-gray-900">{{ $activeCount }}</p>
            </div>
            <div class="w-10 h-10 rounded-lg flex items-center justify-center flex-shrink-0" style="background: rgba(37, 99, 235, 0.15); color: #2563eb;">
                <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" fill="currentColor" viewBox="0 0 16 16"><path d="M3 0a1 1 0 0 0-1 1v14a1 1 0 0 0 1.555.832L8 13.101l4.445 2.731A1 1 0 0 0 14 15V1a1 1 0 0 0-1-1H3z"/></svg>
            </div>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-4 flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-500 mb-0.5">محققة</p>
                <p class="text-2xl font-bold text-gray-900">{{ $achievedCount }}</p>
            </div>
            <div class="w-10 h-10 rounded-lg flex items-center justify-center flex-shrink-0" style="background: rgba(34, 197, 94, 0.15); color: #16a34a;">
                <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" fill="currentColor" viewBox="0 0 16 16"><path d="M16 8A8 8 0 1 1 0 8a8 8 0 0 1 16 0zM6.904 11.803 4.151 9.05a.5.5 0 1 1 .707-.707l1.89 1.89 4.39-4.39a.5.5 0 0 1 .707.708l-4.743 4.742a.5.5 0 0 1-.707 0z"/></svg>
            </div>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-4 flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-500 mb-0.5">متوسط الإنجاز</p>
                <p class="text-2xl font-bold text-gray-900">{{ $avgCompletion }}%</p>
            </div>
            <div class="w-10 h-10 rounded-lg flex items-center justify-center flex-shrink-0" style="background: rgba(234, 179, 8, 0.15); color: #ca8a04;">
                <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" fill="currentColor" viewBox="0 0 16 16"><path d="M8 3a5 5 0 1 0 4.546 2.914.5.5 0 0 1 .908-.417A6 6 0 1 1 8 2v1z"/><path d="M8 4.466V.534a.25.25 0 0 1 .41-.192l2.36 1.966c.12.1.12.284 0 .384L8.41 4.658A.25.25 0 0 1 8 4.466z"/></svg>
            </div>
        </div>
    </div>

    {{-- الجدول --}}
    <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-right">
                <thead class="bg-gray-50 text-gray-600 border-b border-gray-200">
                    <tr>
                        <th class="py-3 px-4 font-medium">اسم الهدف</th>
                        <th class="py-3 px-4 font-medium">مخصص لـ</th>
                        <th class="py-3 px-4 font-medium">نوع الهدف</th>
                        <th class="py-3 px-4 font-medium">الفترة</th>
                        <th class="py-3 px-4 font-medium">الهدف</th>
                        <th class="py-3 px-4 font-medium">الإنجاز</th>
                        <th class="py-3 px-4 font-medium">الحالة</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($targets as $t)
                        @php
                            $percent = $t->completion_percent;
                            if ($percent < 0) $percent = 0;
                            $barColor = $percent < 50 ? 'bg-red-500' : ($percent < 90 ? 'bg-amber-500' : 'bg-green-500');
                        @endphp
                        <tr class="border-b border-gray-100 hover:bg-gray-50/50">
                            <td class="py-3 px-4 text-gray-900 font-medium">{{ $t->name }}</td>
                            <td class="py-3 px-4 text-gray-700">{{ $t->assigned_name }}</td>
                            <td class="py-3 px-4 text-gray-700">الإيرادات</td>
                            <td class="py-3 px-4 text-gray-700">
                                {{ ucfirst(__($t->period)) }}
                                <div class="text-xs text-gray-400 mt-0.5">{{ $t->start_date?->format('Y-m-d') }} - {{ $t->end_date?->format('Y-m-d') }}</div>
                            </td>
                            <td class="py-3 px-4 text-gray-900">
                                SAR {{ number_format($t->target_amount, 2) }}
                            </td>
                            <td class="py-3 px-4">
                                <div class="flex items-center gap-2">
                                    <div class="flex-1 h-2.5 rounded-full bg-gray-100 overflow-hidden">
                                        <div class="h-2.5 {{ $barColor }}" style="width: {{ min(100, $percent) }}%;"></div>
                                    </div>
                                    <span class="text-xs font-medium text-gray-800 min-w-[48px] text-left">{{ number_format($percent, 1) }}%</span>
                                </div>
                                <div class="text-[11px] text-gray-500 mt-1">
                                    SAR {{ number_format($t->achieved_amount, 2) }} / {{ number_format($t->target_amount, 2) }}
                                </div>
                            </td>
                            <td class="py-3 px-4">
                                @if($t->status === 'completed')
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">محقق</span>
                                @elseif($t->status === 'expired')
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">منتهي</span>
                                @else
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">نشط</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="py-16 text-center text-gray-500">لا توجد أهداف مبيعات</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($targets->hasPages())
            <div class="px-4 py-3 border-top border-gray-200">
                {{ $targets->links() }}
            </div>
        @endif
    </div>
</div>
@endsection

