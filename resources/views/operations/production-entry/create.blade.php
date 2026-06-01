@extends('layouts.app')

@section('title', 'تسجيل إنتاج - '.config('app.name'))

@php
    $warehouseOptions = $warehouses->map(fn ($w) => [
        'value' => (string) $w->id,
        'label' => trim(($w->code ? $w->code.' — ' : '').$w->name_ar),
    ])->values()->all();
    $itemSelectOptions = $items->map(fn ($i) => [
        'value' => (string) $i->id,
        'label' => $i->code.' — '.$i->name_ar,
    ])->values()->all();
    $shiftOptions = $productionShifts->map(fn ($ps) => [
        'value' => (string) $ps->id,
        'label' => $ps->date->format('Y-m-d').' — '.($ps->shift?->name_ar ?? 'وردية')
            .($ps->productionLine ? ' — خط '.$ps->productionLine->code : '')
            .($ps->machine ? ' — م '.$ps->machine->code : ''),
    ])->values()->all();
    $scrapReasonOptions = [
        ['value' => 'quality_defect', 'label' => 'عيب جودة'],
        ['value' => 'machine_error', 'label' => 'خطأ ماكينة'],
        ['value' => 'material_defect', 'label' => 'عيب مادة خام'],
        ['value' => 'operator_error', 'label' => 'خطأ تشغيل'],
        ['value' => 'other', 'label' => 'أخرى'],
    ];
    $downtimeReasonOptions = [
        ['value' => 'electricity', 'label' => 'انقطاع كهرباء'],
        ['value' => 'machine_failure', 'label' => 'عطل ماكينة'],
        ['value' => 'maintenance', 'label' => 'صيانة'],
        ['value' => 'other', 'label' => 'أخرى'],
    ];
    $warehouseRequired = $warehouseRequired ?? false;
@endphp

@section('content')
<div class="max-w-full space-y-6" dir="rtl">
    @if (session('error'))
        <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">{{ session('error') }}</div>
    @endif
    @if (session('warning'))
        <div class="rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">{{ session('warning') }}</div>
    @endif
    @if (session('success'))
        <div class="rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">{{ session('success') }}</div>
    @endif

    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <h1 class="text-2xl font-bold text-gray-900">تسجيل الإنتاج اللحظي</h1>
        <a href="{{ route('operations.shifts.index') }}" class="inline-flex items-center justify-center rounded-lg border border-gray-200 bg-white px-4 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50">العودة لإدارة الورديات</a>
    </div>

    <p class="text-sm text-gray-600">يُربط الإنتاج بالوردية عبر <strong>production_shifts</strong>. بدون مستودع لا تُنشأ حركات مخزن ولا قيود محاسبية.</p>

