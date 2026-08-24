@props([
    'visits' => [],
])

<div class="fleet-card p-4">
    <div class="flex items-center justify-between gap-2 mb-3">
        <div class="flex items-center gap-1 flex-wrap">
            <h2 class="text-base font-bold text-violet-950">زيارات خارج النطاق المسجّل</h2>
            <x-info field="fleet.geo_exceptions_queue" />
        </div>
        <span class="text-xs font-bold text-red-700 bg-red-50 rounded-full px-2 py-0.5 tabular-nums">{{ count($visits) }}</span>
    </div>

    @if(count($visits) === 0)
        <p class="text-sm text-violet-800/70 py-4 text-center">لا توجد استثناءات — كل الزيارات داخل النطاق المعتمد.</p>
    @else
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-right">
                <thead>
                    <tr class="text-violet-800/70 border-b border-violet-100">
                        <th class="py-2 font-semibold">المندوب</th>
                        <th class="py-2 font-semibold">العميل</th>
                        <th class="py-2 font-semibold">المسافة</th>
                        <th class="py-2 font-semibold">الحالة</th>
                        <th class="py-2 font-semibold">الوقت</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($visits as $visit)
                        <tr class="border-b border-violet-50 last:border-0">
                            <td class="py-2 font-semibold text-violet-950">{{ $visit->agent?->name ?? '—' }}</td>
                            <td class="py-2 text-violet-900">{{ $visit->customer?->name ?? '—' }}</td>
                            <td class="py-2 tabular-nums text-violet-900">
                                @if($visit->distance_meters !== null)
                                    {{ number_format($visit->distance_meters) }} م
                                @else
                                    —
                                @endif
                            </td>
                            <td class="py-2">
                                <div class="flex flex-wrap gap-1">
                                    @if($visit->geofence_status === \App\Models\Fleet\FleetVisit::GEOFENCE_OUTSIDE)
                                        <span class="text-xs font-bold text-amber-800 bg-amber-50 rounded-full px-2 py-0.5">خارج النطاق</span>
                                    @endif
                                    @if($visit->is_mocked)
                                        <span class="text-xs font-bold text-red-700 bg-red-50 rounded-full px-2 py-0.5">GPS مزيّف</span>
                                    @endif
                                </div>
                                @if($visit->visit_reason)
                                    <span class="block text-xs text-violet-800/70 mt-1">{{ $visit->visit_reason }}</span>
                                @endif
                            </td>
                            <td class="py-2 text-xs text-violet-800/70 tabular-nums">
                                {{ optional($visit->visited_at)->format('Y-m-d H:i') ?? '—' }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>
