@extends('layouts.app')

@section('title', 'الموازنات - MIRADA ERP')

@section('breadcrumb')
    <a href="{{ route('dashboard') }}" class="text-gray-500 hover:text-blue-600">الرئيسية</a>
    <span class="mx-1 text-gray-400">›</span>
    <a href="{{ route('finance.dashboard') }}" class="text-gray-500 hover:text-blue-600">المحاسبة</a>
    <span class="mx-1 text-gray-400">›</span>
    <span class="text-blue-900 font-semibold">الموازنات</span>
@endsection

@section('content')
<div dir="rtl" class="mx-auto w-full max-w-full space-y-6">
    <section class="flex flex-wrap items-start justify-between gap-4 rounded-lg bg-white p-4 md:p-5">
        <div>
            <h1 class="text-3xl font-bold text-gray-900">الموازنات</h1>
            <p class="mt-1 text-sm text-gray-500">تخطيط ومتابعة موازنات المؤسسة</p>
        </div>
        <a href="{{ route('finance.budgets.create') }}" class="inline-flex items-center gap-2 rounded-lg bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-blue-700">
            <span class="text-base leading-none">+</span>
            ميزانية جديدة
        </a>
    </section>

    <section class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-4">
        <div class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm">
            <p class="text-xs font-medium text-gray-500">إجمالي المخطط <x-info field="budget_total_planned" /></p>
            <p class="mt-2 text-2xl font-bold text-gray-900">SAR {{ number_format((float) $stats['planned'], 2) }}</p>
        </div>
        <div class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm">
            <p class="text-xs font-medium text-gray-500">إجمالي الفعلي <x-info field="budget_total_actual" /></p>
            <p class="mt-2 text-2xl font-bold text-gray-900">SAR {{ number_format((float) $stats['actual'], 2) }}</p>
        </div>
        <div class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm">
            <p class="text-xs font-medium text-gray-500">إجمالي الفرق <x-info field="budget_variance" /></p>
            @php
                $variance = (float) $stats['variance'];
                $varianceColor = $variance > 0 ? 'text-red-600' : 'text-green-600';
                $varianceLabel = $variance > 0 ? 'فوق الميزانية' : 'تحت الميزانية';
            @endphp
            <p class="mt-2 text-2xl font-bold {{ $varianceColor }}">SAR {{ number_format(abs($variance), 2) }}</p>
            <p class="mt-1 text-xs {{ $varianceColor }}">{{ $varianceLabel }} ({{ number_format((float) $stats['variance_percent'], 2) }}%)</p>
        </div>
        <div class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm">
            <p class="text-xs font-medium text-gray-500">الموازنات النشطة <x-info field="budget_active_count" /></p>
            <p class="mt-2 text-2xl font-bold text-gray-900">{{ (int) $stats['active_count'] }}</p>
        </div>
    </section>

    <section class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm">
        <div class="space-y-4 p-4">
            <form method="GET" action="{{ route('finance.budgets.index') }}" class="space-y-4">
                <div class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-4 lg:items-end">
                <div class="space-y-1">
                    <label class="inline-flex items-center gap-1 text-xs font-medium text-gray-600">
                        <span>السنة المالية</span>
                        <x-info field="fiscal_year" />
                    </label>
                    <select name="fiscal_year" class="h-10 w-full rounded-lg border border-gray-200 bg-gray-50 px-3 text-sm focus:border-blue-500 focus:ring-blue-500">
                        <option value="">كل السنوات</option>
                        @foreach($fiscalYears as $year)
                            <option value="{{ $year }}" @selected($fiscalYear !== null && (int) $fiscalYear === (int) $year)>{{ $year }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="space-y-1">
                    <label class="inline-flex items-center gap-1 text-xs font-medium text-gray-600">
                        <span>الحالات</span>
                        <x-info field="budget_status" />
                    </label>
                    <select name="status" class="h-10 w-full rounded-lg border border-gray-200 bg-gray-50 px-3 text-sm focus:border-blue-500 focus:ring-blue-500">
                        <option value="">جميع الحالات</option>
                        <option value="draft" @selected($status === 'draft')>مسودة</option>
                        <option value="active" @selected($status === 'active')>نشطة</option>
                        <option value="closed" @selected($status === 'closed')>مغلقة</option>
                    </select>
                </div>
                <div class="space-y-1">
                    <label class="inline-flex items-center gap-1 text-xs font-medium text-gray-600">
                        <span>البحث في الموازنات</span>
                        <x-info field="budget_search" />
                    </label>
                    <input type="search" name="search" value="{{ $search }}" placeholder="اسم الميزانية..." class="h-10 w-full rounded-lg border border-gray-200 bg-gray-50 px-3 text-sm focus:border-blue-500 focus:ring-blue-500">
                </div>
                <div class="space-y-1">
                    <label class="inline-flex items-center gap-1 text-xs font-medium text-gray-600">
                        <span>المؤرشفة</span>
                        <x-info field="budget_archived_filter" />
                    </label>
                    <label class="inline-flex h-10 w-full items-center gap-2 rounded-lg border border-gray-200 bg-gray-50 px-3 text-sm font-medium text-gray-700">
                        <input type="checkbox" name="show_archived" value="1" @checked($showArchived) class="h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                        <span>عرض الموازنات المؤرشفة</span>
                    </label>
                </div>
                </div>
                <div class="flex justify-end">
                    <button type="submit" class="h-10 rounded-lg bg-blue-600 px-5 text-sm font-semibold text-white hover:bg-blue-700">استعراض</button>
                </div>
            </form>

            @if($showArchived)
                <div class="inline-flex items-center gap-2 rounded-lg border border-violet-200 bg-violet-50 px-3 py-2 text-xs font-semibold text-violet-800">
                    <span class="inline-block h-2 w-2 rounded-full bg-violet-600"></span>
                    وضع عرض الموازنات المؤرشفة مفعل
                </div>
            @endif

            <div class="overflow-x-auto rounded-lg border border-gray-100">
                <table class="w-full min-w-[1080px] text-sm">
                    <thead class="bg-gray-50 text-xs font-semibold text-gray-500">
                        <tr>
                            <th class="px-4 py-3 text-right">اسم الميزانية <x-info field="budget_name" /></th>
                            <th class="px-4 py-3 text-right">السنة المالية <x-info field="fiscal_year" /></th>
                            <th class="px-4 py-3 text-right">الفترة <x-info field="budget_period" /></th>
                            <th class="px-4 py-3 text-right">الحالة <x-info field="budget_status" /></th>
                            <th class="px-4 py-3 text-right">المخطط <x-info field="budget_total_planned" /></th>
                            <th class="px-4 py-3 text-right">الفعلي <x-info field="budget_total_actual" /></th>
                            <th class="px-4 py-3 text-right">الفرق <x-info field="budget_variance" /></th>
                            <th class="px-4 py-3 text-right">الإجراءات <x-info field="budget_actions" /></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse($budgets as $budget)
                            @php
                                $rowVariance = (float) $budget->variance;
                                $rowVarianceClass = $rowVariance > 0 ? 'text-red-600' : 'text-green-600';
                            @endphp
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-3 text-gray-800">{{ $budget->name }}</td>
                                <td class="px-4 py-3 text-gray-700">{{ $budget->fiscal_year }}</td>
                                <td class="px-4 py-3 text-gray-700">{{ $budget->start_date->format('Y-m-d') }} - {{ $budget->end_date->format('Y-m-d') }}</td>
                                <td class="px-4 py-3">
                                    @if($budget->archived_at)
                                        <span class="inline-flex rounded-full bg-violet-100 px-2.5 py-0.5 text-xs font-medium text-violet-800">مؤرشفة</span>
                                    @elseif($budget->status === 'active')
                                        <span class="inline-flex rounded-full bg-blue-100 px-2.5 py-0.5 text-xs font-medium text-blue-700">نشطة</span>
                                    @elseif($budget->status === 'closed')
                                        <span class="inline-flex rounded-full bg-gray-100 px-2.5 py-0.5 text-xs font-medium text-gray-600">مغلقة</span>
                                    @else
                                        <span class="inline-flex rounded-full bg-yellow-100 px-2.5 py-0.5 text-xs font-medium text-yellow-700">مسودة</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-gray-800">SAR {{ number_format((float) $budget->planned_total, 2) }}</td>
                                <td class="px-4 py-3 text-gray-800">SAR {{ number_format((float) $budget->actual_total, 2) }}</td>
                                <td class="px-4 py-3 font-semibold {{ $rowVarianceClass }}">SAR {{ number_format((float) $budget->variance, 2) }}</td>
                                <td class="px-4 py-3">
                                    <div class="inline-flex items-center gap-1">
                                        @if($budget->archived_at)
                                            <a href="{{ route('finance.budgets.show', $budget) }}" class="inline-flex h-8 w-8 items-center justify-center rounded-md border border-gray-200 bg-white text-gray-600 hover:bg-gray-50 hover:text-blue-600" title="عرض">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5s8.268 2.943 9.542 7c-1.274 4.057-5.065 7-9.542 7S3.732 16.057 2.458 12z" /></svg>
                                            </a>
                                        @elseif($budget->status === 'draft' && ! $budget->archived_at)
                                            <a href="{{ route('finance.budgets.edit', $budget) }}" class="inline-flex h-8 w-8 items-center justify-center rounded-md border border-gray-200 bg-white text-gray-600 hover:bg-gray-50 hover:text-blue-600" title="تعديل">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.586a2 2 0 112.828 2.828L11.828 14.828a4 4 0 01-1.414.943l-3.029 1.01 1.01-3.029a4 4 0 01.943-1.414l8.586-8.586z" /></svg>
                                            </a>
                                            <form method="POST" action="{{ route('finance.budgets.destroy', $budget) }}" onsubmit="return confirm('هل أنت متأكد من حذف هذه الموازنة؟');" class="inline">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="inline-flex h-8 w-8 items-center justify-center rounded-md border border-red-200 bg-white text-red-500 hover:bg-red-50 hover:text-red-600" title="حذف">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6M9 7V4a1 1 0 011-1h4a1 1 0 011 1v3M4 7h16" /></svg>
                                                </button>
                                            </form>
                                            <form method="POST" action="{{ route('finance.budgets.activate', $budget) }}" class="inline">
                                                @csrf
                                                <button type="submit" class="inline-flex h-8 w-8 items-center justify-center rounded-md border border-green-200 bg-white text-green-600 hover:bg-green-50" title="تفعيل">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                                                </button>
                                            </form>
                                        @elseif($budget->status === 'active')
                                            <a href="{{ route('finance.budgets.show', $budget) }}" class="inline-flex h-8 w-8 items-center justify-center rounded-md border border-gray-200 bg-white text-gray-600 hover:bg-gray-50 hover:text-blue-600" title="عرض">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5s8.268 2.943 9.542 7c-1.274 4.057-5.065 7-9.542 7S3.732 16.057 2.458 12z" /></svg>
                                            </a>
                                            <form method="POST" action="{{ route('finance.budgets.close', $budget) }}" onsubmit="return confirm('هل أنت متأكد من إغلاق هذه الموازنة؟ سيتم حفظ الصورة النهائية للفروقات.');" class="inline">
                                                @csrf
                                                <button type="submit" class="inline-flex h-8 w-8 items-center justify-center rounded-md border border-amber-200 bg-white text-amber-600 hover:bg-amber-50" title="إغلاق">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2h-1V9a5 5 0 00-10 0v2H6a2 2 0 00-2 2v6a2 2 0 002 2zm3-10V9a3 3 0 116 0v2H9z" /></svg>
                                                </button>
                                            </form>
                                        @else
                                            <a href="{{ route('finance.budgets.show', $budget) }}" class="inline-flex h-8 w-8 items-center justify-center rounded-md border border-gray-200 bg-white text-gray-600 hover:bg-gray-50 hover:text-blue-600" title="عرض">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5s8.268 2.943 9.542 7c-1.274 4.057-5.065 7-9.542 7S3.732 16.057 2.458 12z" /></svg>
                                            </a>
                                            <form method="POST" action="{{ route('finance.budgets.archive', $budget) }}" class="inline">
                                                @csrf
                                                <button type="submit" class="inline-flex h-8 w-8 items-center justify-center rounded-md border border-indigo-200 bg-white text-indigo-600 hover:bg-indigo-50 {{ $budget->archived_at ? 'opacity-50 cursor-not-allowed' : '' }}" title="أرشفة" {{ $budget->archived_at ? 'disabled' : '' }}>
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8l2 12h10l2-12M3 5h18v3H3V5z" /></svg>
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="px-4 py-20 text-center text-sm text-gray-500">لا توجد بيانات</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        @if($budgets->hasPages())
            <div class="border-t border-gray-100 px-4 py-3">
                {{ $budgets->links() }}
            </div>
        @endif
    </section>
</div>
@endsection

