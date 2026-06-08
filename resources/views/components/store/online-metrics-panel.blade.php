@props(['panel'])

@php
    $variant = $panel['variant'] ?? 'full';
    $metrics = $panel['metrics'] ?? [];
    $currency = $panel['currency'] ?? 'SAR';
    $links = $panel['links'] ?? [];
    $uid = 'storeMetrics'.substr(md5(json_encode($panel)), 0, 8);
    $storefrontLabel = $panel['storefront_label'] ?? 'المتجر الإلكتروني';
    $ordersLabel = $panel['orders_label'] ?? 'الطلبات';
    $pendingLabel = $panel['metrics_pending_label'] ?? 'بانتظار التحصيل';
    $fmt = fn (float|int|string|null $n): string => number_format((float) ($n ?? 0), 2, '.', ',');
@endphp

@if($variant === 'compact')
<section class="rounded-2xl border border-indigo-100 bg-gradient-to-l from-indigo-50/90 via-white to-violet-50/60 p-5 shadow-sm" dir="rtl">
    <div class="flex flex-wrap items-center justify-between gap-4 mb-4">
        <div>
            <p class="text-xs font-bold uppercase tracking-wider text-indigo-600">Online Store</p>
            <h2 class="text-lg font-bold text-slate-900 inline-flex items-center gap-2">
                {{ $storefrontLabel }}
                <x-info field="store.dashboard_section" />
            </h2>
        </div>
        <div class="flex flex-wrap gap-2">
            @if(! empty($links['orders']))
                <a href="{{ $links['orders'] }}" class="rounded-lg border border-indigo-200 bg-white px-3 py-1.5 text-xs font-semibold text-indigo-700 hover:bg-indigo-50">{{ $ordersLabel }}</a>
            @endif
            @if(! empty($links['settings']))
                <a href="{{ $links['settings'] }}" class="rounded-lg bg-indigo-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-indigo-700">إعدادات المتجر</a>
            @endif
        </div>
    </div>
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3">
        <div class="rounded-xl border border-white/80 bg-white/80 p-3 backdrop-blur-sm">
            <p class="text-[11px] font-semibold text-slate-500"><x-info field="store.metrics_sales_today" /> مبيعات اليوم</p>
            <p class="mt-1 text-xl font-black tabular-nums text-slate-900">{{ $currency }} {{ $fmt($metrics['sales_today'] ?? 0) }}</p>
        </div>
        <div class="rounded-xl border border-white/80 bg-white/80 p-3 backdrop-blur-sm">
            <p class="text-[11px] font-semibold text-slate-500"><x-info field="store.metrics_orders_today" /> طلبات اليوم</p>
            <p class="mt-1 text-xl font-black tabular-nums text-indigo-600">{{ (int) ($metrics['orders_today'] ?? 0) }}</p>
        </div>
        <div class="rounded-xl border border-white/80 bg-white/80 p-3 backdrop-blur-sm">
            <p class="text-[11px] font-semibold text-slate-500"><x-info field="store.metrics_revenue_month" /> إيراد الشهر</p>
            <p class="mt-1 text-xl font-black tabular-nums text-slate-900">{{ $currency }} {{ $fmt($metrics['revenue_month'] ?? 0) }}</p>
        </div>
        <div class="rounded-xl border border-amber-200 bg-amber-50/90 p-3">
            <p class="text-[11px] font-semibold text-amber-800"><x-info field="store.metrics_pending_collection" /> {{ $pendingLabel }}</p>
            <p class="mt-1 text-xl font-black tabular-nums text-amber-900">{{ (int) ($metrics['pending_collection'] ?? 0) }}</p>
        </div>
    </div>
</section>

