@extends('layouts.fleet')

@section('title', 'عهدة '.$agent->name)

@section('content')
<div class="space-y-6" dir="rtl">
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-extrabold text-violet-950">عهدة {{ $agent->name }}</h1>
            @if($agent->phone)
                <p class="text-sm text-violet-700/80 mt-1" dir="ltr">{{ $agent->phone }}</p>
            @endif
        </div>
        <a href="{{ route('fleet.custody.balances') }}" class="fleet-btn fleet-btn-soft text-sm">كل الأرصدة</a>
    </div>

    <div class="grid gap-3 grid-cols-2 max-w-md">
        <div class="fleet-card p-4 text-center">
            <p class="text-xs text-violet-700 mb-1">إجمالي الكمية</p>
            <p class="text-xl font-extrabold text-violet-600 tabular-nums">{{ number_format($totalQty, 2) }}</p>
        </div>
        <div class="fleet-card p-4 text-center">
            <p class="text-xs text-violet-700 mb-1">قيمة العهدة</p>
            <p class="text-xl font-extrabold text-violet-600 tabular-nums">{{ erp_money($totalValue) }}</p>
        </div>
    </div>

    <div class="fleet-card overflow-hidden">
        <table class="min-w-full text-sm">
            <thead class="bg-violet-50">
                <tr>
                    <th class="px-4 py-3 text-right font-semibold"><x-info field="fleet.custody_product" /> الصنف</th>
                    <th class="px-4 py-3 text-right font-semibold">SKU</th>
                    <th class="px-4 py-3 text-right font-semibold"><x-info field="fleet.custody_quantity" /> الرصيد</th>
                    <th class="px-4 py-3 text-right font-semibold">سعر الوحدة</th>
                    <th class="px-4 py-3 text-right font-semibold">القيمة</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                @forelse($lines as $line)
                    <tr>
                        <td class="px-4 py-3 font-medium">{{ $line->product_name }}</td>
                        <td class="px-4 py-3 font-mono text-xs">{{ $line->sku ?? '—' }}</td>
                        <td class="px-4 py-3 tabular-nums">{{ number_format($line->quantity, 2) }}</td>
                        <td class="px-4 py-3 tabular-nums">{{ erp_money($line->unit_price) }}</td>
                        <td class="px-4 py-3 tabular-nums">{{ erp_money($line->line_value) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="px-4 py-8 text-center text-gray-500">لا يوجد رصيد عهدة لهذا المندوب.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
