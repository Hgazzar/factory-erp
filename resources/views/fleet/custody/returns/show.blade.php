@extends('layouts.fleet')

@section('title', $return->return_number.' — مرتجع عهدة')

@section('content')
<div class="space-y-6" dir="rtl">
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-extrabold text-violet-950">{{ $return->return_number }}</h1>
            <p class="text-sm text-violet-700/80 mt-1">
                {{ $return->agent?->name }} · {{ $return->returned_on->format('Y-m-d') }}
                · {{ $statusLabels[$return->status] ?? $return->status }}
            </p>
        </div>
        <div class="flex flex-wrap gap-2">
            @if(app(\App\Support\FleetAccess::class)->allows(\App\Support\FleetAccess::CAP_MANAGE_CUSTODY))
                @if($return->isDraft())
                    <form method="POST" action="{{ route('fleet.custody.returns.confirm', $return) }}">@csrf
                        <button type="submit" class="fleet-btn fleet-btn-primary text-sm">تأكيد المرتجع</button>
                    </form>
                @endif
                @if($return->status !== \App\Models\Fleet\FleetCustodyReturn::STATUS_VOID)
                    <form method="POST" action="{{ route('fleet.custody.returns.void', $return) }}" onsubmit="return confirm('إلغاء سند المرتجع؟')">@csrf
                        <button type="submit" class="fleet-btn fleet-btn-soft text-sm text-red-700">إلغاء السند</button>
                    </form>
                @endif
            @endif
            <a href="{{ route('fleet.custody.returns.index') }}" class="fleet-btn fleet-btn-soft text-sm">رجوع</a>
        </div>
    </div>

    @if($errors->has('custody_return'))
        <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-900">{{ $errors->first('custody_return') }}</div>
    @endif

    @if($return->notes)
        <div class="fleet-card p-4 text-sm">{{ $return->notes }}</div>
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
                @foreach($return->lines as $line)
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

    @if($return->isConfirmed())
        <p class="text-sm text-emerald-800">
            تم خصم المرتجع من رصيد العهدة —
            <a href="{{ route('fleet.custody.balances.agent', $return->agent_id) }}" class="font-semibold underline">عرض رصيد {{ $return->agent?->name }}</a>
        </p>
    @endif
</div>
@endsection
