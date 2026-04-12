@extends('layouts.app')

@section('title', 'العقود والاشتراكات - ' . config('app.name'))

@section('breadcrumb')
    <a href="{{ route('dashboard') }}" class="text-gray-500 hover:text-indigo-600">الرئيسية</a>
    <span>›</span>
    <a href="{{ route('sales.dashboard') }}" class="text-gray-500 hover:text-indigo-600">المبيعات</a>
    <span>›</span>
    <span class="text-indigo-900 font-semibold">العقود والاشتراكات</span>
@endsection

@section('content')
<div class="max-w-full" dir="rtl">

    <div class="flex flex-wrap items-center justify-between gap-4 mb-6">
        <div class="flex items-center gap-3">
            <h1 class="text-2xl font-bold text-gray-900">العقود والاشتراكات</h1>
            <span class="text-gray-500 text-sm hidden sm:inline">إدارة العقود المتكررة والاشتراكات</span>
        </div>
        <a href="{{ route('sales.contracts.create') }}" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-lg text-white font-medium text-sm transition shadow-sm bg-indigo-600 hover:bg-indigo-700">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" viewBox="0 0 16 16"><path d="M8 4a.5.5 0 0 1 .5.5v3h3a.5.5 0 0 1 0 1h-3v3a.5.5 0 0 1-1 0v-3h-3a.5.5 0 0 1 0-1h3v-3A.5.5 0 0 1 8 4z"/></svg>
            عقد جديد
        </a>
    </div>

    {{-- البطاقات الإحصائية --}}
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-4 flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-500 mb-0.5">إجمالي العقود</p>
                <p class="text-xl font-bold text-gray-900">{{ $totalContracts }}</p>
                <p class="text-xs text-gray-500">{{ $activeCount }} نشط</p>
            </div>
            <div class="w-10 h-10 rounded-lg flex items-center justify-center flex-shrink-0 bg-indigo-50 text-indigo-600">
                <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" fill="currentColor" viewBox="0 0 16 16"><path d="M14 14V4.5L9.5 0H4a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h8a2 2 0 0 0 2-2zM9.5 3A1.5 1.5 0 0 0 11 4.5h2V14a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1V2a1 1 0 0 1 1-1h5.5v2z"/></svg>
            </div>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-4 flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-500 mb-0.5">MRR</p>
                <p class="text-xl font-bold text-gray-900">SAR {{ number_format($mrr, 2) }}</p>
                <p class="text-xs text-gray-500">الإيرادات الشهرية المتكررة</p>
            </div>
            <div class="w-10 h-10 rounded-lg flex items-center justify-center flex-shrink-0 bg-emerald-50 text-emerald-600">
                <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" fill="currentColor" viewBox="0 0 16 16"><path d="M4 10.781c.148 1.667 1.513 2.85 3.591 3.003V15h1.043v-1.216c2.27-.179 3.678-1.438 3.678-3.3 0-1.59-.947-2.51-2.956-3.028l-.722-.187V3.467c1.122.11 1.879.714 2.07 1.616h1.471c-.166-1.6-1.54-2.748-3.54-2.875V1H7.591v1.233c-1.939.23-3.27 1.472-3.27 3.156 0 1.454.966 2.483 2.661 2.917l.61.162v4.031c-1.149-.17-1.94-.8-2.131-1.718H4z"/></svg>
            </div>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-4 flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-500 mb-0.5">ARR</p>
                <p class="text-xl font-bold text-gray-900">SAR {{ number_format($arr, 2) }}</p>
                <p class="text-xs text-gray-500">الإيرادات السنوية المتكررة</p>
            </div>
            <div class="w-10 h-10 rounded-lg flex items-center justify-center flex-shrink-0 bg-amber-50 text-amber-600">
                <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" fill="currentColor" viewBox="0 0 16 16"><path d="M1 11a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1v3a1 1 0 0 1-1 1H2a1 1 0 0 1-1-1v-3zm5-4a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1v7a1 1 0 0 1-1 1H7a1 1 0 0 1-1-1V7zm5-5a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1v12a1 1 0 0 1-1 1h-2a1 1 0 0 1-1-1V2z"/></svg>
            </div>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-4 flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-500 mb-0.5">التجديدات</p>
                <p class="text-xl font-bold text-gray-900">{{ $renewalsThisMonth }}</p>
                <p class="text-xs text-gray-500">{{ $renewalsThisMonth }} تنتهي هذا الشهر</p>
            </div>
            <div class="w-10 h-10 rounded-lg flex items-center justify-center flex-shrink-0 bg-cyan-50 text-cyan-600">
                <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" fill="currentColor" viewBox="0 0 16 16"><path fill-rule="evenodd" d="M8 3a5 5 0 1 0 4.546 2.914.5.5 0 0 1 .908-.417A6 6 0 1 1 8 2v1z"/><path d="M8 4.466V.534a.25.25 0 0 1 .41-.192l2.36 1.966c.12.1.12.284 0 .384L8.41 4.658A.25.25 0 0 1 8 4.466z"/></svg>
            </div>
        </div>
    </div>

    {{-- البحث والفلاتر --}}
    <form method="GET" action="{{ route('sales.contracts.index') }}" class="bg-white rounded-xl border border-gray-200 shadow-sm p-4 mb-6">
        <div class="flex flex-wrap items-center gap-3">
            <div class="relative flex-1 min-w-[200px]">
                <span class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none text-gray-400">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" viewBox="0 0 16 16"><path d="M11.742 10.344a6.5 6.5 0 1 0-1.397 1.398h-.001c.03.04.062.078.098.115l3.85 3.85a1 1 0 0 0 1.415-1.414l-3.85-3.85a1.007 1.007 0 0 0-.115-.1zM12 6.5a5.5 5.5 0 1 1-11 0 5.5 5.5 0 0 1 11 0z"/></svg>
                </span>
                <input type="search" name="q" value="{{ request('q') }}" placeholder="بحث في العقود..." class="w-full pr-10 pl-3 py-2 border border-gray-300 rounded-lg text-sm text-right focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
            </div>
            <select name="status" class="py-2 px-3 border border-gray-300 rounded-lg text-sm bg-white min-w-[120px] text-right">
                @foreach($statuses as $value => $label)
                    <option value="{{ $value }}" {{ request('status') === (string) $value ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
            </select>
            <select name="type" class="py-2 px-3 border border-gray-300 rounded-lg text-sm bg-white min-w-[120px] text-right">
                @foreach($types as $value => $label)
                    <option value="{{ $value }}" {{ request('type') === (string) $value ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
            </select>
            <button type="submit" class="px-4 py-2 rounded-lg bg-gray-100 text-gray-700 text-sm font-medium hover:bg-gray-200 transition">تطبيق</button>
        </div>
    </form>

    {{-- جدول العقود --}}
    <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-right">
                <thead class="bg-gray-50 text-gray-600 border-b border-gray-200">
                    <tr>
                        <th class="py-3 px-4 font-medium">رقم العقد</th>
                        <th class="py-3 px-4 font-medium">اسم العقد</th>
                        <th class="py-3 px-4 font-medium">العميل</th>
                        <th class="py-3 px-4 font-medium">نوع العقد</th>
                        <th class="py-3 px-4 font-medium">دورة الفوترة</th>
                        <th class="py-3 px-4 font-medium">المبلغ</th>
                        <th class="py-3 px-4 font-medium">الفاتورة التالية</th>
                        <th class="py-3 px-4 font-medium">الحالة</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($contracts as $c)
                        <tr class="border-b border-gray-100 hover:bg-gray-50/50">
                            <td class="py-3 px-4 text-gray-900 font-medium">{{ $c->contract_number ?? 'CON-' . $c->id }}</td>
                            <td class="py-3 px-4 text-gray-900">{{ $c->name }}</td>
                            <td class="py-3 px-4 text-gray-700">{{ $c->customer?->name ?? '-' }}</td>
                            <td class="py-3 px-4 text-gray-700">{{ $c->type === 'service' ? 'خدمة' : 'منتج' }}</td>
                            <td class="py-3 px-4 text-gray-700">{{ $c->billing_cycle === 'monthly' ? 'شهري' : ($c->billing_cycle === 'quarterly' ? 'ربع سنوي' : 'سنوي') }}</td>
                            <td class="py-3 px-4 text-gray-900">SAR {{ number_format($c->total, 2) }}</td>
                            <td class="py-3 px-4 text-gray-700">{{ $c->next_invoice_date?->format('Y-m-d') ?? '—' }}</td>
                            <td class="py-3 px-4">
                                @if($c->status === 'active')
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">نشط</span>
                                @elseif($c->status === 'expired')
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-700">منتهي</span>
                                @else
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">ملغي</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="py-16 text-center text-gray-500">لا توجد عقود</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($contracts->hasPages())
            <div class="px-4 py-3 border-t border-gray-200">
                {{ $contracts->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