@elseif($variant === 'embedded')
<div class="space-y-4" dir="rtl">
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
            <div class="text-xs font-medium text-gray-500 mb-1"><x-info field="store.metrics_sales_today" /> مبيعات اليوم</div>
            <div class="text-2xl font-bold text-gray-900 tabular-nums">{{ $currency }} {{ $fmt($metrics['sales_today'] ?? 0) }}</div>
        </div>
        <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
            <div class="text-xs font-medium text-gray-500 mb-1"><x-info field="store.metrics_orders_today" /> طلبات اليوم</div>
            <div class="text-2xl font-bold text-indigo-600 tabular-nums">{{ (int) ($metrics['orders_today'] ?? 0) }}</div>
        </div>
        <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
            <div class="text-xs font-medium text-gray-500 mb-1"><x-info field="store.metrics_revenue_month" /> إيراد الشهر</div>
            <div class="text-2xl font-bold text-gray-900 tabular-nums">{{ $currency }} {{ $fmt($metrics['revenue_month'] ?? 0) }}</div>
        </div>
        <div class="rounded-xl border border-amber-200 bg-amber-50 p-4 shadow-sm">
            <div class="text-xs font-medium text-amber-800 mb-1"><x-info field="store.metrics_pending_collection" /> {{ $pendingLabel }}</div>
            <div class="text-2xl font-bold text-amber-900 tabular-nums">{{ (int) ($metrics['pending_collection'] ?? 0) }}</div>
        </div>
    </div>
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
        <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
            <h2 class="text-sm font-bold text-gray-900 mb-3 inline-flex items-center gap-1"><x-info field="store.chart_top_products" /> أفضل المنتجات (30 يوم)</h2>
            @forelse($metrics['top_products'] ?? [] as $row)
                <div class="flex justify-between text-sm py-2 border-b border-gray-100 last:border-0">
                    <span>{{ $row['name'] }}</span>
                    <span class="text-gray-500 tabular-nums">{{ $row['qty'] }} — {{ $fmt($row['revenue']) }}</span>
                </div>
            @empty
                <p class="text-sm text-gray-500">لا توجد مبيعات أونلاين بعد.</p>
            @endforelse
        </div>
        <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
            <h2 class="text-sm font-bold text-gray-900 mb-3 inline-flex items-center gap-1"><x-info field="store.table_recent_orders" /> آخر الطلبات</h2>
            @forelse($metrics['recent_orders'] ?? [] as $order)
                <div class="flex justify-between text-sm py-2 border-b border-gray-100 last:border-0">
                    <span>{{ $order['invoice_number'] }} — {{ $order['customer'] ?? 'زائر' }}</span>
                    <span class="text-gray-500 tabular-nums">{{ $fmt($order['total']) }}</span>
                </div>
            @empty
                <p class="text-sm text-gray-500">لا طلبات حديثة.</p>
            @endforelse
        </div>
    </div>
</div>