<div class="grid grid-cols-1 gap-6 lg:grid-cols-12">
    <div class="space-y-6 lg:col-span-5">
        <div class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm">
            <form class="flex flex-wrap items-end gap-4" method="GET" action="{{ route('operations.production-entry.create') }}">
                <div class="min-w-[10rem] flex-1">
                    <label class="mb-1.5 block text-sm font-semibold text-gray-800">تاريخ الوردية</label>
                    <input type="date" name="date" class="w-full rounded-lg border border-gray-200 bg-white px-3 py-2.5 text-sm" value="{{ $date }}">
                </div>
                <button type="submit" class="inline-flex items-center justify-center rounded-lg border border-indigo-200 bg-indigo-50 px-4 py-2.5 text-sm font-semibold text-indigo-800 hover:bg-indigo-100">تحديث</button>
            </form>
        </div>

        <div class="rounded-lg border border-gray-200 bg-white shadow-sm">
            <div class="border-b border-gray-100 px-4 py-3 text-base font-bold text-gray-900">نموذج إدخال الإنتاج</div>
            <div class="p-4 sm:p-5">
                <div id="bom-warning-banner" class="hidden mb-4 rounded-lg border border-amber-300 bg-amber-50 px-4 py-3 text-sm text-amber-950" role="alert"></div>

                <form action="{{ route('operations.production-entry.store') }}" method="POST" class="space-y-4" id="production-entry-form">
                    @csrf
                    <div>
                        <label class="mb-1.5 block text-sm font-semibold text-gray-800">وردية الإنتاج <span class="text-red-600">*</span></label>
                        <x-custom-select
                            name="production_shift_id"
                            id="pe_production_shift_id"
                            :options="$shiftOptions"
                            :value="old('production_shift_id', $selectedProductionShiftId ? (string) $selectedProductionShiftId : '')"
                            :required="true"
                            :error="$errors->has('production_shift_id')"
                            :empty-option="true"
                            empty-label="— اختر الوردية —"
                            placeholder="ابحث بالتاريخ أو الخط..."
                        />
                        @error('production_shift_id')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="mb-1.5 block text-sm font-semibold text-gray-800 flex items-center gap-1">
                            <x-info field="production_entry_warehouse" />
                            مستودع صرف/إدخال المخزون
                            @if($warehouseRequired)
                                <span class="text-red-600">*</span>
                            @endif
                        </label>
                        @canFeature(\App\Support\PremiumFeatureKeys::MANUFACTURING_INVENTORY_AUTO_LINK)
                            <p class="mb-1 text-xs text-indigo-700">ميزة الربط المخزني المؤتمت مفعّلة — المستودع إلزامي.</p>
                        @else
                            <p class="mb-1 text-xs text-gray-500">عند اختيار مستودع: يُصرف الخام حسب BOM ويُدخل التام ويُرحَّل القيد. بدون مستودع: تسجيل كميات فقط.</p>
                        @endcanFeature
                        <x-custom-select
                            name="warehouse_id"
                            id="pe_warehouse_id"
                            :options="$warehouseOptions"
                            :value="old('warehouse_id', '')"
                            :required="$warehouseRequired"
                            :error="$errors->has('warehouse_id')"
                            :empty-option="! $warehouseRequired"
                            :empty-label="$warehouseRequired ? null : '— بدون ترحيل مخزني —'"
                            placeholder="ابحث باسم المستودع أو الكود..."
                        />
                        @error('warehouse_id')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="mb-1.5 block text-sm font-semibold text-gray-800">الصنف <span class="text-red-600">*</span></label>
                        <div class="mb-2 flex flex-wrap items-stretch gap-2">
                            <input type="text" id="item-barcode" class="min-w-0 flex-1 rounded-lg border border-gray-200 bg-white px-3 py-2.5 text-sm" placeholder="امسح الباركود أو اكتبه"
                                   autocomplete="off" title="امسح الباركود لاختيار الصنف تلقائياً">
                            <span class="inline-flex items-center rounded-lg border border-gray-200 bg-gray-50 px-3 text-xs text-gray-500">باركود</span>
                        </div>
                        <x-custom-select
                            name="item_id"
                            id="item_id"
                            :options="$itemSelectOptions"
                            :value="old('item_id', '')"
                            :required="true"
                            :error="$errors->has('item_id')"
                            :empty-option="true"
                            empty-label="— اختر الصنف —"
                            placeholder="ابحث بالكود أو الاسم..."
                        />
                        @error('item_id')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <label class="mb-1.5 block text-sm font-semibold text-gray-800">الكمية المنتجة <span class="text-red-600">*</span></label>
                            <input type="number" inputmode="decimal" name="quantity"
                                   class="w-full rounded-lg border border-gray-200 bg-white px-3 py-2.5 text-sm tabular-nums @error('quantity') border-red-500 ring-1 ring-red-200 @enderror"
                                   value="{{ old('quantity') }}" min="0" step="any" required>
                            @error('quantity')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label class="mb-1.5 block text-sm font-semibold text-gray-800">الكمية المرفوضة / الهالك</label>
                            <input type="number" inputmode="decimal" name="rejected_quantity"
                                   class="w-full rounded-lg border border-gray-200 bg-white px-3 py-2.5 text-sm tabular-nums @error('rejected_quantity') border-red-500 ring-1 ring-red-200 @enderror"
                                   value="{{ old('rejected_quantity', 0) }}" min="0" step="any">
                            @error('rejected_quantity')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                        </div>
                    </div>
                    <div>
                        <label class="mb-1.5 block text-sm font-semibold text-gray-800">سبب الهالك</label>
                        <x-custom-select
                            name="scrap_reason"
                            id="pe_scrap_reason"
                            :options="$scrapReasonOptions"
                            :value="old('scrap_reason', '')"
                            :required="false"
                            :searchable="false"
                            :empty-option="true"
                            empty-label="— لا يوجد —"
                        />
                        @error('scrap_reason')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>

                    @canFeature(\App\Support\PremiumFeatureKeys::MANUFACTURING_MACHINE_DOWNTIME)
                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 rounded-lg border border-gray-100 bg-gray-50/80 p-3">
                            <div>
                                <label class="mb-1.5 block text-sm font-semibold text-gray-800 flex items-center gap-1">
                                    <x-info field="production_entry_downtime_reason" /> سبب التوقف
                                </label>
                                <x-custom-select
                                    name="downtime_reason"
                                    id="pe_downtime_reason"
                                    :options="$downtimeReasonOptions"
                                    :value="old('downtime_reason', '')"
                                    :required="false"
                                    :searchable="false"
                                    :empty-option="true"
                                    empty-label="— لا يوجد —"
                                />
                                @error('downtime_reason')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                            </div>
                            <div>
                                <label class="mb-1.5 block text-sm font-semibold text-gray-800 flex items-center gap-1">
                                    <x-info field="production_entry_downtime_hours" /> ساعات ضائعة
                                </label>
                                <input type="number" inputmode="decimal" name="downtime_lost_hours"
                                       class="w-full rounded-lg border border-gray-200 bg-white px-3 py-2.5 text-sm tabular-nums @error('downtime_lost_hours') border-red-500 @enderror"
                                       value="{{ old('downtime_lost_hours') }}" min="0" max="24" step="any" placeholder="0">
                                @error('downtime_lost_hours')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                            </div>
                        </div>
                    @endcanFeature

                    <div>
                        <label class="mb-1.5 block text-sm font-semibold text-gray-800">وقت التسجيل</label>
                        <input type="datetime-local" name="logged_at"
                               class="w-full rounded-lg border border-gray-200 bg-white px-3 py-2.5 text-sm @error('logged_at') border-red-500 @enderror"
                               value="{{ old('logged_at') }}">
                        @error('logged_at')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="mb-1.5 block text-sm font-semibold text-gray-800">ملاحظات</label>
                        <textarea name="notes" rows="2"
                                  class="w-full rounded-lg border border-gray-200 bg-white px-3 py-2.5 text-sm @error('notes') border-red-500 @enderror">{{ old('notes') }}</textarea>
                        @error('notes')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <button type="submit" class="inline-flex w-full items-center justify-center rounded-lg bg-indigo-600 px-4 py-3 text-sm font-semibold text-white shadow-sm hover:bg-indigo-700 sm:w-auto">تسجيل</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="space-y-4 lg:col-span-7">
        <div class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm">
            <div class="flex flex-wrap items-center justify-between gap-2 border-b border-gray-100 px-4 py-3">
                <span class="font-semibold text-gray-900">آخر 10 سجلات لتاريخ {{ $date }}</span>
                <a href="{{ route('operations.dashboard.index', ['from_date' => $date, 'to_date' => $date]) }}" class="inline-flex items-center rounded-lg border border-indigo-200 bg-indigo-50 px-3 py-1.5 text-xs font-medium text-indigo-800 hover:bg-indigo-100 sm:text-sm">
                    لوحة التحكم
                </a>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-100 text-right text-sm text-gray-800">
                    <thead class="bg-gray-50 text-gray-600">
                        <tr>
                            <th class="px-3 py-2 font-medium">الوقت</th>
                            <th class="px-3 py-2 font-medium">الوردية</th>
                            <th class="px-3 py-2 font-medium">الصنف</th>
                            <th class="px-3 py-2 font-medium">منتج</th>
                            <th class="px-3 py-2 font-medium">هالك</th>
                            <th class="px-3 py-2 font-medium">
                                <x-info field="production_entry_inventory_sync" /> المخزون
                            </th>
                            <th class="px-3 py-2 font-medium">
                                <x-info field="production_entry_journal_link" /> القيد
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse($recentLogs as $log)
                            <tr class="hover:bg-gray-50/80">
                                <td class="px-3 py-2 whitespace-nowrap tabular-nums">{{ $log->logged_at?->format('H:i') ?? '-' }}</td>
                                <td class="px-3 py-2">
                                    {{ $log->productionShift?->shift?->name_ar ?? '-' }}
                                    <div class="text-xs text-gray-500">
                                        {{ $log->productionShift?->date?->format('Y-m-d') }}
                                    </div>
                                </td>
                                <td class="px-3 py-2">
                                    {{ $log->item?->code ?? '-' }} — {{ $log->item?->name_ar ?? '' }}
                                </td>
                                <td class="px-3 py-2 tabular-nums">{{ number_format($log->quantity, 2) }}</td>
                                <td class="px-3 py-2 tabular-nums">{{ number_format($log->rejected_quantity, 2) }}</td>
                                <td class="px-3 py-2">
                                    @if($log->inventory_synced_at)
                                        <span class="inline-flex rounded-full bg-green-50 px-2 py-0.5 text-xs font-medium text-green-800" title="{{ $log->inventory_synced_at->format('Y-m-d H:i') }}">متزامن</span>
                                    @elseif($log->warehouse_id)
                                        <span class="inline-flex rounded-full bg-amber-50 px-2 py-0.5 text-xs font-medium text-amber-800">مستودع بدون مزامنة</span>
                                    @else
                                        <span class="inline-flex rounded-full bg-gray-100 px-2 py-0.5 text-xs font-medium text-gray-600">—</span>
                                    @endif
                                </td>
                                <td class="px-3 py-2 font-mono text-xs" dir="ltr">
                                    @if($log->linked_journal_entry_id ?? null)
                                        #{{ $log->linked_journal_entry_id }}
                                    @else
                                        <span class="text-gray-400">—</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-3 py-8 text-center text-gray-500">
                                    لا توجد سجلات إنتاج لهذا التاريخ.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    var barcodeInput = document.getElementById('item-barcode');
    var bomBanner = document.getElementById('bom-warning-banner');
    var bomStatusUrl = @json(route('operations.production-entry.item-bom-status'));

    function selectItemById(id) {
        window.dispatchEvent(new CustomEvent('erp-sync-searchable', { detail: { id: 'item_id', value: String(id) } }));
        if (barcodeInput) barcodeInput.value = '';
        refreshBomWarning(id);
    }

    function refreshBomWarning(itemId) {
        if (!bomBanner) return;
        var id = itemId || '';
        if (!id) {
            bomBanner.classList.add('hidden');
            bomBanner.textContent = '';
            return;
        }
        fetch(bomStatusUrl + '?item_id=' + encodeURIComponent(id), {
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
        })
            .then(function(r) { return r.json(); })
            .then(function(data) {
                if (data.show_warning && data.message) {
                    bomBanner.textContent = data.message;
                    bomBanner.classList.remove('hidden');
                } else {
                    bomBanner.classList.add('hidden');
                    bomBanner.textContent = '';
                }
            })
            .catch(function() {});
    }

    window.addEventListener('erp-sync-searchable', function(e) {
        if (e.detail && e.detail.id === 'item_id' && e.detail.value) {
            refreshBomWarning(e.detail.value);
        }
    });

    var initialItem = @json(old('item_id', ''));
    if (initialItem) refreshBomWarning(initialItem);

    if (!barcodeInput) return;

    function searchByBarcode() {
        var barcode = barcodeInput.value.trim();
        if (!barcode) return;
        var url = @json(route('operations.production-entry.item-by-barcode')) + '?barcode=' + encodeURIComponent(barcode);
        fetch(url, { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } })
            .then(function(r) { return r.json(); })
            .then(function(data) {
                if (data.found && data.id) selectItemById(data.id);
            })
            .catch(function() {});
    }

    barcodeInput.addEventListener('keydown', function(e) {
        if (e.key === 'Enter') { e.preventDefault(); searchByBarcode(); }
    });
    barcodeInput.addEventListener('blur', searchByBarcode);
});
</script>
@endpush
@endsection
