@extends('layouts.fleet')

@section('title', 'طلبات المتجر — '.niche_module_label('fleet'))

@section('content')
<div class="space-y-6" dir="rtl">
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-extrabold text-violet-950"><x-info field="fleet.nav_store_orders" /> طلبات المتجر (Pool)</h1>
            <p class="text-sm text-violet-700/80 mt-1"><x-info field="fleet.store_orders_intro" /></p>
        </div>
        <a href="{{ route('fleet.routes.index') }}" class="fleet-btn fleet-btn-soft text-sm">خطوط السير</a>
    </div>

    @if($pendingCount === 0)
        <div class="fleet-card p-8 text-center text-violet-800">
            <p class="font-semibold">لا توجد طلبات بانتظار الإسناد.</p>
        </div>
    @else
        <div class="fleet-card overflow-hidden">
            <table class="min-w-full text-sm">
                <thead class="bg-violet-50">
                    <tr>
                        <th class="px-4 py-3 text-right font-semibold"><x-info field="fleet.store_order_invoice" /> الفاتورة</th>
                        <th class="px-4 py-3 text-right font-semibold"><x-info field="fleet.store_order_customer" /> العميل</th>
                        <th class="px-4 py-3 text-right font-semibold"><x-info field="fleet.store_order_phone" /> الجوال</th>
                        <th class="px-4 py-3 text-right font-semibold"><x-info field="fleet.store_order_total" /> الإجمالي</th>
                        <th class="px-4 py-3 text-right font-semibold"><x-info field="fleet.store_order_fulfillment" /> التسليم</th>
                        <th class="px-4 py-3 text-right font-semibold"><x-info field="fleet.store_order_assign_route" /> إسناد لخط سير</th>
                        <th class="px-4 py-3 text-right font-semibold"><x-info field="fleet.store_order_assign_agent" /> إسناد مندوب</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @foreach($orders as $order)
                        <tr class="hover:bg-violet-50/40">
                            <td class="px-4 py-3 font-mono text-xs" dir="ltr">{{ $order->invoice_number ?? '#'.$order->id }}</td>
                            <td class="px-4 py-3 font-medium">{{ $order->customer_name }}</td>
                            <td class="px-4 py-3" dir="ltr">{{ $order->customer_phone }}</td>
                            <td class="px-4 py-3 tabular-nums font-semibold">{{ number_format((float) $order->total_amount, 2) }}</td>
                            <td class="px-4 py-3">
                                <span class="inline-flex rounded-full px-2 py-0.5 text-xs font-semibold bg-amber-100 text-amber-800">
                                    {{ $fulfillmentLabels[$order->fulfillment_status] ?? $order->fulfillment_status }}
                                </span>
                            </td>
                            <td class="px-4 py-3">
                                @if(app(\App\Support\FleetAccess::class)->allows(\App\Support\FleetAccess::CAP_MANAGE_STORE_ORDERS))
                                    <form method="POST" action="{{ route('fleet.store-orders.assign-route', $order->id) }}" class="flex flex-wrap gap-1 items-center">
                                        @csrf
                                        <x-searchable-select
                                            name="route_id"
                                            :options="collect($routes)->map(fn ($r) => ['value' => (string) $r->id, 'label' => ($r->agent?->name ?? 'مندوب').' · '.$r->route_date->format('Y-m-d')])->all()"
                                            empty-label="اختر خط سير"
                                            :searchable="count($routes) > 6"
                                            class="min-w-[10rem]"
                                        />
                                        <button type="submit" class="fleet-btn fleet-btn-primary text-xs py-1 px-2">إسناد</button>
                                    </form>
                                @else
                                    —
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                @if(app(\App\Support\FleetAccess::class)->allows(\App\Support\FleetAccess::CAP_MANAGE_STORE_ORDERS))
                                    <form method="POST" action="{{ route('fleet.store-orders.assign-agent', $order->id) }}" class="flex flex-wrap gap-1 items-center">
                                        @csrf
                                        <x-searchable-select
                                            name="agent_id"
                                            :options="collect($agents)->map(fn ($a) => ['value' => (string) $a->id, 'label' => $a->name])->all()"
                                            empty-label="اختر مندوب"
                                            :searchable="count($agents) > 6"
                                            class="min-w-[8rem]"
                                        />
                                        <button type="submit" class="fleet-btn fleet-btn-soft text-xs py-1 px-2">إسناد</button>
                                    </form>
                                @else
                                    —
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div>{{ $orders->links() }}</div>
    @endif
</div>
@endsection
