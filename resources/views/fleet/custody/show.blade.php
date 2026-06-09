@extends('layouts.fleet')

@section('title', $issue->issue_number.' — عهدة')

@section('content')
<div class="space-y-6" dir="rtl">
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-extrabold text-violet-950">{{ $issue->issue_number }}</h1>
            <p class="text-sm text-violet-700/80 mt-1">
                {{ $issue->agent?->name }} · {{ $issue->issued_on->format('Y-m-d') }}
                · {{ $statusLabels[$issue->status] ?? $issue->status }}
            </p>
        </div>
        <div class="flex flex-wrap gap-2">
            @if(app(\App\Support\FleetAccess::class)->allows(\App\Support\FleetAccess::CAP_MANAGE_CUSTODY))
                @if($issue->isDraft())
                    <form method="POST" action="{{ route('fleet.custody.confirm', $issue) }}">@csrf
                        <button type="submit" class="fleet-btn fleet-btn-primary text-sm">تأكيد الصرف</button>
                    </form>
                @endif
                @if($issue->status !== \App\Models\Fleet\FleetCustodyIssue::STATUS_VOID)
                    <form method="POST" action="{{ route('fleet.custody.void', $issue) }}" onsubmit="return confirm('إلغاء سند العهدة؟')">@csrf
                        <button type="submit" class="fleet-btn fleet-btn-soft text-sm text-red-700">إلغاء السند</button>
                    </form>
                @endif
            @endif
            <a href="{{ route('fleet.custody.index') }}" class="fleet-btn fleet-btn-soft text-sm">رجوع</a>
        </div>
    </div>

    @if($errors->has('custody'))
        <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-900">{{ $errors->first('custody') }}</div>
    @endif

    @if($issue->notes)
        <div class="fleet-card p-4 text-sm">{{ $issue->notes }}</div>
    @endif

    <div class="fleet-card overflow-hidden">
        <table class="min-w-full text-sm">
            <thead class="bg-violet-50">
                <tr>
                    <th class="px-4 py-3 text-right font-semibold"><x-info field="fleet.custody_product" /> الصنف</th>
                    <th class="px-4 py-3 text-right font-semibold">SKU</th>
                    <th class="px-4 py-3 text-right font-semibold"><x-info field="fleet.custody_quantity" /> الكمية</th>
                    <th class="px-4 py-3 text-right font-semibold">سعر الوحدة</th>
                    <th class="px-4 py-3 text-right font-semibold">القيمة</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                @foreach($issue->lines as $line)
                    <tr>
                        <td class="px-4 py-3 font-medium">{{ $line->product?->name ?? '—' }}</td>
                        <td class="px-4 py-3 font-mono text-xs">{{ $line->product?->sku ?? '—' }}</td>
                        <td class="px-4 py-3 tabular-nums">{{ number_format($line->quantity, 2) }}</td>
                        <td class="px-4 py-3 tabular-nums">{{ erp_money($line->unit_price) }}</td>
                        <td class="px-4 py-3 tabular-nums">{{ erp_money($line->quantity * $line->unit_price) }}</td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot class="bg-violet-50/50">
                <tr>
                    <td colspan="2" class="px-4 py-3 font-semibold">الإجمالي</td>
                    <td class="px-4 py-3 tabular-nums font-semibold">{{ number_format($totalQty, 2) }}</td>
                    <td class="px-4 py-3"></td>
                    <td class="px-4 py-3 tabular-nums font-semibold">{{ erp_money($totalValue) }}</td>
                </tr>
            </tfoot>
        </table>
    </div>

    @if($issue->isIssued())
        <p class="text-sm text-emerald-800">
            العهدة مؤكدة — <a href="{{ route('fleet.custody.balances.agent', $issue->agent_id) }}" class="font-semibold underline">عرض رصيد {{ $issue->agent?->name }}</a>
        </p>
    @endif
</div>
@endsection
