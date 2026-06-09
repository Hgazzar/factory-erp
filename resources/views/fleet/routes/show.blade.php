@extends('layouts.fleet')

@section('title', 'خط سير — '.$route->agent?->name)

@section('content')
<div class="space-y-6" dir="rtl">
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-extrabold text-violet-950">{{ $route->agent?->name ?? 'خط سير' }}</h1>
            <p class="text-sm text-violet-700/80 mt-1">
                {{ $route->route_date->format('Y-m-d') }}
                · {{ $statusLabels[$route->status] ?? $route->status }}
            </p>
        </div>
        <div class="flex flex-wrap gap-2">
            @if(app(\App\Support\FleetAccess::class)->allows(\App\Support\FleetAccess::CAP_MANAGE_ROUTES))
                @if(in_array($route->status, [\App\Models\Fleet\FleetRoute::STATUS_PLANNED, \App\Models\Fleet\FleetRoute::STATUS_IN_PROGRESS], true))
                    @if($route->status === \App\Models\Fleet\FleetRoute::STATUS_PLANNED)
                        <form method="POST" action="{{ route('fleet.routes.start', $route) }}">@csrf
                            <button type="submit" class="fleet-btn fleet-btn-primary text-sm">بدء التنفيذ</button>
                        </form>
                    @endif
                    <form method="POST" action="{{ route('fleet.routes.complete', $route) }}">@csrf
                        <button type="submit" class="fleet-btn fleet-btn-soft text-sm">إغلاق الخط</button>
                    </form>
                    <form method="POST" action="{{ route('fleet.routes.cancel', $route) }}" onsubmit="return confirm('إلغاء خط السير؟')">@csrf
                        <button type="submit" class="fleet-btn fleet-btn-soft text-sm text-red-700">إلغاء</button>
                    </form>
                    <a href="{{ route('fleet.routes.edit', $route) }}" class="fleet-btn fleet-btn-soft text-sm">تعديل</a>
                @endif
            @endif
            <a href="{{ route('fleet.routes.index', ['date' => $route->route_date->toDateString()]) }}" class="fleet-btn fleet-btn-soft text-sm">رجوع</a>
        </div>
    </div>

    @if($route->notes)
        <div class="fleet-card p-4 text-sm text-violet-900">{{ $route->notes }}</div>
    @endif

    <div class="fleet-card overflow-hidden">
        <table class="min-w-full text-sm">
            <thead class="bg-violet-50">
                <tr>
                    <th class="px-4 py-3 text-right font-semibold w-12">#</th>
                    <th class="px-4 py-3 text-right font-semibold">العميل</th>
                    <th class="px-4 py-3 text-right font-semibold">الجوال</th>
                    <th class="px-4 py-3 text-right font-semibold">المدينة</th>
                    <th class="px-4 py-3 text-right font-semibold"><x-info field="fleet.route_stop_status" /> الحالة</th>
                    <th class="px-4 py-3 text-right font-semibold"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                @foreach($route->stops as $stop)
                    <tr>
                        <td class="px-4 py-3 tabular-nums text-violet-600 font-bold">{{ $stop->sort_order }}</td>
                        <td class="px-4 py-3 font-medium">{{ $stop->customer?->name ?? '—' }}</td>
                        <td class="px-4 py-3" dir="ltr">{{ $stop->customer?->phone ?? '—' }}</td>
                        <td class="px-4 py-3">{{ $stop->customer?->city ?? '—' }}</td>
                        <td class="px-4 py-3">
                            <span class="inline-flex rounded-full px-2 py-0.5 text-xs font-semibold
                                @if($stop->status === 'visited') bg-emerald-100 text-emerald-800
                                @elseif($stop->status === 'skipped') bg-gray-100 text-gray-600
                                @else bg-amber-100 text-amber-800 @endif">
                                {{ $stopStatusLabels[$stop->status] ?? $stop->status }}
                            </span>
                        </td>
                        <td class="px-4 py-3">
                            @if(app(\App\Support\FleetAccess::class)->allows(\App\Support\FleetAccess::CAP_MANAGE_ROUTES)
                                && in_array($route->status, [\App\Models\Fleet\FleetRoute::STATUS_PLANNED, \App\Models\Fleet\FleetRoute::STATUS_IN_PROGRESS], true))
                                <div class="flex flex-wrap gap-1">
                                    @if(app(\App\Support\FleetAccess::class)->allows(\App\Support\FleetAccess::CAP_MANAGE_COLLECTIONS))
                                        <a href="{{ route('fleet.collections.create', ['route_stop_id' => $stop->id, 'route_id' => $route->id, 'agent_id' => $route->agent_id, 'customer_id' => $stop->customer_id]) }}"
                                           class="text-xs font-semibold text-violet-700 hover:underline">تحصيل</a>
                                    @endif
                                    @if($stop->status !== 'visited')
                                        <form method="POST" action="{{ route('fleet.route-stops.status', $stop) }}">@csrf @method('PATCH')
                                            <input type="hidden" name="status" value="visited">
                                            <button type="submit" class="text-xs font-semibold text-emerald-700 hover:underline">زُورِ</button>
                                        </form>
                                    @endif
                                    @if($stop->status !== 'skipped')
                                        <form method="POST" action="{{ route('fleet.route-stops.status', $stop) }}">@csrf @method('PATCH')
                                            <input type="hidden" name="status" value="skipped">
                                            <button type="submit" class="text-xs font-semibold text-gray-600 hover:underline">تخطي</button>
                                        </form>
                                    @endif
                                    @if($stop->status !== 'pending')
                                        <form method="POST" action="{{ route('fleet.route-stops.status', $stop) }}">@csrf @method('PATCH')
                                            <input type="hidden" name="status" value="pending">
                                            <button type="submit" class="text-xs font-semibold text-violet-600 hover:underline">إعادة</button>
                                        </form>
                                    @endif
                                </div>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection
