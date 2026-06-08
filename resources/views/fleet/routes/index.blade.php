@extends('layouts.fleet')

@section('title', 'خطوط السير — '.niche_module_label('fleet'))

@section('content')
<div class="space-y-6" dir="rtl">
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-extrabold text-violet-950"><x-info field="fleet.nav_routes" /> خطوط السير</h1>
            <p class="text-sm text-violet-700/80 mt-1">تاريخ: {{ $date }}</p>
        </div>
        @if(app(\App\Support\FleetAccess::class)->allows(\App\Support\FleetAccess::CAP_MANAGE_ROUTES))
            <a href="{{ route('fleet.routes.create', ['route_date' => $date]) }}" class="fleet-btn fleet-btn-primary">خط سير جديد</a>
        @endif
    </div>

    <form method="GET" class="fleet-card p-4 flex flex-wrap gap-3 items-end">
        <div>
            <label class="block text-sm font-semibold mb-1"><x-info field="fleet.route_date" /> التاريخ</label>
            <input type="date" name="date" value="{{ $date }}" class="rounded-lg border-gray-300">
        </div>
        <div class="min-w-[10rem]">
            <label class="block text-sm font-semibold mb-1"><x-info field="fleet.route_agent" /> المندوب</label>
            <x-searchable-select
                name="agent_id"
                :options="collect($agents)->map(fn ($a) => ['value' => (string) $a->id, 'label' => $a->name])->all()"
                :selected="(string) $agentId"
                empty-label="الكل"
                empty-option
            />
        </div>
        <button type="submit" class="fleet-btn fleet-btn-soft">تصفية</button>
    </form>

    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
        @forelse($routes as $route)
            <a href="{{ route('fleet.routes.show', $route) }}" class="fleet-card p-5 block hover:shadow-md transition no-underline text-inherit">
                <div class="flex items-start justify-between gap-2 mb-3">
                    <div>
                        <p class="font-bold text-violet-950">{{ $route->agent?->name ?? '—' }}</p>
                        <p class="text-xs text-violet-700/70">{{ $route->route_date->format('Y-m-d') }}</p>
                    </div>
                    <span class="inline-flex rounded-full px-2 py-0.5 text-xs font-semibold bg-violet-100 text-violet-800">
                        {{ $statusLabels[$route->status] ?? $route->status }}
                    </span>
                </div>
                <p class="text-sm text-violet-800">
                    {{ $route->visited_stops_count }} / {{ $route->stops_count }} زيارة
                </p>
            </a>
        @empty
            <div class="fleet-card p-8 text-center text-gray-500 md:col-span-2 xl:col-span-3">
                لا توجد خطوط سير في هذا اليوم.
            </div>
        @endforelse
    </div>
</div>
@endsection
