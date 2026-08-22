@extends(niche_shell_layout())

@section('title', niche_label('finance.dashboard', 'لوحة المحاسبة').' - '.config('app.name'))

@section('breadcrumb')
    <a href="{{ route('dashboard') }}" class="text-gray-500 hover:text-indigo-600">الرئيسية</a>
    <span>›</span>
    <span class="text-indigo-900 font-semibold">المحاسبة</span>
@endsection

@push('styles')
<style>
    .acc-dashboard-toolbar { background: #fff; border-radius: 0.5rem; padding: 0.75rem 1rem; margin-bottom: 1.25rem; box-shadow: 0 1px 3px rgba(0,0,0,0.06); border: 1px solid #e5e7eb; display: flex; flex-wrap: wrap; align-items: center; gap: 0.5rem; justify-content: flex-end; }
    .acc-dashboard-toolbar .btn-primary-toolbar { background: #2563eb; color: #fff; font-weight: 600; padding: 0.5rem 1rem; border-radius: 0.5rem; text-decoration: none; display: inline-flex; align-items: center; gap: 0.35rem; border: none; }
    .acc-dashboard-toolbar .btn-primary-toolbar:hover { background: #1d4ed8; color: #fff; }
    .acc-dashboard-toolbar .btn-secondary-toolbar { background: #fff; color: #374151; border: 1px solid #d1d5db; padding: 0.5rem 1rem; border-radius: 0.5rem; text-decoration: none; font-size: 0.9rem; }
    .acc-dashboard-toolbar .btn-secondary-toolbar:hover { background: #f9fafb; color: #1f2937; }
    .acc-kpi-card { background: #fff; border-radius: 1rem; padding: 1rem 1.25rem; box-shadow: 0 2px 12px rgba(0,0,0,0.08); border: none; height: 100%; display: flex; align-items: flex-start; gap: 0.75rem; }
    .acc-kpi-icon { width: 48px; height: 48px; border-radius: 0.75rem; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
    .acc-kpi-value { font-size: 1.25rem; font-weight: 700; color: #111827; }
    .acc-kpi-label { font-size: 0.8rem; color: #6b7280; margin-top: 0.25rem; }
    .acc-widget-card { background: #fff; border-radius: 1rem; padding: 1.25rem; box-shadow: 0 2px 8px rgba(0,0,0,0.06); border: 1px solid #e5e7eb; height: 100%; display: flex; flex-direction: column; }
    .acc-widget-title { font-weight: 600; color: #1f2937; font-size: 0.95rem; margin-bottom: 1rem; display: flex; align-items: center; gap: 0.5rem; }
    .acc-quick-actions { display: grid; grid-template-columns: repeat(6, 1fr); gap: 0.75rem; }
    @media (max-width: 992px) { .acc-quick-actions { grid-template-columns: repeat(3, 1fr); } }
    @media (max-width: 576px) { .acc-quick-actions { grid-template-columns: repeat(2, 1fr); } }
    .acc-quick-action-btn { border-radius: 0.5rem; padding: 1rem 0.75rem; display: flex; flex-direction: column; align-items: center; text-align: center; text-decoration: none; font-size: 0.8rem; font-weight: 500; color: #374151; transition: box-shadow 0.2s; border: 1px solid #e5e7eb; }
    .acc-quick-action-btn:hover { box-shadow: 0 4px 12px rgba(0,0,0,0.1); color: #1f2937; }
    .acc-quick-action-btn .qa-icon { width: 40px; height: 40px; margin-bottom: 0.5rem; display: flex; align-items: center; justify-content: center; }
    .acc-bottom-card { background: #fff; border-radius: 0.5rem; box-shadow: 0 1px 3px rgba(0,0,0,0.06); border: 1px solid #e5e7eb; padding: 1rem 1.25rem; text-decoration: none; color: #374151; display: flex; align-items: center; justify-content: space-between; gap: 0.75rem; transition: box-shadow 0.2s; }
    .acc-bottom-card:hover { box-shadow: 0 4px 12px rgba(0,0,0,0.08); color: #1f2937; }
    .acc-bottom-card-title { font-weight: 600; font-size: 0.95rem; color: #1f2937; margin: 0 0 0.2rem 0; }
    .acc-bottom-card-sub { font-size: 0.8rem; color: #6b7280; margin: 0; }
    .acc-section-title { display: flex; align-items: center; gap: 0.5rem; font-weight: 600; font-size: 1rem; color: #1f2937; margin-bottom: 1rem; }
    .chart-summary { font-size: 0.9rem; margin-top: 0.75rem; }
    .chart-summary .text-revenue { color: #059669; font-weight: 600; }
    .chart-summary .text-expense { color: #dc2626; font-weight: 600; }
    .chart-legend { display: flex; flex-wrap: wrap; gap: 1rem; margin-top: 0.75rem; font-size: 0.8rem; color: #6b7280; }
    .chart-legend-item { display: inline-flex; align-items: center; gap: 0.4rem; }
    .chart-legend-dot { width: 10px; height: 10px; border-radius: 999px; }
</style>
@endpush

@section('content')
<div dir="rtl" class="content-wrap">
    {{-- شريط الأدوات: الترتيب من اليمين لليسار = تحديث - القيود اليومية - قيد يومي جديد، ومثبت على اليسار --}}
    <div class="acc-dashboard-toolbar">
        <button type="button" class="btn-secondary-toolbar" onclick="window.location.reload();">تحديث</button>
        <a href="{{ route('finance.journals.index') }}" class="btn-secondary-toolbar">القيود اليومية</a>
        <a href="{{ route('finance.journals.create') }}" class="btn-primary-toolbar">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" viewBox="0 0 16 16"><path d="M8 4a.5.5 0 0 1 .5.5v3h3a.5.5 0 0 1 0 1h-3v3a.5.5 0 0 1-1 0v-3h-3a.5.5 0 0 1 0-1h3v-3A.5.5 0 0 1 8 4z"/></svg>
            قيد يومي جديد
        </a>
    </div>

    <header class="mb-4">
        <nav class="text-sm text-gray-500 mb-2" aria-label="مسار التنقل">
            <a href="{{ route('dashboard') }}" class="text-indigo-600 hover:underline">الرئيسية</a>
            <span class="mx-1">›</span>
            <span>{{ niche_label('finance.dashboard', 'لوحة المحاسبة') }}</span>
        </nav>
        <h1 class="text-xl md:text-2xl font-bold text-gray-900">{{ niche_label('finance.dashboard', 'لوحة المحاسبة') }}</h1>
    </header>

    @php
        $dashExpenseSupplierOpts = collect($expenseSuppliers ?? [])->map(fn ($s) => [
            'value' => (string) $s->id,
            'label' => (string) $s->localized_display_name,
        ])->all();
        $dashExpenseSupplierSelected = isset($expenseSupplierId) && $expenseSupplierId !== '' && $expenseSupplierId !== null
            ? (string) $expenseSupplierId
            : '';
    @endphp
    <section class="mb-6 rounded-lg border border-gray-200 bg-white p-4 shadow-sm">
        <form method="GET" action="{{ route('finance.dashboard') }}" class="flex flex-wrap items-end gap-3">
            <div class="min-w-[200px] flex-1 sm:max-w-xs">
                <label class="mb-1 flex items-center gap-1 text-xs font-medium text-gray-600">
                    تصفية مصروفات الرسم البياني
                    <x-info field="finance.finance_dashboard_expense_filters" />
                </label>
                <x-custom-select
                    name="expense_supplier_id"
                    class="w-full"
                    :options="$dashExpenseSupplierOpts"
                    :selected="$dashExpenseSupplierSelected"
                    empty-label="كل الموردين"
                    placeholder="ابحث عن مورد..."
                />
            </div>
            <div class="w-40">
                <label class="mb-1 block text-xs font-medium text-gray-600">من تاريخ</label>
                <input type="date" name="expense_date_from" value="{{ $expenseDateFrom ?? '' }}" class="h-10 w-full rounded-md border border-gray-200 bg-gray-50 px-3 text-sm text-gray-700 focus:border-blue-500 focus:ring-blue-500">
            </div>
            <div class="w-40">
                <label class="mb-1 block text-xs font-medium text-gray-600">إلى تاريخ</label>
                <input type="date" name="expense_date_to" value="{{ $expenseDateTo ?? '' }}" class="h-10 w-full rounded-md border border-gray-200 bg-gray-50 px-3 text-sm text-gray-700 focus:border-blue-500 focus:ring-blue-500">
            </div>
            <button type="submit" class="h-10 rounded-md bg-blue-600 px-4 text-sm font-semibold text-white hover:bg-blue-700">تطبيق</button>
            @if(!empty($expenseFiltersActive))
                <a href="{{ route('finance.dashboard') }}" class="h-10 inline-flex items-center rounded-md border border-gray-200 bg-white px-4 text-sm font-medium text-gray-700 hover:bg-gray-50">إزالة الفلاتر</a>
            @endif
        </form>
        @if(!empty($expenseFiltersActive))
            <p class="mt-2 text-xs text-amber-800 bg-amber-50 rounded-md px-3 py-2 border border-amber-100">يتم احتساب عمود «المصروفات» في الرسم أدناه وملخص «استخدام الميزانية» من سندات المصروفات المطابقة للفلاتر فقط.</p>
        @endif
    </section>

    {{-- البطاقات العلوية الأربع --}}
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <div class="acc-kpi-card">
            <div class="acc-kpi-icon bg-green-100 text-green-600">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 16 16"><path d="M7.247 11.14 2.451 5.658C1.885 5.013 2.345 4 3.204 4h9.592a1 1 0 0 1 .753 1.659l-4.796 5.48a1 1 0 0 1-1.506 0z"/></svg>
            </div>
            <div>
                <div class="acc-kpi-value">{{ $unsettledCount ?? 0 }}</div>
                <div class="acc-kpi-label">غير مسوى</div>
            </div>
        </div>
        <div class="acc-kpi-card">
            <div class="acc-kpi-icon bg-gray-100 text-gray-600">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 16 16"><path d="M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14zm0 1A8 8 0 1 0 8 0a8 8 0 0 0 0 16z"/><path d="M7 5.5a.5.5 0 0 1 .5-.5h2a.5.5 0 0 1 .5.5v2a.5.5 0 0 1-.5.5h-2a.5.5 0 0 1-.5-.5v-2zm0 4a.5.5 0 0 1 .5-.5h2a.5.5 0 0 1 .5.5v2a.5.5 0 0 1-.5.5h-2a.5.5 0 0 1-.5-.5v-2z"/></svg>
            </div>
            <div>
                <div class="acc-kpi-value">{{ number_format($budgetVsActualPercent ?? 0, 1) }}%</div>
                <div class="acc-kpi-label">الميزانية مقابل الفعلي</div>
            </div>
        </div>
        <div class="acc-kpi-card">
            <div class="acc-kpi-icon bg-purple-100 text-purple-600">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 16 16"><path d="M4 10.781c.148 1.667 1.513 2.85 3.591 3.003V15h1.043v-1.216c2.27-.179 3.678-1.438 3.678-3.3 0-1.59-.947-2.51-2.956-3.028l-.722-.187V3.467c1.122.11 1.879.714 2.07 1.616h1.471c-.166-1.6-1.54-2.748-3.54-2.875V1H7.591v1.233c-1.939.23-3.27 1.472-3.27 3.156 0 1.454.966 2.483 2.661 2.917l.61.162v4.031c-1.149-.17-1.94-.8-2.131-1.718H4z"/></svg>
            </div>
            <div>
                <div class="acc-kpi-value">SAR {{ number_format($payableAgingTotal ?? 0, 2) }}</div>
                <div class="acc-kpi-label"><x-info field="finance.dashboard_ap_ledger" /> ذمم دائنة (2010)</div>
            </div>
        </div>
        <div class="acc-kpi-card">
            <div class="acc-kpi-icon bg-amber-100 text-amber-600">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 16 16"><path d="M8 3.5a.5.5 0 0 0-1 0V9a.5.5 0 0 0 .252.434l3.5 2a.5.5 0 0 0 .496-.868L8 8.71V3.5z"/><path d="M8 16A8 8 0 1 0 8 0a8 8 0 0 0 0 16zm7-8A7 7 0 1 1 1 8a7 7 0 0 1 14 0z"/></svg>
            </div>
            <div>
                <div class="acc-kpi-value">SAR {{ number_format($receivableAgingTotal ?? 0, 2) }}</div>
                <div class="acc-kpi-label"><x-info field="finance.dashboard_ar_ledger" /> ذمم مدينة (1030)</div>
            </div>
        </div>
    </div>

    {{-- استخدام الميزانية + الإيرادات والمصروفات الشهرية --}}
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
        {{-- refresh-key: accounting-charts-20260312 --}}
        {{-- استخدام الميزانية (رسم بياني دائري) --}}
        <div class="acc-widget-card h-full w-full">
            <div class="acc-widget-title">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" viewBox="0 0 16 16"><path d="M8 4.754a3.246 3.246 0 1 0 0 6.492 3.246 3.246 0 0 0 0-6.492zM5.754 8a2.246 2.246 0 1 1 4.492 0 2.246 2.246 0 0 1-4.492 0z"/></svg>
                استخدام الميزانية
            </div>
            @if(($budgetTotal ?? 0) > 0)
                <div class="flex-1 flex flex-col items-center justify-center text-center py-4">
                    <div class="w-40 h-40">
                        <canvas id="budgetUsageChart"></canvas>
                    </div>
                    <p class="mt-3 text-sm text-gray-600">
                        تم استخدام
                        <span class="font-semibold text-red-600">
                            {{ number_format($budgetUsed ?? 0, 2) }} SAR
                        </span>
                        من
                        <span class="font-semibold text-gray-800">
                            {{ number_format($budgetTotal ?? 0, 2) }} SAR
                        </span>
                    </p>
                    <div class="chart-legend mt-2 justify-center">
                        <span class="chart-legend-item">
                            <span class="chart-legend-dot" style="background-color:#ef4444;"></span>
                            المصروفات
                        </span>
                        <span class="chart-legend-item">
                            <span class="chart-legend-dot" style="background-color:#e5e7eb;"></span>
                            المتبقي من الميزانية
                        </span>
                    </div>
                </div>
            @else
                <div class="flex-1 flex flex-col items-center justify-center text-center py-6 text-gray-400">
                    <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" fill="currentColor" viewBox="0 0 16 16" class="mb-2"><path d="M8 4.754a3.246 3.246 0 1 0 0 6.492 3.246 3.246 0 0 0 0-6.492zM5.754 8a2.246 2.246 0 1 1 4.492 0 2.246 2.246 0 0 1-4.492 0z"/></svg>
                    <p class="text-sm text-gray-500">لا توجد بيانات ميزانية مسجلة.</p>
                </div>
            @endif
        </div>

        {{-- الإيرادات والمصروفات الشهرية (آخر 6 أشهر) --}}
        <div class="acc-widget-card h-full w-full">
            <div class="acc-widget-title flex justify-between items-center">
                <span class="flex items-center gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" viewBox="0 0 16 16"><path d="M1 11a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1v3a1 1 0 0 1-1 1H2a1 1 0 0 1-1-1v-3zm5-4a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1v7a1 1 0 0 1-1 1H7a1 1 0 0 1-1-1V7zm5-5a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1v12a1 1 0 0 1-1 1h-2a1 1 0 0 1-1-1V2z"/></svg>
                    {{ niche_label('finance.revenue', 'الإيرادات') }} و{{ niche_label('finance.expense', 'المصروفات') }} الشهرية
                </span>
                <span class="text-xs text-gray-500">آخر ٦ أشهر — من القيود</span>
            </div>
            <div class="chart-summary flex flex-wrap gap-4">
                <span class="text-revenue">
                    إجمالي الإيرادات: {{ number_format(collect($chartRevenue ?? [])->sum(), 2) }} SAR
                </span>
                <span class="text-expense">
                    إجمالي المصروفات (الأعمدة الحمراء): {{ number_format(collect($chartExpenses ?? [])->sum(), 2) }} SAR
                    @if(!empty($expenseFiltersActive))
                        <span class="mr-1 text-xs font-normal text-gray-500">— حسب الفلاتر</span>
                    @endif
                </span>
            </div>
            <div class="flex-1 mt-4">
                @if(collect($chartRevenue ?? [])->sum() + collect($chartExpenses ?? [])->sum() > 0)
                    <div class="h-56">
                        <canvas id="revenueExpensesChart"></canvas>
                    </div>
                    <div class="chart-legend">
                        <span class="chart-legend-item">
                            <span class="chart-legend-dot" style="background-color:#22c55e;"></span>
                            إجمالي الإيرادات
                        </span>
                        <span class="chart-legend-item">
                            <span class="chart-legend-dot" style="background-color:#ef4444;"></span>
                            إجمالي المصروفات
                        </span>
                    </div>
                @else
                    <div class="flex items-center justify-center min-h-[180px] border border-dashed border-gray-200 rounded-lg text-gray-400">
                        <p class="text-sm text-gray-500">لا توجد بيانات للفترة.</p>
                    </div>
                @endif
            </div>
        </div>
    </div>

    {{-- لوحات الملخص: الحسابات البنكية، تقادم الدائنين، تقادم المدينين، آخر القيود --}}
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <div class="acc-widget-card">
            <div class="acc-widget-title">{{ niche_label('finance.cash', 'النقدية') }} والبنك (الأستاذ العام)</div>
            <div class="flex-1 flex flex-col gap-3 py-3 text-sm text-right w-full">
                <div class="flex justify-between items-center gap-2 border-b border-gray-100 pb-2">
                    <span class="text-gray-600"><x-info field="finance.dashboard_cash_ledger" /> صندوق النقدية (1010)</span>
                    <span class="font-semibold text-gray-900 tabular-nums">SAR {{ number_format($cashLedgerBalance ?? 0, 2) }}</span>
                </div>
                <div class="flex justify-between items-center gap-2">
                    <span class="text-gray-600"><x-info field="finance.dashboard_bank_ledger" /> البنك الرئيسي (1020)</span>
                    <span class="font-semibold text-gray-900 tabular-nums">SAR {{ number_format($bankLedgerBalance ?? 0, 2) }}</span>
                </div>
                <a href="{{ route('finance.ledger.index') }}" class="text-sm text-indigo-600 hover:underline mt-1 text-center">دفتر الأستاذ ←</a>
            </div>
        </div>
        <div class="acc-widget-card">
            <div class="acc-widget-title"><x-info field="finance.dashboard_ap_ledger" /> ذمم دائنة (2010)</div>
            <div class="flex-1 flex flex-col items-center justify-center text-center py-4">
                <p class="text-lg font-semibold text-gray-800">SAR {{ number_format($payableAgingTotal ?? 0, 2) }}</p>
                <a href="{{ route('finance.ledger.index') }}" class="text-sm text-indigo-600 hover:underline mt-2">عرض الكل ←</a>
            </div>
        </div>
        <div class="acc-widget-card">
            <div class="acc-widget-title"><x-info field="finance.dashboard_ar_ledger" /> ذمم مدينة (1030)</div>
            <div class="flex-1 flex flex-col items-center justify-center text-center py-4">
                <p class="text-lg font-semibold text-gray-800">SAR {{ number_format($receivableAgingTotal ?? 0, 2) }}</p>
                <a href="{{ route('reports.statement.index') }}" class="text-sm text-indigo-600 hover:underline mt-2">عرض الكل ←</a>
            </div>
        </div>
        <div class="acc-widget-card">
            <div class="acc-widget-title">آخر القيود اليومية</div>
            <div class="flex-1">
                @forelse($latestJournals ?? [] as $entry)
                    <div class="py-2 border-b border-gray-100 last:border-0 flex justify-between items-center text-sm">
                        <span class="text-gray-700">{{ $entry->reference ?: 'قيد #' . $entry->id }}</span>
                        <a href="{{ route('finance.journals.index') }}" class="text-indigo-600 hover:underline">عرض</a>
                    </div>
                @empty
                    <div class="flex flex-col items-center justify-center text-center py-4 text-gray-500">
                        <p class="text-sm">لا توجد قيود</p>
                        <a href="{{ route('finance.journals.create') }}" class="text-sm text-indigo-600 hover:underline mt-2">عرض الكل ←</a>
                    </div>
                @endforelse
            </div>
        </div>
    </div>

    {{-- إجراءات سريعة --}}
    <div class="mb-6">
        <div class="acc-section-title">
            <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" fill="currentColor" viewBox="0 0 16 16" class="text-indigo-500"><path d="M4 10.781c.148 1.667 1.513 2.85 3.591 3.003V15h1.043v-1.216c2.27-.179 3.678-1.438 3.678-3.3 0-1.59-.947-2.51-2.956-3.028l-.722-.187V3.467c1.122.11 1.879.714 2.07 1.616h1.471c-.166-1.6-1.54-2.748-3.54-2.875V1H7.591v1.233c-1.939.23-3.27 1.472-3.27 3.156 0 1.454.966 2.483 2.661 2.917l.61.162v4.031c-1.149-.17-1.94-.8-2.131-1.718H4z"/></svg>
            إجراءات سريعة
        </div>
        <div class="acc-quick-actions">
            <a href="{{ route('finance.journals.create') }}" class="acc-quick-action-btn bg-blue-50 border-blue-200 text-blue-800">
                <span class="qa-icon text-blue-600"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 16 16"><path d="M14 14V4.5L9.5 0H4a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h8a2 2 0 0 0 2-2zM9.5 3A1.5 1.5 0 0 0 11 4.5h2V14a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1V2a1 1 0 0 1 1-1h5.5v2z"/></svg></span>
                قيد يومي جديد
            </a>
            <a href="{{ route('finance.expenses.index') }}" class="acc-quick-action-btn bg-red-50 border-red-200 text-red-800">
                <span class="qa-icon text-red-600"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 16 16"><path d="M4 10.781c.148 1.667 1.513 2.85 3.591 3.003V15h1.043v-1.216c2.27-.179 3.678-1.438 3.678-3.3 0-1.59-.947-2.51-2.956-3.028l-.722-.187V3.467c1.122.11 1.879.714 2.07 1.616h1.471c-.166-1.6-1.54-2.748-3.54-2.875V1H7.591v1.233c-1.939.23-3.27 1.472-3.27 3.156 0 1.454.966 2.483 2.661 2.917l.61.162v4.031c-1.149-.17-1.94-.8-2.131-1.718H4z"/></svg></span>
                مصروف جديد
            </a>
            <a href="{{ route('finance.bank-accounts.index') }}" class="acc-quick-action-btn bg-purple-50 border-purple-200 text-purple-800">
                <span class="qa-icon text-purple-600"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 16 16"><path d="M0 1a1 1 0 0 1 1-1h14a1 1 0 0 1 1 1v1a1 1 0 0 1-1 1v7.5a2.5 2.5 0 0 1-2.5 2.5h-9A2.5 2.5 0 0 1 1 9.5V2a1 1 0 0 1-1-1zm1 3v7.5A1.5 1.5 0 0 0 2.5 13h9a1.5 1.5 0 0 0 1.5-1.5V4H2z"/></svg></span>
                تسوية البنك
            </a>
            <a href="{{ route('finance.reports.profit-loss') }}" class="acc-quick-action-btn bg-green-50 border-green-200 text-green-800">
                <span class="qa-icon text-green-600"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 16 16"><path d="M1 11a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1v3a1 1 0 0 1-1 1H2a1 1 0 0 1-1-1v-3zm5-4a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1v7a1 1 0 0 1-1 1H7a1 1 0 0 1-1-1V7zm5-5a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1v12a1 1 0 0 1-1 1h-2a1 1 0 0 1-1-1V2z"/></svg></span>
                عرض التقارير
            </a>
            <a href="{{ route('finance.budgets.index') }}" class="acc-quick-action-btn bg-amber-50 border-amber-200 text-amber-800">
                <span class="qa-icon text-amber-600"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 16 16"><path d="M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14zm0 1A8 8 0 1 0 8 0a8 8 0 0 0 0 16z"/><path d="M7 5.5a.5.5 0 0 1 .5-.5h2a.5.5 0 0 1 .5.5v2a.5.5 0 0 1-.5.5h-2a.5.5 0 0 1-.5-.5v-2zm0 4a.5.5 0 0 1 .5-.5h2a.5.5 0 0 1 .5.5v2a.5.5 0 0 1-.5.5h-2a.5.5 0 0 1-.5-.5v-2z"/></svg></span>
                الموازنات
            </a>
            <a href="{{ route('finance.tax-rates.index') }}" class="acc-quick-action-btn bg-cyan-50 border-cyan-200 text-cyan-800">
                <span class="qa-icon text-cyan-600"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 16 16"><path d="M8 4.754a3.246 3.246 0 1 0 0 6.492 3.246 3.246 0 0 0 0-6.492zM5.754 8a2.246 2.246 0 1 1 4.492 0 2.246 2.246 0 0 1-4.492 0z"/><path d="M9.796 1.343c-.527-1.79-3.065-1.79-3.592 0l-.094.319a.873.873 0 0 1-1.255.52l-.292-.16c-1.64-.892-3.433.902-2.54 2.541l.159.292a.873.873 0 0 1-.52 1.255l-.319.094c-1.79.527-1.79 3.065 0 3.592l.319.094a.873.873 0 0 1 .52 1.255l-.16.292c-.892 1.64.901 3.434 2.541 2.54l.292-.159a.873.873 0 0 1 1.255.52l.094.319c.527 1.79 3.065 1.79 3.592 0l.094-.319a.873.873 0 0 1 1.255-.52l.292.16c1.64.893 3.434-.902 2.54-2.541l-.159-.292a.873.873 0 0 1 .52-1.255l.319-.094c1.79-.527 1.79-3.065 0-3.592l-.319-.094a.873.873 0 0 1-.52-1.255l.16-.292c.893-1.64-.902-3.433-2.541-2.54l-.292.159a.873.873 0 0 1-1.255-.52l-.094-.319z"/></svg></span>
                ضرائب الدليل
            </a>
        </div>
    </div>

    {{-- أقسام المحاسبة (بطاقات سريعة) --}}
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
        <a href="{{ route('finance.accounts.index') }}" class="acc-bottom-card">
            <div class="flex items-center gap-3">
                <div class="w-11 h-11 rounded-full bg-blue-100 text-blue-600 flex items-center justify-center flex-shrink-0">
                    <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" fill="currentColor" viewBox="0 0 16 16"><path d="M1 2.828c.885-.37 2.154-.769 3.388-.893 1.33-.134 2.458.063 3.112.752v9.746c-.935-.53-2.12-.603-3.213-.493-1.18.12-2.37.461-3.287.811V2.828z"/></svg>
                </div>
                <div>
                    <p class="acc-bottom-card-title">دليل الحسابات</p>
                    <p class="acc-bottom-card-sub">أقسام المحاسبة</p>
                </div>
            </div>
            <span class="text-gray-400">←</span>
        </a>
        <a href="{{ route('finance.journals.index') }}" class="acc-bottom-card">
            <div class="flex items-center gap-3">
                <div class="w-11 h-11 rounded-full bg-indigo-100 text-indigo-600 flex items-center justify-center flex-shrink-0">
                    <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" fill="currentColor" viewBox="0 0 16 16"><path d="M1 2.5A1.5 1.5 0 0 1 2.5 1h3A1.5 1.5 0 0 1 7 2.5v3A1.5 1.5 0 0 1 5.5 7h-3A1.5 1.5 0 0 1 1 5.5v-3z"/></svg>
                </div>
                <div>
                    <p class="acc-bottom-card-title">القيود اليومية</p>
                    <p class="acc-bottom-card-sub">أقسام المحاسبة</p>
                </div>
            </div>
            <span class="text-gray-400">←</span>
        </a>
        <a href="{{ route('finance.bank-accounts.index') }}" class="acc-bottom-card">
            <div class="flex items-center gap-3">
                <div class="w-11 h-11 rounded-full bg-emerald-100 text-emerald-600 flex items-center justify-center flex-shrink-0">
                    <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" fill="currentColor" viewBox="0 0 16 16"><path d="M0 3a2 2 0 0 1 2-2h13.5a.5.5 0 0 1 .5.5v1a.5.5 0 0 1-.5.5H15v2a1 1 0 0 1 1 1v3.5a1.5 1.5 0 0 1-1.5 1.5h-12A2.5 2.5 0 0 1 0 12.5V3z"/></svg>
                </div>
                <div>
                    <p class="acc-bottom-card-title">الحسابات البنكية</p>
                    <p class="acc-bottom-card-sub">أقسام المحاسبة</p>
                </div>
            </div>
            <span class="text-gray-400">←</span>
        </a>
        <a href="{{ route('finance.reports.profit-loss') }}" class="acc-bottom-card">
            <div class="flex items-center gap-3">
                <div class="w-11 h-11 rounded-full bg-amber-100 text-amber-600 flex items-center justify-center flex-shrink-0">
                    <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" fill="currentColor" viewBox="0 0 16 16"><path d="M1 11a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1v3a1 1 0 0 1-1 1H2a1 1 0 0 1-1-1v-3zm5-4a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1v7a1 1 0 0 1-1 1H7a1 1 0 0 1-1-1V7zm5-5a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1v12a1 1 0 0 1-1 1h-2a1 1 0 0 1-1-1V2z"/></svg>
                </div>
                <div>
                    <p class="acc-bottom-card-title">التقارير المالية</p>
                    <p class="acc-bottom-card-sub">أقسام المحاسبة</p>
                </div>
            </div>
            <span class="text-gray-400">←</span>
        </a>
    </div>

    <div class="mt-4 grid grid-cols-1 gap-4 md:grid-cols-2">
        <a href="{{ route('finance.tax-rates.index') }}" class="acc-bottom-card">
            <div class="flex items-center gap-3">
                <div class="flex h-11 w-11 flex-shrink-0 items-center justify-center rounded-full bg-cyan-100 text-cyan-600">
                    <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" fill="currentColor" viewBox="0 0 16 16"><path d="M8 4.754a3.246 3.246 0 1 0 0 6.492 3.246 3.246 0 0 0 0-6.492zM5.754 8a2.246 2.246 0 1 1 4.492 0 2.246 2.246 0 0 1-4.492 0z"/></svg>
                </div>
                <div>
                    <p class="acc-bottom-card-title">إعدادات الضرائب</p>
                    <p class="acc-bottom-card-sub">ربط كل ضريبة بحساب خصوم في الدليل</p>
                </div>
            </div>
            <span class="text-gray-400">←</span>
        </a>
        <a href="{{ route('finance.payment-method-accounts.edit') }}" class="acc-bottom-card">
            <div class="flex items-center gap-3">
                <div class="flex h-11 w-11 flex-shrink-0 items-center justify-center rounded-full bg-teal-100 text-teal-600">
                    <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" fill="currentColor" viewBox="0 0 16 16"><path d="M0 3.5A1.5 1.5 0 0 1 1.5 2h13A1.5 1.5 0 0 1 15.5 3.5v9a1.5 1.5 0 0 1-1.5 1.5h-13A1.5 1.5 0 0 1 0 12.5v-9zM1.5 3a.5.5 0 0 0-.5.5v9a.5.5 0 0 0 .5.5h13a.5.5 0 0 0 .5-.5v-9a.5.5 0 0 0-.5-.5h-13z"/></svg>
                </div>
                <div>
                    <p class="acc-bottom-card-title">وسائل الدفع والدليل</p>
                    <p class="acc-bottom-card-sub">نقد، تحويل، شبكة — أصول نقدية</p>
                </div>
            </div>
            <span class="text-gray-400">←</span>
        </a>
    </div>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const revenueExpensesCanvas = document.getElementById('revenueExpensesChart');
    const budgetUsageCanvas = document.getElementById('budgetUsageChart');

    const chartLabels = @json($chartLabels ?? []);
    const chartRevenue = @json($chartRevenue ?? []);
    const chartExpenses = @json($chartExpenses ?? []);

    if (revenueExpensesCanvas && chartLabels.length > 0) {
        const ctx = revenueExpensesCanvas.getContext('2d');
        // eslint-disable-next-line no-undef
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: chartLabels,
                datasets: [
                    {
                        label: 'الإيرادات',
                        data: chartRevenue,
                        backgroundColor: '#22c55e',
                        borderRadius: 6,
                    },
                    {
                        label: 'المصروفات',
                        data: chartExpenses,
                        backgroundColor: '#ef4444',
                        borderRadius: 6,
                    },
                ],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            label: function (context) {
                                const value = context.parsed.y ?? 0;
                                return `${context.dataset.label}: SAR ${value.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
                            },
                        },
                    },
                },
                interaction: {
                    mode: 'index',
                    intersect: false,
                },
                scales: {
                    x: {
                        grid: { display: false },
                    },
                    y: {
                        ticks: {
                            callback: function (value) {
                                return value.toLocaleString();
                            },
                        },
                    },
                },
            },
        });
    }

    if (budgetUsageCanvas && {{ (int) ($budgetTotal ?? 0) }} > 0) {
        const budgetUsed = {{ (float) ($budgetUsed ?? 0) }};
        const budgetRemaining = {{ (float) ($budgetRemaining ?? 0) }};

        const ctxBudget = budgetUsageCanvas.getContext('2d');
        // eslint-disable-next-line no-undef
        new Chart(ctxBudget, {
            type: 'doughnut',
            data: {
                labels: ['المصروفات', 'المتبقي'],
                datasets: [{
                    data: [budgetUsed, budgetRemaining],
                    backgroundColor: ['#ef4444', '#e5e7eb'],
                    borderWidth: 0,
                }],
            },
            options: {
                cutout: '65%',
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            label: function (context) {
                                const value = context.parsed ?? 0;
                                return `${context.label}: SAR ${value.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
                            },
                        },
                    },
                },
            },
        });
    }
});
</script>
@endpush

@endsection
