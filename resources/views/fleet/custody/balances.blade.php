@extends('layouts.fleet')

@section('title', 'أرصدة العهدة — '.niche_module_label('fleet'))

@section('content')
<div class="space-y-6" dir="rtl">
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-extrabold text-violet-950"><x-info field="fleet.custody_balance" /> أرصدة عهدة المناديب</h1>
            <p class="text-sm text-violet-700/80 mt-1">مجموع البضاعة المصروفة غير الملغاة</p>
        </div>
        <a href="{{ route('fleet.custody.index') }}" class="fleet-btn fleet-btn-soft">سجل السندات</a>
    </div>

    <div class="fleet-card overflow-hidden">
        <table class="min-w-full text-sm">
            <thead class="bg-violet-50">
                <tr>
                    <th class="px-4 py-3 text-right font-semibold"><x-info field="fleet.custody_agent" /> المندوب</th>
                    <th class="px-4 py-3 text-right font-semibold">أصناف</th>
                    <th class="px-4 py-3 text-right font-semibold">إجمالي الكمية</th>
                    <th class="px-4 py-3 text-right font-semibold">قيمة العهدة</th>
                    <th class="px-4 py-3 text-right font-semibold"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                @forelse($rows as $row)
                    <tr>
                        <td class="px-4 py-3 font-medium">{{ $row['agent_name'] }}</td>
                        <td class="px-4 py-3 tabular-nums">{{ $row['sku_count'] }}</td>
                        <td class="px-4 py-3 tabular-nums">{{ number_format($row['total_qty'], 2) }}</td>
                        <td class="px-4 py-3 tabular-nums">{{ erp_money($row['total_value']) }}</td>
                        <td class="px-4 py-3">
                            <a href="{{ route('fleet.custody.balances.agent', $row['agent_id']) }}" class="text-violet-600 font-semibold hover:underline">تفاصيل</a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="px-4 py-8 text-center text-gray-500">لا توجد عهدة مصروفة بعد.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
