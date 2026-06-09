@extends('layouts.fleet')

@section('title', niche_label('modules.inventory', 'العهدة').' — '.niche_module_label('fleet'))

@section('content')
<div class="space-y-6" dir="rtl">
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-extrabold text-violet-950"><x-info field="fleet.nav_custody" /> سندات العهدة</h1>
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('fleet.custody.balances') }}" class="fleet-btn fleet-btn-soft">أرصدة المناديب</a>
            @if(app(\App\Support\FleetAccess::class)->allows(\App\Support\FleetAccess::CAP_MANAGE_CUSTODY))
                <a href="{{ route('fleet.custody.create') }}" class="fleet-btn fleet-btn-primary">صرف عهدة جديدة</a>
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
            <label class="block text-sm font-semibold mb-1"><x-info field="fleet.custody_status" /> الحالة</label>
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
                    <th class="px-4 py-3 text-right font-semibold"><x-info field="fleet.custody_issue_number" /> الرقم</th>
                    <th class="px-4 py-3 text-right font-semibold"><x-info field="fleet.custody_agent" /> المندوب</th>
                    <th class="px-4 py-3 text-right font-semibold"><x-info field="fleet.custody_issued_on" /> التاريخ</th>
                    <th class="px-4 py-3 text-right font-semibold">الأصناف</th>
                    <th class="px-4 py-3 text-right font-semibold"><x-info field="fleet.custody_status" /> الحالة</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                @forelse($issues as $issue)
                    <tr class="hover:bg-violet-50/40 cursor-pointer" onclick="window.location='{{ route('fleet.custody.show', $issue) }}'">
                        <td class="px-4 py-3 font-mono text-xs">{{ $issue->issue_number }}</td>
                        <td class="px-4 py-3 font-medium">{{ $issue->agent?->name ?? '—' }}</td>
                        <td class="px-4 py-3">{{ $issue->issued_on->format('Y-m-d') }}</td>
                        <td class="px-4 py-3 tabular-nums">{{ $issue->lines_count }}</td>
                        <td class="px-4 py-3">
                            <span class="inline-flex rounded-full px-2 py-0.5 text-xs font-semibold
                                @if($issue->status === 'issued') bg-emerald-100 text-emerald-800
                                @elseif($issue->status === 'void') bg-gray-100 text-gray-600
                                @else bg-amber-100 text-amber-800 @endif">
                                {{ $statusLabels[$issue->status] ?? $issue->status }}
                            </span>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="px-4 py-8 text-center text-gray-500">لا توجد سندات عهدة.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{ $issues->links() }}
</div>
@endsection
