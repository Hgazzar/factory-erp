@extends('layouts.fleet')

@section('title', 'مرتجعات العهدة — '.niche_module_label('fleet'))

@section('content')
<div class="space-y-6" dir="rtl">
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-extrabold text-violet-950"><x-info field="fleet.nav_custody_returns" /> مرتجعات العهدة</h1>
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('fleet.custody.index') }}" class="fleet-btn fleet-btn-soft">سندات الصرف</a>
            @if(app(\App\Support\FleetAccess::class)->allows(\App\Support\FleetAccess::CAP_MANAGE_CUSTODY))
                <a href="{{ route('fleet.custody.returns.create') }}" class="fleet-btn fleet-btn-primary">مرتجع جديد</a>
            @endif
        </div>
    </div>

    <form method="GET" class="fleet-card p-4 flex flex-wrap gap-3 items-end">
        <div class="min-w-[10rem]">
            <label class="block text-sm font-semibold mb-1"><x-info field="fleet.custody_agent" /> المندوب</label>
            <x-searchable-select
                name="agent_id"
                :options="collect($agents)->map(fn ($a) => ['value' => (string) $a->id, 'label' => $a->name])->all()"
                :selected="(string) $agentId"
                empty-label="الكل"
                empty-option
            />
        </div>
        <div class="min-w-[10rem]">
            <label class="block text-sm font-semibold mb-1"><x-info field="fleet.custody_return_status" /> الحالة</label>
            <x-searchable-select
                name="status"
                :options="collect($statusLabels)->map(fn ($label, $value) => ['value' => $value, 'label' => $label])->values()->all()"
                :selected="$status"
                empty-label="الكل"
                empty-option
                :searchable="false"
            />
        </div>
        <button type="submit" class="fleet-btn fleet-btn-soft">تصفية</button>
    </form>

    <div class="fleet-card overflow-hidden">
        <table class="min-w-full text-sm">
            <thead class="bg-violet-50">
                <tr>
                    <th class="px-4 py-3 text-right font-semibold"><x-info field="fleet.custody_return_number" /> الرقم</th>
                    <th class="px-4 py-3 text-right font-semibold"><x-info field="fleet.custody_agent" /> المندوب</th>
                    <th class="px-4 py-3 text-right font-semibold"><x-info field="fleet.custody_returned_on" /> التاريخ</th>
                    <th class="px-4 py-3 text-right font-semibold">الأصناف</th>
                    <th class="px-4 py-3 text-right font-semibold"><x-info field="fleet.custody_return_status" /> الحالة</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                @forelse($returns as $item)
                    <tr class="hover:bg-violet-50/40 cursor-pointer" onclick="window.location='{{ route('fleet.custody.returns.show', $item) }}'">
                        <td class="px-4 py-3 font-mono text-xs">{{ $item->return_number }}</td>
                        <td class="px-4 py-3 font-medium">{{ $item->agent?->name ?? '—' }}</td>
                        <td class="px-4 py-3">{{ $item->returned_on->format('Y-m-d') }}</td>
                        <td class="px-4 py-3 tabular-nums">{{ $item->lines_count }}</td>
                        <td class="px-4 py-3">
                            <span class="inline-flex rounded-full px-2 py-0.5 text-xs font-semibold
                                @if($item->status === 'confirmed') bg-emerald-100 text-emerald-800
                                @elseif($item->status === 'void') bg-gray-100 text-gray-600
                                @else bg-amber-100 text-amber-800 @endif">
                                {{ $statusLabels[$item->status] ?? $item->status }}
                            </span>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="px-4 py-8 text-center text-gray-500">لا توجد مرتجعات عهدة.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{ $returns->links() }}
</div>
@endsection
