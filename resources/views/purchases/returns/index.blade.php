@extends('layouts.app')

@section('title', 'مرتجعات المشتريات - MIRADA ERP')

@section('breadcrumb')
    <a href="{{ route('dashboard') }}" class="text-gray-500 hover:text-indigo-600">الرئيسية</a>
    <span>›</span>
    <a href="{{ route('purchases.dashboard') }}" class="text-gray-500 hover:text-indigo-600">المشتريات</a>
    <span>›</span>
    <span class="text-indigo-900 font-semibold">مرتجعات المشتريات</span>
@endsection

@push('styles')
<style>
    .ret-widget { background: #fff; border-radius: 1rem; border: 1px solid #e5e7eb; box-shadow: 0 1px 3px rgba(0,0,0,0.06); padding: 1rem 1.25rem; }
    .ret-table-card { background: #fff; border-radius: 1rem; border: 1px solid #e5e7eb; box-shadow: 0 1px 3px rgba(0,0,0,0.06); }
    .ret-badge { padding: 0.25rem 0.6rem; border-radius: 9999px; font-size: 0.8rem; font-weight: 500; }
    .ret-badge-pending { background: rgba(245, 158, 11, 0.2); color: #b45309; }
    .ret-badge-shipped { background: rgba(59, 130, 246, 0.2); color: #2563eb; }
    .ret-badge-completed { background: rgba(34, 197, 94, 0.15); color: #15803d; }
</style>
@endpush

@section('content')
<div class="max-w-full" dir="rtl">

    <div class="flex flex-wrap items-center justify-between gap-4 mb-6">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-2xl flex items-center justify-center flex-shrink-0" style="background: rgba(124, 58, 237, 0.2); color: #7c3aed;">
                <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" fill="currentColor" viewBox="0 0 16 16"><path fill-rule="evenodd" d="M8 3a5 5 0 1 0 4.546 2.914.5.5 0 0 1 .908-.417A6 6 0 1 1 8 2v1z"/><path d="M8 4.466V.534a.25.25 0 0 1 .41-.192l2.36 1.966c.12.1.12.284 0 .384L8.41 4.658A.25.25 0 0 1 8 4.466z"/></svg>
            </div>
            <h1 class="text-2xl font-bold text-gray-900">مرتجعات المشتريات</h1>
        </div>
        <a href="{{ route('purchases.returns.create') }}" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-2xl text-white font-medium text-sm transition shadow-sm" style="background: #2563eb;">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" viewBox="0 0 16 16"><path d="M8 4a.5.5 0 0 1 .5.5v3h3a.5.5 0 0 1 0 1h-3v3a.5.5 0 0 1-1 0v-3h-3a.5.5 0 0 1 0-1h3v-3A.5.5 0 0 1 8 4z"/></svg>
            مرتجع جديد
        </a>
    </div>

    {{-- بطاقات الإحصائيات --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <div class="ret-widget flex items-center gap-4">
            <div class="w-12 h-12 rounded-2xl flex items-center justify-center flex-shrink-0" style="background: rgba(34, 197, 94, 0.2); color: #16a34a;">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 16 16"><path d="M4 10.781c.148 1.667 1.513 2.85 3.591 3.003V15h1.043v-1.216c2.27-.179 3.678-1.438 3.678-3.3 0-1.59-.947-2.51-2.956-3.028l-.722-.187V3.467c1.122.11 1.879.714 2.07 1.616h1.471c-.166-1.6-1.54-2.748-3.54-2.875V1H7.591v1.233c-1.939.23-3.27 1.472-3.27 3.156 0 1.454.966 2.483 2.661 2.917l.61.162v4.031c-1.149-.17-1.94-.8-2.131-1.718H4z"/></svg>
            </div>
            <div>
                <p class="text-sm text-gray-500 font-medium">المبلغ المرتجع</p>
                <p class="text-xl font-bold text-gray-900">SAR {{ number_format($totalReturnedAmount, 2) }}</p>
                <p class="text-xs text-gray-500">{{ $totalCount }} مرتجعات</p>
            </div>
        </div>
        <div class="ret-widget flex items-center gap-4">
            <div class="w-12 h-12 rounded-2xl flex items-center justify-center flex-shrink-0" style="background: rgba(59, 130, 246, 0.2); color: #2563eb;">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 16 16"><path d="M0 3.5A1.5 1.5 0 0 1 1.5 2h9A1.5 1.5 0 0 1 12 3.5v7.5a.5.5 0 0 1-1 0V5a.5.5 0 0 0-.5-.5h-9a.5.5 0 0 0-.5.5v7a.5.5 0 0 0 .5.5h2a.5.5 0 0 1 0 1h-2A1.5 1.5 0 0 1 0 10.5v-7z"/><path d="M1 14.5a.5.5 0 0 1 .5-.5h4a.5.5 0 0 1 .5.5v-2a.5.5 0 0 1 .5-.5h6a.5.5 0 0 1 .5.5v2a.5.5 0 0 1-.5.5h-6a.5.5 0 0 1-.5-.5v-2H1.5a.5.5 0 0 1-.5-.5z"/></svg>
            </div>
            <div>
                <p class="text-sm text-gray-500 font-medium">المرتجعات المشحونة</p>
                <p class="text-xl font-bold text-gray-900">{{ $shippedCount }}</p>
            </div>
        </div>
        <div class="ret-widget flex items-center gap-4">
            <div class="w-12 h-12 rounded-2xl flex items-center justify-center flex-shrink-0" style="background: rgba(245, 158, 11, 0.2); color: #d97706;">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 16 16"><path fill-rule="evenodd" d="M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14zm0 1A8 8 0 1 0 8 0a8 8 0 0 0 0 16z"/><path d="M7.5 3a.5.5 0 0 1 .5.5v5.21l3.248 1.856a.5.5 0 0 1-.496.868l-3.5-2A.5.5 0 0 1 7 9V3.5a.5.5 0 0 1 .5-.5z"/></svg>
            </div>
            <div>
                <p class="text-sm text-gray-500 font-medium">المرتجعات المعلقة</p>
                <p class="text-xl font-bold text-gray-900">{{ $pendingCount }}</p>
            </div>
        </div>
        <div class="ret-widget flex items-center gap-4">
            <div class="w-12 h-12 rounded-2xl flex items-center justify-center flex-shrink-0" style="background: rgba(124, 58, 237, 0.2); color: #7c3aed;">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 16 16"><path fill-rule="evenodd" d="M8 3a5 5 0 1 0 4.546 2.914.5.5 0 0 1 .908-.417A6 6 0 1 1 8 2v1z"/><path d="M8 4.466V.534a.25.25 0 0 1 .41-.192l2.36 1.966c.12.1.12.284 0 .384L8.41 4.658A.25.25 0 0 1 8 4.466z"/></svg>
            </div>
            <div>
                <p class="text-sm text-gray-500 font-medium">إجمالي المرتجعات</p>
                <p class="text-xl font-bold text-gray-900">{{ $totalCount }}</p>
            </div>
        </div>
    </div>

    {{-- شريط الأدوات والبحث --}}
    <form method="GET" action="{{ route('purchases.returns.index') }}" class="ret-table-card p-4 mb-4">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div class="flex items-center gap-3">
                <select name="status" class="px-3 py-2 rounded-xl border border-gray-300 text-sm bg-white min-w-[140px]">
                    <option value="">جميع الحالات</option>
                    <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>معلق</option>
                    <option value="shipped" {{ request('status') === 'shipped' ? 'selected' : '' }}>مشحون</option>
                    <option value="completed" {{ request('status') === 'completed' ? 'selected' : '' }}>مكتمل</option>
                </select>
                <input type="search" name="q" value="{{ request('q') }}" placeholder="البحث في المرتجعات..." class="w-56 px-3 py-2 rounded-xl border border-gray-300 text-sm focus:ring-2 focus:ring-indigo-500">
                <button type="submit" class="px-4 py-2 rounded-xl bg-indigo-600 text-white text-sm font-medium hover:bg-indigo-700">بحث</button>
            </div>
            <span class="text-sm text-gray-600">الإجمالي <span class="font-semibold text-gray-900">{{ $returns->total() }}</span></span>
        </div>
    </form>

    {{-- جدول المرتجعات --}}
    <div class="ret-table-card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-right">
                <thead class="bg-gray-50 border-b border-gray-200">
                    <tr>
                        <th class="py-3 px-4 font-medium text-gray-600">رقم المرتجع</th>
                        <th class="py-3 px-4 font-medium text-gray-600">التاريخ</th>
                        <th class="py-3 px-4 font-medium text-gray-600">المورد</th>
                        <th class="py-3 px-4 font-medium text-gray-600">السبب</th>
                        <th class="py-3 px-4 font-medium text-gray-600">الأصناف</th>
                        <th class="py-3 px-4 font-medium text-gray-600">الإجمالي</th>
                        <th class="py-3 px-4 font-medium text-gray-600">الحالة</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($returns as $r)
                    <tr class="border-b border-gray-100 hover:bg-gray-50/50">
                        <td class="py-3 px-4 font-medium text-gray-800">{{ $r->code ?? 'PR-' . $r->id }}</td>
                        <td class="py-3 px-4 text-gray-600">{{ $r->date?->format('Y-m-d') ?? '—' }}</td>
                        <td class="py-3 px-4 text-gray-600">{{ $r->supplier?->name ?? '—' }}</td>
                        <td class="py-3 px-4 text-gray-600">{{ $r->reason_type ?? $r->reason ?? '—' }}</td>
                        <td class="py-3 px-4 text-gray-600">{{ $r->items_count ?? 0 }}</td>
                        <td class="py-3 px-4 text-gray-800">SAR {{ number_format((float) $r->total, 2) }}</td>
                        <td class="py-3 px-4">
                            @if($r->status === 'completed')
                                <span class="ret-badge ret-badge-completed">{{ $r->status_label }}</span>
                            @elseif($r->status === 'shipped')
                                <span class="ret-badge ret-badge-shipped">{{ $r->status_label }}</span>
                            @else
                                <span class="ret-badge ret-badge-pending">{{ $r->status_label }}</span>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="py-12 text-center">
                            <p class="text-gray-500 font-medium">لا توجد مرتجعات</p>
                            <p class="text-sm text-gray-400 mt-1">يمكنك إنشاء مرتجع جديد باستخدام الزر أعلاه.</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($returns->hasPages())
            <div class="px-4 py-3 border-t border-gray-200">{{ $returns->links() }}</div>
        @endif
    </div>
</div>
@endsection
