@extends('layouts.app')

@section('title', 'الموازنات - '.config('app.name'))

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
            <p class="mt-2 text-2xl font-bold text-gray-900">SAR {{ erp_money((float) $stats['planned']) }}</p>
        </div>
        <div class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm">
            <p class="text-xs font-medium text-gray-500">إجمالي الفعلي <x-info field="budget_total_actual" /></p>
            <p class="mt-2 text-2xl font-bold text-gray-900">SAR {{ erp_money((float) $stats['actual']) }}</p>
        </div>
        <div class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm">
            <p class="text-xs font-medium text-gray-500">إجمالي الفرق <x-info field="budget_variance" /></p>
            @php
                $variance = (float) $stats['variance'];
                $varianceColor = $variance > 0 ? 'text-red-600' : 'text-green-600';
                $varianceLabel = $variance > 0 ? 'فوق الميزانية' : 'تحت الميزانية';
            @endphp
            <p class="mt-2 text-2xl font-bold {{ $varianceColor }}">SAR {{ erp_money(abs($variance)) }}</p>
            <p class="mt-1 text-xs {{ $varianceColor }}">{{ $varianceLabel }} ({{ erp_qty((float) $stats['variance_percent']) }}%)</p>
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
                    @php
                        $budgetFilterYearOpts = collect($fiscalYears)->map(fn ($year) => [
                            'value' => (string) $year,
                            'label' => (string) $year,
                        ])->prepend(['value' => '', 'label' => 'كل السنوات'])->values()->all();
                        $budgetFilterYearSelected = $fiscalYear !== null ? (string) $fiscalYear : '';
                    @endphp
                    <x-custom-select
                        name="fiscal_year"
                        class="w-full"
                        :options="$budgetFilterYearOpts"
                        :selected="$budgetFilterYearSelected"
                        :empty-option="false"
                        placeholder="السنة المالية..."
                    />
                </div>
                <div class="space-y-1">
                    <label class="inline-flex items-center gap-1 text-xs font-medium text-gray-600">
                        <span>الحالات</span>
                        <x-info field="budget_status" />
                    </label>
                    @php
                        $budgetFilterStatusOpts = [
                            ['value' => '', 'label' => 'جميع الحالات'],
                            ['value' => 'draft', 'label' => 'مسودة'],
                            ['value' => 'active', 'label' => 'نشطة'],
                            ['value' => 'closed', 'label' => 'مغلقة'],
                        ];
                    @endphp
                    <x-custom-select
                        name="status"
                        class="w-full"
                        :options="$budgetFilterStatusOpts"
                        :selected="$status"
                        :empty-option="false"
                        placeholder="الحالة..."
                    />
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
                            <th scope="col" class="w-[1%] whitespace-nowrap px-4 py-3 text-center text-xs font-semibold text-gray-500">
                                <span class="inline-flex items-center justify-center gap-1">
                                    <x-info field="budget_actions" />
                                    الإجراءات
                                </span>
                            </th>
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
                                <td class="px-4 py-3 text-gray-800">SAR {{ erp_money((float) $budget->planned_total) }}</td>
                                <td class="px-4 py-3 text-gray-800">SAR {{ erp_money((float) $budget->actual_total) }}</td>
                                <td class="px-4 py-3 font-semibold {{ $rowVarianceClass }}">SAR {{ erp_money((float) $budget->variance) }}</td>
                                <td class="px-4 py-3 text-center align-middle">
                                    @php $budgetMenuId = 'budget-actions-'.$budget->id; @endphp
                                    <x-erp-actions-dropdown :menu-id="$budgetMenuId">
                                        @if($budget->archived_at)
                                            <a href="{{ route('finance.budgets.show', $budget) }}"
                                               class="erp-menu-item flex items-center gap-3 px-3 py-2.5 text-sm text-gray-800 transition hover:bg-gray-50"
                                               role="menuitem">
                                                <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-sky-100 text-sky-700">
                                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16"><path d="M16 8s-3-5.5-8-5.5S0 8 0 8s3 5.5 8 5.5S16 8 16 8zM1.173 8a13.133 13.133 0 0 1 1.66-2.043C4.12 4.668 5.88 3.5 8 3.5c2.12 0 3.879 1.168 5.168 2.457A13.133 13.133 0 0 1 14.828 8c-.086.13-.17.263-.252.394C12.879 10.668 11.12 11.5 8 11.5c-2.12 0-3.879-1.168-5.168-2.457A13.134 13.134 0 0 1 1.172 8z"/><path d="M8 5.5a2.5 2.5 0 1 0 0 5 2.5 2.5 0 0 0 0-5zM4.5 8a3.5 3.5 0 1 1 7 0 3.5 3.5 0 0 1-7 0z"/></svg>
                                                </span>
                                                <span class="flex-1 text-right font-medium leading-snug">عرض الموازنة</span>
                                            </a>
                                        @elseif($budget->status === 'draft' && ! $budget->archived_at)
                                            <a href="{{ route('finance.budgets.edit', $budget) }}"
                                               class="erp-menu-item flex items-center gap-3 px-3 py-2.5 text-sm text-gray-800 transition hover:bg-gray-50"
                                               role="menuitem">
                                                <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-gray-100 text-gray-600">
                                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16"><path d="M12.146.146a.5.5 0 0 1 .708 0l3 3a.5.5 0 0 1 0 .708l-9.5 9.5a.5.5 0 0 1-.168.11l-5 2a.5.5 0 0 1-.65-.65l2-5a.5.5 0 0 1 .11-.168zM11.207 2L3 10.207V12h1.793L13 3.793z"/></svg>
                                                </span>
                                                <span class="flex-1 text-right font-medium leading-snug">تعديل الموازنة</span>
                                            </a>
                                            <div class="mx-2 my-2 border-t border-gray-100"></div>
                                            <form method="POST" action="{{ route('finance.budgets.destroy', $budget) }}" class="m-0" onsubmit="return confirm('هل أنت متأكد من حذف هذه الموازنة؟');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit"
                                                        class="erp-menu-item flex w-full items-center gap-3 px-3 py-2.5 text-right text-sm font-medium text-red-700 transition hover:bg-red-50"
                                                        role="menuitem">
                                                    <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-red-50 text-red-600">
                                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16"><path d="M5.5 5.5A.5.5 0 0 1 6 6v6a.5.5 0 0 1-1 0V6a.5.5 0 0 1 .5-.5zm2.5 0a.5.5 0 0 1 .5.5v6a.5.5 0 0 1-1 0V6a.5.5 0 0 1 .5-.5zm3 .5a.5.5 0 0 0-1 0v6a.5.5 0 0 0 1 0z"/><path d="M14.5 3a1 1 0 0 1-1 1H13v9a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V4h-.5a1 1 0 0 1-1-1V2a1 1 0 0 1 1-1H6a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1h3.5a1 1 0 0 1 1 1zM4.118 4 4 4.059V13a1 1 0 0 0 1 1h6a1 1 0 0 0 1-1V4.059L11.882 4zM2.5 3h11V2h-11z"/></svg>
                                                    </span>
                                                    <span class="flex-1 leading-snug">حذف الموازنة</span>
                                                </button>
                                            </form>
                                            <div class="mx-2 my-2 border-t border-gray-100"></div>
                                            <form method="POST" action="{{ route('finance.budgets.activate', $budget) }}" class="m-0">
                                                @csrf
                                                <button type="submit"
                                                        class="erp-menu-item flex w-full items-center gap-3 px-3 py-2.5 text-right text-sm font-medium text-emerald-800 transition hover:bg-emerald-50"
                                                        role="menuitem">
                                                    <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-emerald-100 text-emerald-700">
                                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16"><path d="M10.97 4.97a.75.75 0 0 1 1.07 1.05l-3.99 4.99a.75.75 0 0 1-1.08.02L4.324 8.384a.75.75 0 1 1 1.06-1.06l2.094 2.093 3.473-4.425z"/></svg>
                                                    </span>
                                                    <span class="flex-1 leading-snug">تفعيل الموازنة</span>
                                                </button>
                                            </form>
                                        @elseif($budget->status === 'active')
                                            <a href="{{ route('finance.budgets.show', $budget) }}"
                                               class="erp-menu-item flex items-center gap-3 px-3 py-2.5 text-sm text-gray-800 transition hover:bg-gray-50"
                                               role="menuitem">
                                                <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-sky-100 text-sky-700">
                                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16"><path d="M16 8s-3-5.5-8-5.5S0 8 0 8s3 5.5 8 5.5S16 8 16 8zM1.173 8a13.133 13.133 0 0 1 1.66-2.043C4.12 4.668 5.88 3.5 8 3.5c2.12 0 3.879 1.168 5.168 2.457A13.133 13.133 0 0 1 14.828 8c-.086.13-.17.263-.252.394C12.879 10.668 11.12 11.5 8 11.5c-2.12 0-3.879-1.168-5.168-2.457A13.134 13.134 0 0 1 1.172 8z"/><path d="M8 5.5a2.5 2.5 0 1 0 0 5 2.5 2.5 0 0 0 0-5zM4.5 8a3.5 3.5 0 1 1 7 0 3.5 3.5 0 0 1-7 0z"/></svg>
                                                </span>
                                                <span class="flex-1 text-right font-medium leading-snug">عرض الموازنة</span>
                                            </a>
                                            <div class="mx-2 my-2 border-t border-gray-100"></div>
                                            <form method="POST" action="{{ route('finance.budgets.close', $budget) }}" class="m-0" onsubmit="return confirm('هل أنت متأكد من إغلاق هذه الموازنة؟ سيتم حفظ الصورة النهائية للفروقات.');">
                                                @csrf
                                                <button type="submit"
                                                        class="erp-menu-item flex w-full items-center gap-3 px-3 py-2.5 text-right text-sm font-medium text-amber-800 transition hover:bg-amber-50"
                                                        role="menuitem">
                                                    <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-amber-100 text-amber-700">
                                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16"><path d="M8 1a2 2 0 0 1 2 2v4H6V3a2 2 0 0 1 2-2zm3 6V3a3 3 0 0 0-6 0v4a2 2 0 0 0-2 2v5h12V9a2 2 0 0 0-2-2z"/></svg>
                                                    </span>
                                                    <span class="flex-1 leading-snug">إغلاق الموازنة</span>
                                                </button>
                                            </form>
                                        @else
                                            <a href="{{ route('finance.budgets.show', $budget) }}"
                                               class="erp-menu-item flex items-center gap-3 px-3 py-2.5 text-sm text-gray-800 transition hover:bg-gray-50"
                                               role="menuitem">
                                                <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-sky-100 text-sky-700">
                                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16"><path d="M16 8s-3-5.5-8-5.5S0 8 0 8s3 5.5 8 5.5S16 8 16 8zM1.173 8a13.133 13.133 0 0 1 1.66-2.043C4.12 4.668 5.88 3.5 8 3.5c2.12 0 3.879 1.168 5.168 2.457A13.133 13.133 0 0 1 14.828 8c-.086.13-.17.263-.252.394C12.879 10.668 11.12 11.5 8 11.5c-2.12 0-3.879-1.168-5.168-2.457A13.134 13.134 0 0 1 1.172 8z"/><path d="M8 5.5a2.5 2.5 0 1 0 0 5 2.5 2.5 0 0 0 0-5zM4.5 8a3.5 3.5 0 1 1 7 0 3.5 3.5 0 0 1-7 0z"/></svg>
                                                </span>
                                                <span class="flex-1 text-right font-medium leading-snug">عرض الموازنة</span>
                                            </a>
                                            <div class="mx-2 my-2 border-t border-gray-100"></div>
                                            <form method="POST" action="{{ route('finance.budgets.archive', $budget) }}" class="m-0">
                                                @csrf
                                                <button type="submit"
                                                        class="erp-menu-item flex w-full items-center gap-3 px-3 py-2.5 text-right text-sm font-medium text-indigo-800 transition hover:bg-indigo-50 disabled:cursor-not-allowed disabled:opacity-50"
                                                        role="menuitem"
                                                        {{ $budget->archived_at ? 'disabled' : '' }}>
                                                    <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-indigo-100 text-indigo-700">
                                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16"><path d="M.54 3.87.5 3a2 2 0 0 1 2-2h3.672a2 2 0 0 1 1.414.586l.828.828A2 2 0 0 0 9.828 3h3.982a2 2 0 0 1 1.992 2.181l-.637 7.184a2.25 2.25 0 0 1-2.244 2.077H8.926a.75.75 0 0 1-.565-.378L6.5 11H2.75a.75.75 0 0 1-.648-.336L.54 3.87z"/></svg>
                                                    </span>
                                                    <span class="flex-1 leading-snug">أرشفة الموازنة</span>
                                                </button>
                                            </form>
                                        @endif
                                    </x-erp-actions-dropdown>
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

