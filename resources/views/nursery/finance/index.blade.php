@extends('layouts.nursery')

@section('title', niche_label('finance.summary', 'ملخص المالية'))

@section('content')
@php
    $s = $summary;
@endphp
<div class="w-full space-y-5" dir="rtl">
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-extrabold text-orange-950">{{ niche_label('finance.summary', 'ملخص المالية') }}</h1>
            <p class="text-sm text-orange-800/80 mt-1">
                تحصيلات، مستحقات، مصروفات، وصافي الفترة
                <x-info field="nursery.finance_intro" />
            </p>
        </div>
        <a href="{{ route('nursery.subscriptions.index') }}" class="nursery-btn nursery-btn-soft text-sm">الاشتراكات</a>
    </div>

    <div class="flex flex-wrap gap-2">
        @foreach(['month' => 'هذا الشهر', 'week' => 'هذا الأسبوع', 'day' => 'اليوم', 'year' => 'هذه السنة', 'all' => 'الكل'] as $key => $label)
            <a href="{{ route('nursery.finance.index', ['period' => $key]) }}"
               class="nursery-btn text-sm py-1.5 {{ $period === $key ? 'nursery-btn-primary' : 'nursery-btn-soft' }}">{{ $label }}</a>
        @endforeach
    </div>

    <div class="nursery-stats-row">
        <x-nursery-stat-card title="التحصيلات" :value="number_format($s['collected_amount'], 2)" info="nursery.finance_collected" tone="success" :hint="$s['collected_count'].' اشتراك مدفوع'" spark="bars"
            :percent="$spark['collected']['percent']" :trend="$spark['collected']['trend']"
            :href="route('nursery.subscriptions.index')" link-label="الاشتراكات" />
        <x-nursery-stat-card title="المستحقات" :value="number_format($s['outstanding_amount'], 2)" info="nursery.finance_outstanding" tone="warning" :hint="$s['outstanding_count'].' غير مدفوع نشط'" spark="line"
            :percent="$spark['outstanding']['percent']" :trend="$spark['outstanding']['trend']"
            :href="route('nursery.subscriptions.index')" link-label="المستحقات" />
        <x-nursery-stat-card title="المصروفات" :value="number_format($s['expense_amount'], 2)" info="nursery.finance_expenses" tone="primary" :hint="$s['expense_count'].' حركة'" spark="bars"
            :percent="$spark['expenses']['percent']" :trend="$spark['expenses']['trend']"
            :href="!empty($canOpenExpenses) ? route('finance.expenses.index') : null" :link-label="!empty($canOpenExpenses) ? 'المصروفات' : null" />
    </div>

    <div class="nursery-stats-row">
        <x-nursery-stat-card title="صافي الفترة" :value="number_format($s['net_period'], 2)" info="nursery.finance_net" :tone="$s['net_period'] >= 0 ? 'success' : 'danger'" hint="تحصيلات − مصروفات" spark="ring"
            :percent="$spark['net_period']['percent']" :trend="$spark['net_period']['trend']" />
        <x-nursery-stat-card title="منتهية الاستحقاق" :value="number_format($s['overdue_amount'], 2)" info="nursery.finance_overdue" tone="danger" :hint="$s['overdue_count'].' اشتراك'" spark="line"
            :percent="$spark['overdue']['percent']" :trend="$spark['overdue']['trend']"
            :href="route('nursery.subscriptions.index')" link-label="الاشتراكات" />
        <x-nursery-stat-card title="منتهٍ (حالة)" :value="number_format($s['expired_unpaid_amount'], 2)" info="nursery.finance_expired" tone="warning" :hint="$s['expired_unpaid_count'].' اشتراك'" spark="bars"
            :percent="$spark['expired_unpaid']['percent']" :trend="$spark['expired_unpaid']['trend']"
            :href="route('nursery.subscriptions.index')" link-label="الاشتراكات" />
    </div>

    @if($s['ledger_net_profit'] !== null)
        <div class="nursery-stats-row">
            <x-nursery-stat-card title="صافي دفتر الأرباح" :value="number_format($s['ledger_net_profit'], 2)" info="nursery.finance_ledger_net" :tone="$s['ledger_net_profit'] >= 0 ? 'info' : 'danger'" hint="من قيود المحاسبة" spark="ring"
                :percent="$spark['ledger_net_profit']['percent']" :trend="$spark['ledger_net_profit']['trend']"
                :href="!empty($canOpenProfitLoss) ? route('finance.reports.profit-loss', array_filter(['from_date' => $s['from']?->toDateString(), 'to_date' => $s['to']?->toDateString()])) : null"
                :link-label="!empty($canOpenProfitLoss) ? 'الأرباح والخسائر' : null" />
        </div>
    @endif

    @if($financeModuleOn)
        <section class="nursery-card p-5">
            <h2 class="text-base font-bold text-orange-950 mb-3">التقارير والمصروفات <x-info field="nursery.finance_reports" /></h2>
            <div class="flex flex-wrap gap-2">
                @if($canOpenExpenses)
                    <a href="{{ route('finance.expenses.index') }}" class="nursery-btn nursery-btn-primary text-sm">تسجيل / عرض المصروفات</a>
                @endif
                @if($canOpenProfitLoss)
                    <a href="{{ route('finance.reports.profit-loss', array_filter([
                        'from_date' => $s['from']?->toDateString(),
                        'to_date' => $s['to']?->toDateString(),
                    ])) }}" class="nursery-btn nursery-btn-soft text-sm">تقرير الأرباح والخسائر</a>
                @endif
                <a href="{{ route('nursery.subscriptions.index', ['period' => $period === 'all' ? 'all' : $period]) }}" class="nursery-btn nursery-btn-soft text-sm">قائمة الاشتراكات</a>
            </div>
            <p class="text-xs text-orange-700/70 mt-3">المصروفات والأرباح تُدار عبر المحاسبة الحالية — بدون دفتر موازٍ داخل الحضانة.</p>
        </section>
    @else
        <div class="nursery-card px-4 py-3 text-sm text-amber-900 bg-amber-50">
            وحدة المحاسبة غير مفعّلة — تظهر مبالغ الاشتراكات فقط. تفعيل Finance يفعّل المصروفات وترحيل القيود عند الدفع.
        </div>
    @endif

    <div class="grid gap-5 lg:grid-cols-2">
        <section class="nursery-card nursery-table-card">
            <div class="nursery-table-card__toolbar">
                <div>
                    <h2>المستحقات حسب ولي الأمر <x-info field="nursery.finance_by_guardian" /></h2>
                    <p>تجميع المبالغ غير المدفوعة</p>
                </div>
            </div>
            <div class="overflow-x-auto">
                <table class="nursery-table min-w-[320px]">
                    <thead>
                        <tr>
                            <th>ولي الأمر</th>
                            <th>المبلغ</th>
                            <th class="text-center">عدد</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($s['by_guardian'] as $row)
                            <tr>
                                <td>
                                    <span class="nursery-table-name__title">{{ $row['guardian_name'] }}</span>
                                </td>
                                <td class="tabular-nums text-amber-700 font-semibold">{{ number_format($row['outstanding_amount'], 2) }}</td>
                                <td class="text-center tabular-nums font-semibold">{{ $row['unpaid_count'] }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="!py-10 text-center text-orange-800/70">لا مستحقات حالياً</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        <section class="nursery-card nursery-table-card">
            <div class="nursery-table-card__toolbar">
                <div>
                    <h2>اشتراكات غير مدفوعة قريبة الاستحقاق</h2>
                    <p>أقرب فترات تحتاج متابعة</p>
                </div>
            </div>
            <div class="overflow-x-auto">
                <table class="nursery-table min-w-[360px]">
                    <thead>
                        <tr>
                            <th>الطفل</th>
                            <th>حتى</th>
                            <th>المبلغ</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($s['recent_unpaid'] as $sub)
                            <tr>
                                <td>
                                    <span class="nursery-table-name__title">{{ $sub->child?->name }}</span>
                                </td>
                                <td class="text-xs tabular-nums text-slate-600">{{ $sub->ends_on?->format('Y-m-d') }}</td>
                                <td class="tabular-nums font-semibold text-slate-800">{{ number_format($sub->finalAmount(), 2) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="!py-10 text-center text-orange-800/70">لا اشتراكات غير مدفوعة</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </div>
</div>
@endsection
