@extends('layouts.fleet')

@section('title', 'لوحة التحكم — '.niche_module_label('fleet'))

@section('content')
<div class="w-full space-y-6" dir="rtl">
    <div>
        <h1 class="text-2xl font-extrabold text-violet-950">لوحة التحكم</h1>
        <p class="mt-1 text-sm text-violet-800/80">
            {{ niche_module_label('fleet') }} — ملخص العمليات الميدانية
            <x-info field="fleet.dashboard_intro" />
        </p>
    </div>

    <section>
        <h2 class="text-base font-bold text-violet-950 mb-3">أرقام المؤسسة</h2>
        <div class="grid gap-3 grid-cols-2 sm:grid-cols-3 lg:grid-cols-5">
            <div class="fleet-card p-4 text-center">
                <div class="flex items-center justify-center gap-1 flex-wrap mb-2">
                    <span class="text-sm font-bold text-violet-950">مناديب نشطون</span>
                    <x-info field="fleet.stat_agents_active" />
                </div>
                <p class="text-2xl font-extrabold text-violet-600 tabular-nums">{{ $stats['agents_active'] }}</p>
            </div>
            <div class="fleet-card p-4 text-center">
                <div class="flex items-center justify-center gap-1 flex-wrap mb-2">
                    <span class="text-sm font-bold text-violet-950">إجمالي المناديب</span>
                    <x-info field="fleet.stat_agents_total" />
                </div>
                <p class="text-2xl font-extrabold text-violet-600 tabular-nums">{{ $stats['agents_total'] }}</p>
            </div>
            <div class="fleet-card p-4 text-center">
                <div class="flex items-center justify-center gap-1 flex-wrap mb-2">
                    <span class="text-sm font-bold text-violet-950">عملاء نشطون</span>
                    <x-info field="fleet.stat_customers_active" />
                </div>
                <p class="text-2xl font-extrabold text-violet-600 tabular-nums">{{ $stats['customers_active'] }}</p>
            </div>
            <div class="fleet-card p-4 text-center">
                <div class="flex items-center justify-center gap-1 flex-wrap mb-2">
                    <span class="text-sm font-bold text-violet-950">أصناف نشطة</span>
                    <x-info field="fleet.stat_products_active" />
                </div>
                <p class="text-2xl font-extrabold text-violet-600 tabular-nums">{{ $stats['products_active'] }}</p>
            </div>
            <div class="fleet-card p-4 text-center opacity-75">
                <div class="flex items-center justify-center gap-1 flex-wrap mb-2">
                    <span class="text-sm font-bold text-violet-950">خطوط السير اليوم</span>
                    <x-info field="fleet.stat_routes_today" />
                </div>
                <p class="text-2xl font-extrabold text-violet-400 tabular-nums">{{ $stats['routes_today'] }}</p>
                <p class="text-xs text-violet-600 mt-1">قريباً — F2</p>
            </div>
        </div>
    </section>

    <section class="flex flex-wrap gap-2">
        <a href="{{ route('fleet.agents.index') }}" class="fleet-btn fleet-btn-primary text-sm">إدارة المناديب</a>
        <a href="{{ route('fleet.customers.index') }}" class="fleet-btn fleet-btn-soft text-sm">عملاء الميدان</a>
        <a href="{{ route('fleet.products.index') }}" class="fleet-btn fleet-btn-soft text-sm">الكتalog الخفيف</a>
    </section>
</div>
@endsection
