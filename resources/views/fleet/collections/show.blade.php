@extends('layouts.fleet')

@section('title', $collection->collection_number.' — تحصيل')

@section('content')
<div class="space-y-6" dir="rtl">
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-extrabold text-violet-950">{{ $collection->collection_number }}</h1>
            <p class="text-sm text-violet-700/80 mt-1">
                {{ $collection->agent?->name }}
                @if($collection->customer) · {{ $collection->customer->name }} @endif
                · {{ $collection->collected_on->format('Y-m-d') }}
                · {{ $statusLabels[$collection->status] ?? $collection->status }}
            </p>
        </div>
        <div class="flex flex-wrap gap-2">
            @if(app(\App\Support\FleetAccess::class)->allows(\App\Support\FleetAccess::CAP_MANAGE_COLLECTIONS))
                @if($collection->isDraft())
                    <form method="POST" action="{{ route('fleet.collections.confirm', $collection) }}">@csrf
                        <button type="submit" class="fleet-btn fleet-btn-primary text-sm">تأكيد التحصيل</button>
                    </form>
                @endif
                @if($collection->status !== \App\Models\Fleet\FleetCollection::STATUS_VOID)
                    <form method="POST" action="{{ route('fleet.collections.void', $collection) }}" onsubmit="return confirm('إلغاء سند التحصيل؟')">@csrf
                        <button type="submit" class="fleet-btn fleet-btn-soft text-sm text-red-700">إلغاء السند</button>
                    </form>
                @endif
            @endif
            <a href="{{ route('fleet.collections.index') }}" class="fleet-btn fleet-btn-soft text-sm">رجوع</a>
        </div>
    </div>

    @if($errors->has('collection'))
        <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-900">{{ $errors->first('collection') }}</div>
    @endif

    <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-3">
        <div class="fleet-card p-4">
            <p class="text-xs text-violet-700 mb-1"><x-info field="fleet.collection_payment_method" /> طريقة التحصيل</p>
            <p class="font-semibold text-violet-950">{{ $paymentLabels[$collection->payment_method] ?? $collection->payment_method }}</p>
        </div>
        <div class="fleet-card p-4">
            <p class="text-xs text-violet-700 mb-1"><x-info field="fleet.collection_subtotal" /> الإجمالي</p>
            <p class="font-extrabold text-violet-600 tabular-nums">{{ erp_money($collection->subtotal) }}</p>
        </div>
        @if($collection->route)
            <div class="fleet-card p-4">
                <p class="text-xs text-violet-700 mb-1"><x-info field="fleet.collection_route" /> خط السير</p>
                <a href="{{ route('fleet.routes.show', $collection->route) }}" class="font-semibold text-violet-600 hover:underline">{{ $collection->route->route_date->format('Y-m-d') }}</a>
            </div>
        @endif
        @if($collection->routeStop)
            <div class="fleet-card p-4">
                <p class="text-xs text-violet-700 mb-1"><x-info field="fleet.collection_route_stop" /> محطة الزيارة</p>
                <p class="font-semibold text-violet-950">{{ $collection->routeStop->customer?->name ?? '—' }}</p>
            </div>
        @endif
    </div>

    @if($collection->notes)
        <div class="fleet-card p-4 text-sm">{{ $collection->notes }}</div>
    @endif

    <div class="fleet-card overflow-hidden">
        <table class="min-w-full text-sm">
            <thead class="bg-violet-50">
                <tr>
                    <th class="px-4 py-3 text-right font-semibold"><x-info field="fleet.custody_product" /> الصنف</th>
                    <th class="px-4 py-3 text-right font-semibold">SKU</th>
                    <th class="px-4 py-3 text-right font-semibold"><x-info field="fleet.custody_quantity" /> الكمية</th>
                    <th class="px-4 py-3 text-right font-semibold"><x-info field="fleet.collection_unit_price" /> سعر الوحدة</th>
                    <th class="px-4 py-3 text-right font-semibold">الإجمالي</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                @foreach($collection->lines as $line)
                    <tr>
                        <td class="px-4 py-3 font-medium">{{ $line->product?->name ?? '—' }}</td>
                        <td class="px-4 py-3 font-mono text-xs">{{ $line->product?->sku ?? '—' }}</td>
                        <td class="px-4 py-3 tabular-nums">{{ number_format($line->quantity, 2) }}</td>
                        <td class="px-4 py-3 tabular-nums">{{ erp_money($line->unit_price) }}</td>
                        <td class="px-4 py-3 tabular-nums">{{ erp_money($line->line_total) }}</td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot class="bg-violet-50/50">
                <tr>
                    <td colspan="2" class="px-4 py-3 font-semibold">الإجمالي</td>
                    <td class="px-4 py-3 tabular-nums font-semibold">{{ number_format($totalQty, 2) }}</td>
                    <td class="px-4 py-3"></td>
                    <td class="px-4 py-3 tabular-nums font-semibold">{{ erp_money($collection->subtotal) }}</td>
                </tr>
            </tfoot>
        </table>
    </div>

    @if($collection->isConfirmed())
        <p class="text-sm text-emerald-800">
            تم خصم البضاعة من عهدة {{ $collection->agent?->name }} —
            <a href="{{ route('fleet.custody.balances.agent', $collection->agent_id) }}" class="font-semibold underline">عرض الرصيد</a>
        </p>
    @endif
</div>
@endsection
