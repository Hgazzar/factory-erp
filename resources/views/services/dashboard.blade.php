@extends('layouts.app')

@section('title', 'الخدمات والصيانة - '.config('app.name'))

@section('content')
<div class="max-w-full" dir="rtl">
    <div class="flex flex-wrap items-center justify-between gap-4 mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">لوحة الخدمات والصيانة</h1>
            <p class="text-sm text-gray-500 mt-1">متابعة طلبات التركيب والصيانة والإصلاح.</p>
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('services.orders.index') }}" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-lg border border-gray-300 bg-white text-gray-700 text-sm font-medium hover:bg-gray-50">كل الطلبات</a>
            <a href="{{ route('services.orders.create') }}" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-lg text-white text-sm font-medium shadow-sm" style="background: #2563eb;">طلب خدمة جديد</a>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6">
            <p class="text-sm text-gray-500 mb-1"><x-info field="services.open_orders_widget" /> طلبات مفتوحة</p>
            <p class="text-3xl font-bold text-amber-700">{{ $openCount }}</p>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6">
            <p class="text-sm text-gray-500 mb-1"><x-info field="services.urgent_orders_widget" /> عاجلة (مفتوحة)</p>
            <p class="text-3xl font-bold text-red-600">{{ $urgentOpen }}</p>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6">
            <p class="text-sm text-gray-500 mb-1"><x-info field="services.warranty_expiring_widget" /> ضمان ينتهي خلال 30 يوماً</p>
            <p class="text-3xl font-bold text-indigo-700">{{ $warrantyExpiringSoon->count() }}</p>
        </div>
    </div>

    @if($warrantyExpiringSoon->isNotEmpty())
    <div class="bg-amber-50 border border-amber-200 rounded-xl p-4 mb-6">
        <h2 class="font-semibold text-amber-900 mb-3">تنبيه ضمان — ينتهي قريباً</h2>
        <ul class="space-y-2 text-sm text-amber-950">
            @foreach($warrantyExpiringSoon as $asset)
                <li class="flex flex-wrap justify-between gap-2 border-b border-amber-200/60 pb-2 last:border-0">
                    <span>{{ $asset->item?->name_ar ?? $asset->item?->code }} — {{ $asset->deliveryOrder?->delivery_number ?? 'DO-'.$asset->delivery_order_id }}</span>
                    <span class="font-medium">حتى {{ $asset->warranty_end?->format('Y-m-d') }}</span>
                </li>
            @endforeach
        </ul>
    </div>
    @endif

    <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
        <div class="px-4 py-3 border-b border-gray-200 bg-gray-50 font-semibold text-gray-800">آخر طلبات الخدمة</div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-right">
                <thead class="bg-gray-50 text-gray-600 border-b border-gray-200">
                    <tr>
                        <th class="py-3 px-4 font-medium">المرجع</th>
                        <th class="py-3 px-4 font-medium">النوع</th>
                        <th class="py-3 px-4 font-medium">الأولوية</th>
                        <th class="py-3 px-4 font-medium">الحالة</th>
                        <th class="py-3 px-4 font-medium">العميل</th>
                        <th class="py-3 px-4 font-medium"></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($recentOrders as $o)
                        <tr class="border-b border-gray-100">
                            <td class="py-3 px-4 font-mono">{{ $o->reference_number }}</td>
                            <td class="py-3 px-4">{{ match($o->service_type) { 'install' => 'تركيب', 'maintenance' => 'صيانة', 'repair' => 'إصلاح', default => $o->service_type } }}</td>
                            <td class="py-3 px-4">{{ $o->priority === 'urgent' ? 'عاجل' : 'عادي' }}</td>
                            <td class="py-3 px-4">{{ match($o->status) { 'open' => 'مفتوح', 'assigned' => 'مسند لفني', 'in_progress' => 'قيد التنفيذ', 'completed' => 'مكتمل', 'cancelled' => 'ملغى', default => $o->status } }}</td>
                            <td class="py-3 px-4">{{ $o->customer?->name ?? '—' }}</td>
                            <td class="py-3 px-4"><a href="{{ route('services.orders.show', $o) }}" class="text-indigo-600 hover:underline">عرض</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="py-8 text-center text-gray-500">لا توجد طلبات بعد.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