@else
<section class="space-y-6" dir="rtl" data-store-metrics-panel="{{ $uid }}">
    <div class="relative overflow-hidden rounded-2xl border border-indigo-100 bg-gradient-to-l from-indigo-600 via-indigo-500 to-violet-600 p-6 text-white shadow-lg">
        <div class="absolute -left-8 -top-8 h-40 w-40 rounded-full bg-white/10 blur-2xl" aria-hidden="true"></div>
        <div class="absolute -bottom-10 -right-6 h-48 w-48 rounded-full bg-violet-400/20 blur-3xl" aria-hidden="true"></div>
        <div class="relative flex flex-wrap items-end justify-between gap-4">
            <div>
                <p class="text-xs font-bold uppercase tracking-widest text-indigo-100/90">Online Store Channel</p>
                <h2 class="mt-1 text-2xl font-black tracking-tight inline-flex items-center gap-2">
                    {{ $storefrontLabel }}
                    <x-info field="store.dashboard_section" />
                </h2>
                <p class="mt-1 text-sm text-indigo-100/90"><x-info field="store.dashboard_intro" /></p>
            </div>
            <div class="flex flex-wrap gap-2">
                @if(! empty($links['storefront']))
                    <a href="{{ $links['storefront'] }}" target="_blank" rel="noopener"
                       class="rounded-xl border border-white/30 bg-white/10 px-4 py-2 text-sm font-semibold backdrop-blur hover:bg-white/20">
                        زيارة المتجر
                    </a>
                @endif
                @if(! empty($links['orders']))
                    <a href="{{ $links['orders'] }}"
                       class="rounded-xl bg-white px-4 py-2 text-sm font-bold text-indigo-700 shadow-sm hover:bg-indigo-50">
                        {{ $ordersLabel }}
                    </a>
                @endif
                @if(! empty($links['settings']))
                    <a href="{{ $links['settings'] }}"
                       class="rounded-xl border border-white/40 px-4 py-2 text-sm font-semibold text-white hover:bg-white/10">
                        إعدادات
                    </a>
                @endif
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
        @php
            $kpiCards = [
                ['hint' => 'store.metrics_sales_today', 'label' => 'مبيعات اليوم', 'value' => $currency.' '.$fmt($metrics['sales_today'] ?? 0), 'tone' => 'text-slate-900', 'wrap' => 'bg-emerald-50 text-emerald-600'],
                ['hint' => 'store.metrics_orders_today', 'label' => 'طلبات اليوم', 'value' => (string) (int) ($metrics['orders_today'] ?? 0), 'tone' => 'text-indigo-600', 'wrap' => 'bg-indigo-50 text-indigo-600'],
                ['hint' => 'store.metrics_revenue_month', 'label' => 'إيراد الشهر', 'value' => $currency.' '.$fmt($metrics['revenue_month'] ?? 0), 'tone' => 'text-slate-900', 'wrap' => 'bg-violet-50 text-violet-600'],
                ['hint' => 'store.metrics_pending_collection', 'label' => $pendingLabel, 'value' => (string) (int) ($metrics['pending_collection'] ?? 0), 'tone' => 'text-amber-800', 'wrap' => 'bg-amber-50 text-amber-600'],
            ];
        @endphp
        @foreach($kpiCards as $card)
            <div class="rounded-xl border border-slate-200/80 bg-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <p class="text-xs font-semibold text-slate-500 inline-flex items-center gap-1"><x-info field="{{ $card['hint'] }}" /> {{ $card['label'] }}</p>
                        <p class="mt-2 text-2xl font-black tabular-nums {{ $card['tone'] }}">{{ $card['value'] }}</p>
                    </div>
                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl {{ $card['wrap'] }}">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" viewBox="0 0 16 16" aria-hidden="true"><path d="M0 4a2 2 0 0 1 2-2h12a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2V4zm2-1a1 1 0 0 0-1 1v1h14V4a1 1 0 0 0-1-1H2zm13 4H1v5a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1V7z"/></svg>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
        <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-100 px-5 py-4">
                <h3 class="text-base font-bold text-slate-900 inline-flex items-center gap-2"><x-info field="store.chart_daily_sales" /> مبيعات آخر 7 أيام</h3>
            </div>
            <div class="p-5">
                <canvas id="{{ $uid }}Daily" height="200" aria-label="مبيعات يومية"></canvas>
            </div>
        </div>
        <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-100 px-5 py-4">
                <h3 class="text-base font-bold text-slate-900 inline-flex items-center gap-2"><x-info field="store.chart_payment_methods" /> طرق الدفع (الشهر)</h3>
            </div>
            <div class="p-5">
                <canvas id="{{ $uid }}Pay" height="200" aria-label="طرق الدفع"></canvas>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 gap-6 xl:grid-cols-3">
        <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm xl:col-span-1">
            <div class="border-b border-slate-100 px-5 py-4">
                <h3 class="text-base font-bold text-slate-900 inline-flex items-center gap-2"><x-info field="store.chart_top_products" /> أفضل المنتجات</h3>
            </div>
            <div class="divide-y divide-slate-50 px-5">
                @forelse($metrics['top_products'] ?? [] as $row)
                    <div class="flex items-center justify-between gap-3 py-3 text-sm">
                        <span class="font-medium text-slate-800">{{ $row['name'] }}</span>
                        <span class="shrink-0 tabular-nums text-slate-500">{{ $row['qty'] }} · {{ $fmt($row['revenue']) }}</span>
                    </div>
                @empty
                    <p class="py-8 text-center text-sm text-slate-500">لا مبيعات أونلاين بعد.</p>
                @endforelse
            </div>
        </div>
        <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm xl:col-span-1">
            <div class="border-b border-slate-100 px-5 py-4 flex items-center justify-between gap-2">
                <h3 class="text-base font-bold text-slate-900 inline-flex items-center gap-2"><x-info field="store.table_recent_orders" /> آخر الطلبات</h3>
                @if(! empty($links['orders']))
                    <a href="{{ $links['orders'] }}" class="text-xs font-semibold text-indigo-600 hover:text-indigo-800">عرض الكل</a>
                @endif
            </div>
            <div class="divide-y divide-slate-50 px-5">
                @forelse($metrics['recent_orders'] ?? [] as $order)
                    <div class="flex items-center justify-between gap-3 py-3 text-sm">
                        <div class="min-w-0">
                            <p class="font-medium text-slate-800 truncate">{{ $order['invoice_number'] }}</p>
                            <p class="text-xs text-slate-500">{{ $order['customer'] ?? 'زائر' }} · {{ $order['created_at'] }}</p>
                        </div>
                        <span class="shrink-0 font-semibold tabular-nums text-slate-700">{{ $fmt($order['total']) }}</span>
                    </div>
                @empty
                    <p class="py-8 text-center text-sm text-slate-500">لا طلبات حديثة.</p>
                @endforelse
            </div>
        </div>
        <div class="overflow-hidden rounded-xl border border-amber-200/80 bg-amber-50/30 shadow-sm xl:col-span-1">
            <div class="border-b border-amber-100 px-5 py-4">
                <h3 class="text-base font-bold text-amber-950 inline-flex items-center gap-2"><x-info field="store.table_low_stock" /> تنبيهات المخزون</h3>
            </div>
            <div class="divide-y divide-amber-100/60 px-5">
                @forelse($metrics['low_stock'] ?? [] as $row)
                    <div class="flex items-center justify-between gap-3 py-3 text-sm">
                        <span class="font-medium text-amber-950">{{ $row['name'] }}</span>
                        <span class="rounded-full bg-amber-200/80 px-2.5 py-0.5 text-xs font-bold tabular-nums text-amber-900">{{ $row['qty'] }} / {{ $row['alert'] }}</span>
                    </div>
                @empty
                    <p class="py-8 text-center text-sm text-amber-800/70">لا منتجات منشورة بمخزون منخفض.</p>
                @endforelse
            </div>
        </div>
    </div>
