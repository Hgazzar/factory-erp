@extends('layouts.crm')

@section('title', 'لوحة إدارة العملاء - '.config('app.name'))

@section('breadcrumb')
    <a href="{{ route('dashboard') }}" class="text-gray-500 hover:text-indigo-600">الرئيسية</a>
    <span>›</span>
    <a href="{{ route('crm.dashboard') }}" class="text-gray-500 hover:text-indigo-600">إدارة العملاء</a>
    <span>›</span>
    <span class="text-indigo-900 font-semibold truncate">لوحة إدارة العملاء</span>
@endsection

@section('content')
@php
    $topCards = [
        [
            'title' => 'إجمالي العملاء',
            'value' => number_format((int) ($totalCustomersCount ?? 0)),
            'href' => route('crm.customers.index'),
            'hint' => 'crm.total_customers',
            'icon_bg' => 'bg-blue-100',
            'icon_color' => 'text-blue-600',
            'svg' => '<path d="M8 8a3 3 0 1 0 0-6 3 3 0 0 0 0 6m3 1H5a4 4 0 0 0-4 4v1h14v-1a4 4 0 0 0-4-4"/>',
        ],
        [
            'title' => 'الفرص المفتوحة',
            'value' => number_format((int) ($openOpportunitiesCount ?? 0)),
            'href' => route('crm.opportunities.index'),
            'hint' => 'crm.open_opportunities',
            'icon_bg' => 'bg-amber-100',
            'icon_color' => 'text-amber-600',
            'svg' => '<path d="M8 1a7 7 0 1 0 0 14A7 7 0 0 0 8 1m.5 3.5a.5.5 0 0 0-1 0V6H6a.5.5 0 0 0 0 1h1.5v2H6a.5.5 0 0 0 0 1h1.5v1.5a.5.5 0 0 0 1 0V10H10a.5.5 0 0 0 0-1H8.5V7H10a.5.5 0 0 0 0-1H8.5z"/>',
        ],
        [
            'title' => 'حسابات الولاء',
            'value' => number_format((int) ($loyaltyAccountsCount ?? 0)),
            'href' => route('crm.loyalty.accounts.index'),
            'hint' => 'crm.loyalty_accounts',
            'icon_bg' => 'bg-pink-100',
            'icon_color' => 'text-pink-600',
            'svg' => '<path d="m8 2.748-.717-.737C5.6.281 2.514.878 1.4 3.053c-.523 1.023-.641 2.5.314 3.905.92 1.353 2.834 2.777 6.286 5.385 3.452-2.608 5.365-4.032 6.286-5.385.955-1.405.838-2.882.314-3.905-1.114-2.175-4.2-2.772-5.883-1.042z"/>',
        ],
        [
            'title' => 'مواعيد اليوم',
            'value' => number_format((int) ($appointmentsToday ?? 0)),
            'href' => route('crm.appointments.index'),
            'hint' => 'crm.today_appointments',
            'icon_bg' => 'bg-sky-100',
            'icon_color' => 'text-sky-600',
            'svg' => '<path d="M3.5 0a.5.5 0 0 1 .5.5V1h8V.5a.5.5 0 0 1 1 0V1h1A2 2 0 0 1 16 3v11a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2V3a2 2 0 0 1 2-2h1V.5a.5.5 0 0 1 .5-.5M1 6v8a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1V6z"/>',
        ],
        [
            'title' => 'العملاء المحتملين',
            'value' => number_format((int) ($potentialCount ?? 0)),
            'href' => route('crm.customers.index', ['crm_status' => 'potential']),
            'hint' => 'crm.potential_customers',
            'icon_bg' => 'bg-emerald-100',
            'icon_color' => 'text-emerald-600',
            'svg' => '<path d="M12.5 16a3.5 3.5 0 1 0 0-7 3.5 3.5 0 0 0 0 7M8 8a3 3 0 1 0 0-6 3 3 0 0 0 0 6m4.5 0a2.5 2.5 0 1 1 0-5 2.5 2.5 0 0 1 0 5"/>',
        ],
    ];
