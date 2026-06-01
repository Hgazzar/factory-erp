@extends('layouts.app')

@section('title', 'طلب خدمة جديد - '.config('app.name'))

@section('content')
@php
    $serviceOrderCustomerOptions = $customers->map(fn ($c) => [
        'value' => $c->id,
        'label' => (string) ($c->name ?? ''),
    ])->all();
@endphp
<div class="max-w-3xl" dir="rtl">
    <div class="mb-6">
        <a href="{{ route('services.orders.index') }}" class="text-sm text-indigo-600 hover:underline">← رجوع للقائمة</a>
        <h1 class="text-2xl font-bold text-gray-900 mt-2">طلب خدمة جديد</h1>
    </div>

    <form method="post" action="{{ route('services.orders.store') }}" class="bg-white rounded-xl border border-gray-200 shadow-sm p-6 space-y-4" id="service-order-create">
        @csrf
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1"><x-info field="services.type_field" /> نوع الخدمة</label>
                <select name="service_type" id="service_type" class="w-full rounded-lg border-gray-300 text-sm" required>
                    <option value="install" @selected(old('service_type', 'maintenance')==='install')>تركيب</option>
                    <option value="maintenance" @selected(old('service_type')==='maintenance')>صيانة</option>
                    <option value="repair" @selected(old('service_type')==='repair')>إصلاح</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1"><x-info field="services.priority_field" /> الأولوية</label>
                <select name="priority" class="w-full rounded-lg border-gray-300 text-sm" required>
                    <option value="normal" @selected(old('priority','normal')==='normal')>عادي</option>
                    <option value="urgent" @selected(old('priority')==='urgent')>عاجل</option>
                </select>
            </div>
        </div>

        @if($salesOrder)
            <input type="hidden" name="sales_order_id" value="{{ $salesOrder->id }}">
            <p class="text-sm text-gray-600">مرتبط بأمر البيع: <span class="font-mono">SO-{{ $salesOrder->id }}</span></p>
        @endif
        @if($deliveryOrder)
            <input type="hidden" name="delivery_order_id" value="{{ $deliveryOrder->id }}">
            <p class="text-sm text-gray-600">مرتبط بأمر التوريد: <span class="font-mono">{{ $deliveryOrder->delivery_number }}</span></p>
        @endif
        @if(! $salesOrder && ! $deliveryOrder)
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1" for="customer_id-trigger">العميل</label>
                <x-searchable-select
                    class="w-full"
                    name="customer_id"
                    id="customer_id"
                    :options="$serviceOrderCustomerOptions"
                    :value="old('customer_id')"
                    empty-label="—"
                    placeholder="ابحث باسم العميل..."
                />
            </div>
        @endif

        @if($installedAssets->isNotEmpty())
            <div id="installed-asset-wrap">
                <label class="block text-sm font-medium text-gray-700 mb-1"><x-info field="services.installed_asset_field" /> الأصل المثبت (للصيانة/الإصلاح)</label>
                <select name="installed_asset_id" class="w-full rounded-lg border-gray-300 text-sm">
                    <option value="">— بدون تحديد —</option>
                    @foreach($installedAssets as $asset)
                        <option value="{{ $asset->id }}" @selected(old('installed_asset_id')==$asset->id)>
                            {{ $asset->item?->name_ar ?? $asset->item?->code }}
                            @if($asset->warranty_end)
                                — ضمان حتى {{ $asset->warranty_end->format('Y-m-d') }}
                            @endif
                        </option>
                    @endforeach
                </select>
            </div>
        @endif

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1"><x-info field="services.warehouse_field" /> مستودع صرف القطع</label>
            <select name="warehouse_id" class="w-full rounded-lg border-gray-300 text-sm" required>
                <option value="">— اختر مستودعاً —</option>
                @foreach($warehouses as $w)
                    <option value="{{ $w->id }}" @selected(old('warehouse_id')==$w->id)>{{ $w->name_ar ?? $w->code }}</option>
                @endforeach
            </select>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1"><x-info field="services.assign_technician_optional" /> تعيين فني (اختياري)</label>
            <select name="assigned_technician_id" class="w-full rounded-lg border-gray-300 text-sm">
                <option value="">— لاحقاً —</option>
                @foreach($technicians as $t)
                    <option value="{{ $t->id }}" @selected(old('assigned_technician_id')==$t->id)>{{ $t->name }}</option>
                @endforeach
            </select>
        </div>

        <div id="install-paid-wrap" class="flex items-center gap-2 rounded-lg border border-gray-100 bg-gray-50 p-3">
            <input type="hidden" name="is_paid_service" value="0">
            <input type="checkbox" name="is_paid_service" id="is_paid_service" value="1" class="rounded border-gray-300" @checked(old('is_paid_service', true))>
            <label for="is_paid_service" class="text-sm text-gray-700"><x-info field="services.install_paid_checkbox" /> تركيب مدفوع (يُولِّد فاتورة عند الإغلاق إن وُجدت بنود)</label>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1"><x-info field="services.labor_amount_field" /> أجرة يدوية مبدئية (اختياري)</label>
            <input type="number" inputmode="decimal" name="labor_amount" step="any" min="0" value="{{ old('labor_amount') }}" class="w-full rounded-lg border-gray-300 text-sm">
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">وصف / ملاحظات</label>
            <textarea name="description" rows="3" class="w-full rounded-lg border-gray-300 text-sm">{{ old('description') }}</textarea>
        </div>

        <div class="flex gap-2 pt-2">
            <button type="submit" class="px-5 py-2.5 rounded-lg text-white text-sm font-medium" style="background: #2563eb;">حفظ</button>
            <a href="{{ route('services.orders.index') }}" class="px-5 py-2.5 rounded-lg border border-gray-300 text-sm text-gray-700">إلغاء</a>
        </div>
    </form>
</div>
@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    var typeSel = document.getElementById('service_type');
    var installWrap = document.getElementById('install-paid-wrap');
    var assetWrap = document.getElementById('installed-asset-wrap');
    function sync() {
        if (!typeSel) return;
        var isInstall = typeSel.value === 'install';
        if (installWrap) installWrap.style.display = isInstall ? '' : 'none';
        if (assetWrap) assetWrap.style.display = (!isInstall) ? '' : 'none';
    }
    if (typeSel) typeSel.addEventListener('change', sync);
    sync();
});
</script>
@endpush
@endsection
