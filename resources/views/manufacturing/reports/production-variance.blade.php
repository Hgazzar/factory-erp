@extends('layouts.app')

@section('title', 'تقرير انحرافات الإنتاج - UFUQ ERP')

@section('breadcrumb')
    <a href="{{ route('dashboard') }}" class="text-gray-500 hover:text-blue-600">الرئيسية</a>
    <span class="mx-1 text-gray-400">›</span>
    <a href="{{ route('manufacturing.dashboard') }}" class="text-gray-500 hover:text-blue-600">التصنيع</a>
    <span class="mx-1 text-gray-400">›</span>
    <span class="text-blue-900 font-semibold">تقرير انحرافات الإنتاج</span>
@endsection

@section('content')
<style>
    /* يمنع تمدد الجدول إلى 100% مع min-width (كان يسبب توزيع أعمدة غريباً وعدم تطابق بصري مع رؤوس الأعمدة) */
    .erp-table-wrap.production-variance-table-wrap table {
        width: max-content;
        min-width: 1100px;
        border-collapse: collapse;
    }
    .erp-table-wrap.production-variance-table-wrap th,
    .erp-table-wrap.production-variance-table-wrap td {
        vertical-align: middle;
    }
</style>
<div class="mx-auto flex w-full max-w-full flex-col gap-6" dir="rtl">
    <header class="flex flex-wrap items-start justify-between gap-6 border-b border-gray-100 pb-6">
        <div class="min-w-0 flex-1">
            <h1 class="inline-flex flex-wrap items-center gap-2 text-2xl font-bold text-gray-900">
                تقرير انحرافات الإنتاج
                <x-info field="manufacturing.report_variance_intro" />
            </h1>
            <p class="mt-2 text-sm leading-relaxed text-gray-500">مقارنة الكميات والهدر والتكلفة المعيارية للمواد مع الترحيل الفعلي لكل أمر عمل.</p>
        </div>
        <div class="flex shrink-0 flex-wrap gap-2">
            <a href="{{ route('manufacturing.dashboard') }}" class="inline-flex items-center justify-center gap-2 rounded-lg border border-gray-200 bg-white px-4 py-2.5 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50">لوحة التحكم</a>
            <a href="{{ route('manufacturing.runs.index') }}" class="inline-flex items-center justify-center gap-2 rounded-lg border border-gray-200 bg-white px-4 py-2.5 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50">أوامر العمل</a>
        </div>
    </header>

    <div class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm">
        <h2 class="mb-6 text-sm font-bold text-gray-800 inline-flex items-center gap-2">
            التصفية
            <x-info field="manufacturing.report_variance_filters" />
        </h2>
        <form method="GET" action="{{ route('manufacturing.reports.production-variance') }}" class="flex flex-col gap-4 lg:flex-row lg:flex-wrap lg:items-end lg:gap-x-6 lg:gap-y-4">
            <div class="grid min-w-0 flex-1 grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
                <div class="flex min-w-0 flex-col gap-1.5">
                    <label class="flex items-center gap-1 text-xs font-medium text-gray-600">
                        <span class="min-w-0">من تاريخ</span>
                        <x-info field="manufacturing.report_variance_date_from" />
                    </label>
                    <input type="date" name="date_from" value="{{ request('date_from') }}" dir="ltr" lang="en" class="h-10 w-full rounded-lg border border-gray-200 bg-gray-50 px-3 text-sm focus:border-blue-600 focus:ring-2 focus:ring-blue-600">
                </div>
                <div class="flex min-w-0 flex-col gap-1.5">
                    <label class="flex items-center gap-1 text-xs font-medium text-gray-600">
                        <span class="min-w-0">إلى تاريخ</span>
                        <x-info field="manufacturing.report_variance_date_to" />
                    </label>
                    <input type="date" name="date_to" value="{{ request('date_to') }}" dir="ltr" lang="en" class="h-10 w-full rounded-lg border border-gray-200 bg-gray-50 px-3 text-sm focus:border-blue-600 focus:ring-2 focus:ring-blue-600">
                </div>
                <div class="flex min-w-0 flex-col gap-1.5 sm:col-span-2 xl:col-span-1">
                    <label class="flex items-center gap-1 text-xs font-medium text-gray-600">
                        <span class="min-w-0">المنتج التام</span>
                        <x-info field="manufacturing.report_variance_product" />
                    </label>
                    <x-custom-select
                        name="finished_item_id"
                        id="filter_variance_finished_item"
                        class="w-full"
                        :options="$finishedItemOptions"
                        :selected="request('finished_item_id')"
                        empty-label="كل المنتجات"
                        placeholder="ابحث عن منتج..."
                    />
                </div>
                <div class="flex min-w-0 flex-col gap-1.5 sm:col-span-2 xl:col-span-1">
                    <label class="flex items-center gap-1 text-xs font-medium text-gray-600">
                        <span class="min-w-0">الماكينة</span>
                        <x-info field="manufacturing.report_variance_machine" />
                    </label>
                    <x-custom-select
                        name="machine_id"
                        id="filter_variance_machine"
                        class="w-full"
                        :options="$machineOptions"
                        :selected="request('machine_id')"
                        empty-label="كل الماكينات"
                        placeholder="ابحث عن ماكينة..."
                    />
                </div>
            </div>
            <div class="flex w-full shrink-0 border-t border-gray-100 pt-4 lg:w-auto lg:border-t-0 lg:pt-0">
                <button type="submit" class="h-10 w-full rounded-lg bg-blue-600 px-6 text-sm font-semibold text-white shadow-sm hover:bg-blue-700 lg:w-auto">تطبيق</button>
            </div>
        </form>
    </div>

    <p class="inline-flex flex-wrap items-center gap-2 rounded-lg border border-amber-100 bg-amber-50/90 px-4 py-2 text-xs text-amber-900">
        <span>
            <span class="font-semibold text-amber-950">تنبيه الهدر:</span>
            صفوف <span class="font-semibold text-red-700">باللون الأحمر</span> عندما يتجاوز الهالك الفعلي المتوسط للمتوقع بأكثر من خمس نقاط مئوية.
        </span>
        <x-info field="manufacturing.report_variance_scrap_alert_note" />
    </p>

    <div class="erp-table-wrap production-variance-table-wrap overflow-x-auto rounded-lg shadow-sm">
        <table class="w-max min-w-[1100px] border-collapse text-sm text-right">
            <thead class="border-b border-gray-200 bg-gray-50 text-xs font-semibold text-gray-600">
                <tr>
                    <th class="whitespace-nowrap px-4 py-3 text-right align-middle"><span class="inline-flex max-w-full items-center justify-start gap-1">المرجع <x-info field="manufacturing.report_variance_col_ref" /></span></th>
                    <th class="whitespace-nowrap px-4 py-3 text-right align-middle"><span class="inline-flex max-w-full items-center justify-start gap-1">التاريخ <x-info field="manufacturing.report_variance_col_date" /></span></th>
                    <th class="whitespace-nowrap px-4 py-3 text-right align-middle"><span class="inline-flex max-w-full items-center justify-start gap-1">الحالة <x-info field="manufacturing.report_variance_col_status" /></span></th>
                    <th class="min-w-[11rem] max-w-[14rem] px-4 py-3 text-right align-middle"><span class="inline-flex max-w-full items-center justify-start gap-1">المنتج <x-info field="manufacturing.report_variance_col_product" /></span></th>
                    <th class="whitespace-nowrap px-4 py-3 text-right align-middle"><span class="inline-flex max-w-full items-center justify-start gap-1">الماكينة <x-info field="manufacturing.report_variance_col_machine" /></span></th>
                    <th class="whitespace-nowrap px-4 py-3 text-right align-middle"><span class="inline-flex max-w-full items-center justify-start gap-1">كمية التام (مخطط) <x-info field="manufacturing.report_variance_col_qty_planned" /></span></th>
                    <th class="whitespace-nowrap px-4 py-3 text-right align-middle"><span class="inline-flex max-w-full items-center justify-start gap-1">كمية التام (فعلي) <x-info field="manufacturing.report_variance_col_qty_actual" /></span></th>
                    <th class="whitespace-nowrap px-4 py-3 text-right align-middle"><span class="inline-flex max-w-full items-center justify-start gap-1">صرف مواد (مخطط) <x-info field="manufacturing.report_variance_col_mat_planned" /></span></th>
                    <th class="whitespace-nowrap px-4 py-3 text-right align-middle"><span class="inline-flex max-w-full items-center justify-start gap-1">صرف مواد (فعلي) <x-info field="manufacturing.report_variance_col_mat_actual" /></span></th>
                    <th class="whitespace-nowrap px-4 py-3 text-right align-middle"><span class="inline-flex max-w-full items-center justify-start gap-1">هدر متوقع % <x-info field="manufacturing.report_variance_col_scrap_exp" /></span></th>
                    <th class="whitespace-nowrap px-4 py-3 text-right align-middle"><span class="inline-flex max-w-full items-center justify-start gap-1">هدر فعلي % <x-info field="manufacturing.report_variance_col_scrap_act" /></span></th>
                    <th class="whitespace-nowrap px-4 py-3 text-right align-middle"><span class="inline-flex max-w-full items-center justify-start gap-1">تكلفة معيارية <x-info field="manufacturing.report_variance_col_std_cost" /></span></th>
                    <th class="whitespace-nowrap px-4 py-3 text-right align-middle"><span class="inline-flex max-w-full items-center justify-start gap-1">تكلفة فعلية <x-info field="manufacturing.report_variance_col_act_cost" /></span></th>
                    <th class="whitespace-nowrap px-4 py-3 text-right align-middle"><span class="inline-flex max-w-full items-center justify-start gap-1">الانحراف <x-info field="manufacturing.report_variance_col_variance" /></span></th>
                    <th class="whitespace-nowrap px-4 py-3 text-right align-middle"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($runs as $row)
                    @php
                        /** @var \App\Models\ManufacturingRun $run */
                        $run = $row['run'];
                        $scrapAlert = !empty($row['scrap_alert']);
                    @endphp
                    <tr class="{{ $scrapAlert ? 'bg-red-50/90 hover:bg-red-50' : 'hover:bg-gray-50/80' }}">
                        <td class="px-4 py-3 align-middle font-mono text-xs font-semibold text-gray-900">{{ $run->reference }}</td>
                        <td class="px-4 py-3 align-middle text-gray-700" dir="ltr">{{ $run->production_date?->format('Y-m-d') ?? '—' }}</td>
                        <td class="px-4 py-3 align-middle">
                            @if($run->status === \App\Models\ManufacturingRun::STATUS_POSTED)
                                <span class="inline-flex items-center rounded-full bg-green-100 px-2.5 py-1 text-xs font-medium leading-none text-green-800">مرحّل</span>
                            @else
                                <span class="inline-flex items-center rounded-full bg-amber-100 px-2.5 py-1 text-xs font-medium leading-none text-amber-800">مسودة</span>
                            @endif
                        </td>
                        <td class="max-w-[14rem] px-4 py-3 align-middle text-gray-800"><span class="line-clamp-2">{{ $run->finishedItem?->code }} — {{ $run->finishedItem?->name_ar }}</span></td>
                        <td class="px-4 py-3 align-middle text-gray-600">{{ $run->machine?->code ? $run->machine->code.' — ' : '' }}{{ $run->machine?->name_ar ?? '—' }}</td>
                        <td class="px-4 py-3 align-middle tabular-nums text-gray-800">{{ rtrim(rtrim(number_format($row['qty_planned_fg'], 4, '.', ''), '0'), '.') ?: '0' }}</td>
                        <td class="px-4 py-3 align-middle tabular-nums text-gray-800">
                            @if($row['qty_actual_fg'] !== null)
                                {{ rtrim(rtrim(number_format($row['qty_actual_fg'], 4, '.', ''), '0'), '.') ?: '0' }}
                            @else
                                —
                            @endif
                        </td>
                        <td class="px-4 py-3 align-middle tabular-nums text-gray-700">{{ rtrim(rtrim(number_format($row['planned_material_qty_sum'], 4, '.', ''), '0'), '.') ?: '0' }}</td>
                        <td class="px-4 py-3 align-middle tabular-nums text-gray-700">
                            @if($row['actual_material_qty_sum'] !== null)
                                {{ rtrim(rtrim(number_format($row['actual_material_qty_sum'], 4, '.', ''), '0'), '.') ?: '0' }}
                            @else
                                —
                            @endif
                        </td>
                        <td class="px-4 py-3 align-middle tabular-nums text-gray-700">
                            {{ $row['expected_scrap_weighted_pct'] !== null ? number_format($row['expected_scrap_weighted_pct'], 2).'٪' : '—' }}
                        </td>
                        <td class="px-4 py-3 align-middle tabular-nums text-gray-700">
                            {{ $row['actual_scrap_weighted_pct'] !== null ? number_format($row['actual_scrap_weighted_pct'], 2).'٪' : '—' }}
                        </td>
                        <td class="px-4 py-3 align-middle tabular-nums text-gray-800">
                            @if($row['standard_cost'] !== null)
                                {{ number_format($row['standard_cost'], 2) }}
                            @else
                                —
                            @endif
                        </td>
                        <td class="px-4 py-3 align-middle tabular-nums text-gray-800">
                            @if($row['actual_cost'] !== null)
                                {{ number_format($row['actual_cost'], 2) }}
                            @else
                                —
                            @endif
                        </td>
                        <td class="px-4 py-3 align-middle tabular-nums font-semibold {{ ($row['variance'] ?? 0) > 0.0001 ? 'text-red-700' : (($row['variance'] ?? 0) < -0.0001 ? 'text-emerald-700' : 'text-gray-600') }}">
                            @if($row['variance'] !== null)
                                {{ $row['variance'] > 0 ? '+' : '' }}{{ number_format($row['variance'], 2) }}
                            @else
                                —
                            @endif
                        </td>
                        <td class="whitespace-nowrap px-4 py-3 align-middle">
                            <a href="{{ route('manufacturing.show', $run) }}" class="text-sm font-medium text-blue-600 hover:text-blue-800">عرض</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="15" class="px-4 py-10 text-center text-gray-500">لا توجد أوامر مطابقة للفلاتر.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($runs->hasPages())
        <div class="flex justify-center">
            {{ $runs->links() }}
        </div>
    @endif
</div>
@endsection