@endphp
<div class="space-y-6" dir="rtl">
    <div class="flex items-center justify-between gap-4">
        <h1 class="inline-flex items-center gap-2 text-2xl font-bold text-gray-900">
            لوحة إدارة العملاء
            <x-info field="crm.dashboard_intro" />
        </h1>
    </div>

    <section class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-5">
        @foreach($topCards as $card)
            <a href="{{ $card['href'] }}" class="group relative rounded-lg border border-slate-200 bg-white p-4 shadow-sm transition hover:-translate-y-0.5 hover:border-blue-300 hover:shadow">
                <span class="absolute right-3 top-3 inline-flex h-8 w-8 items-center justify-center rounded-full {{ $card['icon_bg'] }} {{ $card['icon_color'] }}">
                    <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 16 16" aria-hidden="true">{!! $card['svg'] !!}</svg>
                </span>
                <div class="mt-2 text-center">
                    <div class="text-3xl font-bold text-gray-900 tabular-nums leading-tight">{{ $card['value'] }}</div>
                    <div class="mt-1 inline-flex items-center gap-1 text-xs font-medium text-gray-500">
                        {{ $card['title'] }}
                        <x-info field="{{ $card['hint'] }}" />
                    </div>
                </div>
            </a>
        @endforeach
    </section>

    <section class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        <div class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm lg:col-span-2">
            <h2 class="mb-4 inline-flex items-center gap-2 text-lg font-bold text-gray-900">
                اتجاهات العملاء
                <x-info field="crm.customer_trends" />
            </h2>
            <p class="mb-3 text-sm text-gray-500">نمو العملاء الجدد خلال آخر 6 أشهر</p>
            <div class="relative h-72" dir="ltr">
                <canvas id="crmCustomersTrendChart" class="max-h-72"></canvas>
            </div>
        </div>

        <div class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
            <h2 class="mb-4 inline-flex items-center gap-2 text-lg font-bold text-gray-900">
                العملاء حسب الحالة
                <x-info field="crm.customers_by_status" />
            </h2>
            <div class="relative h-72" dir="ltr">
                <canvas id="crmCustomersStatusChart" class="max-h-72"></canvas>
            </div>
        </div>
    </section>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    if (typeof Chart === 'undefined') return;

    const trendLabels = @json($trendLabels ?? []);
    const trendSeries = @json($trendSeries ?? []);
    const statusLabels = @json($statusLabels ?? []);
    const statusSeries = @json($statusSeries ?? []);
    const palette = @json($chartPalette ?? []);

    const trendCanvas = document.getElementById('crmCustomersTrendChart');
    if (trendCanvas) {
        new Chart(trendCanvas, {
            type: 'line',
            data: {
                labels: trendLabels,
                datasets: [{
                    label: 'عملاء جدد',
                    data: trendSeries,
                    borderColor: palette.trend_line || '#2563EB',
                    backgroundColor: palette.trend_fill || 'rgba(37, 99, 235, 0.12)',
                    fill: true,
                    tension: 0.35,
                    pointRadius: 3,
                    pointHoverRadius: 4,
                }],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                },
                scales: {
                    y: { beginAtZero: true, ticks: { stepSize: 1 } },
                },
            },
        });
    }

    const statusCanvas = document.getElementById('crmCustomersStatusChart');
    if (statusCanvas) {
        new Chart(statusCanvas, {
            type: 'pie',
            data: {
                labels: statusLabels,
                datasets: [{
                    data: statusSeries,
                    backgroundColor: (palette.status && palette.status.length) ? palette.status : ['#60A5FA', '#34D399', '#9CA3AF'],
                    borderWidth: 0,
                }],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: { font: { family: 'Cairo, sans-serif' } },
                    },
                },
            },
        });
    }
});
</script>
@endpush