</section>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    if (!window.Chart) return;
    const currency = @json($currency);
    const daily = @json($metrics['daily_sales'] ?? []);
    const payments = @json($metrics['payment_methods'] ?? []);
    const uid = @json($uid);

    Chart.defaults.font.family = 'inherit';
    Chart.defaults.color = '#64748b';

    const ctxD = document.getElementById(uid + 'Daily');
    if (ctxD && daily.length) {
        new Chart(ctxD.getContext('2d'), {
            type: 'bar',
            data: {
                labels: daily.map(function (d) { return d.date.slice(5); }),
                datasets: [{
                    label: currency,
                    data: daily.map(function (d) { return d.total; }),
                    backgroundColor: 'rgba(99, 102, 241, 0.55)',
                    borderRadius: 6,
                    borderSkipped: false,
                }]
            },
            options: {
                responsive: true,
                plugins: { legend: { display: false } },
                scales: { y: { beginAtZero: true } }
            }
        });
    }

    const ctxP = document.getElementById(uid + 'Pay');
    if (ctxP && payments.length) {
        new Chart(ctxP.getContext('2d'), {
            type: 'doughnut',
            data: {
                labels: payments.map(function (p) { return p.label; }),
                datasets: [{
                    data: payments.map(function (p) { return p.total; }),
                    backgroundColor: ['#4f46e5', '#059669', '#d97706', '#dc2626', '#7c3aed', '#0891b2'],
                }]
            },
            options: { responsive: true }
        });
    }
});
</script>
@endpush
@endif
