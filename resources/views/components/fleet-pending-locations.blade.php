@props([
    'customers' => [],
    'canApprove' => false,
])

<div class="fleet-card p-4">
    <div class="flex items-center justify-between gap-2 mb-3">
        <div class="flex items-center gap-1 flex-wrap">
            <h2 class="text-base font-bold text-violet-950">مواقع بانتظار الاعتماد</h2>
            <x-info field="fleet.geo_pending_locations" />
        </div>
        <span class="text-xs font-bold text-violet-700 bg-violet-50 rounded-full px-2 py-0.5 tabular-nums">{{ count($customers) }}</span>
    </div>

    @if(count($customers) === 0)
        <p class="text-sm text-violet-800/70 py-4 text-center">لا توجد مواقع معلّقة — كل العملاء لديهم مواقع معتمدة.</p>
    @else
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-right">
                <thead>
                    <tr class="text-violet-800/70 border-b border-violet-100">
                        <th class="py-2 font-semibold">العميل</th>
                        <th class="py-2 font-semibold">المدينة</th>
                        <th class="py-2 font-semibold">الإحداثيات المقترحة</th>
                        <th class="py-2 font-semibold">التاريخ</th>
                        @if($canApprove)
                            <th class="py-2 font-semibold">إجراء</th>
                        @endif
                    </tr>
                </thead>
                <tbody>
                    @foreach($customers as $customer)
                        <tr class="border-b border-violet-50 last:border-0">
                            <td class="py-2 font-semibold text-violet-950">{{ $customer->name }}</td>
                            <td class="py-2 text-violet-900">{{ $customer->city ?? '—' }}</td>
                            <td class="py-2 tabular-nums text-xs text-violet-800/80">
                                @if($customer->latitude !== null && $customer->longitude !== null)
                                    {{ number_format((float) $customer->latitude, 5) }}, {{ number_format((float) $customer->longitude, 5) }}
                                @else
                                    —
                                @endif
                            </td>
                            <td class="py-2 text-xs text-violet-800/70 tabular-nums">
                                {{ optional($customer->location_updated_at)->format('Y-m-d') ?? '—' }}
                            </td>
                            @if($canApprove)
                                <td class="py-2">
                                    <form method="POST" action="{{ route('fleet.customers.approve-location', $customer->id) }}">
                                        @csrf
                                        <button type="submit" class="fleet-btn fleet-btn-primary text-xs">اعتماد الموقع</button>
                                    </form>
                                </td>
                            @endif
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>
