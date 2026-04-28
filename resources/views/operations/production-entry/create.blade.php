@extends('layouts.app')

@section('title', 'تسجيل إنتاج - Factory ERP')

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
@endphp

@section('content')
<div class="max-w-full space-y-6" dir="rtl">
    @if (session('error'))
        <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">{{ session('error') }}</div>
    @endif
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <h1 class="text-2xl font-bold text-gray-900">تسجيل الإنتاج اللحظي</h1>
        <a href="{{ route('operations.shifts.index') }}" class="inline-flex items-center justify-center rounded-lg border border-gray-200 bg-white px-4 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50">العودة لإدارة الورديات</a>
    </div>

    <p class="text-sm text-gray-600">يُستخدَم جدول <strong>production_logs</strong> (لا يوجد جدول <code>production_entries</code>). الربط بالورديات عبر <strong>production_shifts</strong> وقوالب <strong>shifts</strong> في موديول العمليات — وليس بجدول الورديات/الحضور في الموارد البشرية.</p>

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
                <form action="{{ route('operations.production-entry.store') }}" method="POST" class="space-y-4">
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
                        <label class="mb-1.5 block text-sm font-semibold text-gray-800">مستودع صرف/إدخال المخزون</label>
                        <p class="mb-1 text-xs text-gray-500">للمنتج التام: عند اختيار مستودع يُصرف الخام حسب BOM ويُدخل التام. اترك فارغاً لإثبات دفاتري فقط (بدون حركات مخزن).</p>
                        <x-custom-select
                            name="warehouse_id"
                            id="pe_warehouse_id"
                            :options="$warehouseOptions"
                            :value="old('warehouse_id', '')"
                            :required="false"
                            :error="$errors->has('warehouse_id')"
                            :empty-option="true"
                            empty-label="— بدون ترحيل مخزني —"
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
                        <input type="hidden" id="item-barcode-data" value="">
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
                        <select name="scrap_reason" class="w-full rounded-lg border border-gray-200 bg-white px-3 py-2.5 text-sm @error('scrap_reason') border-red-500 @enderror">
                            <option value="">— لا يوجد —</option>
                            <option value="quality_defect" {{ old('scrap_reason') === 'quality_defect' ? 'selected' : '' }}>عيب جودة</option>
                            <option value="machine_error" {{ old('scrap_reason') === 'machine_error' ? 'selected' : '' }}>خطأ ماكينة</option>
                            <option value="material_defect" {{ old('scrap_reason') === 'material_defect' ? 'selected' : '' }}>عيب مادة خام</option>
                            <option value="operator_error" {{ old('scrap_reason') === 'operator_error' ? 'selected' : '' }}>خطأ تشغيل</option>
                            <option value="other" {{ old('scrap_reason') === 'other' ? 'selected' : '' }}>أخرى</option>
                        </select>
                        @error('scrap_reason')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <label class="mb-1.5 block text-sm font-semibold text-gray-800">سبب التوقف (Downtime)</label>
                            <select name="downtime_reason" id="downtime_reason" class="w-full rounded-lg border border-gray-200 bg-white px-3 py-2.5 text-sm @error('downtime_reason') border-red-500 @enderror">
                                <option value="">— لا يوجد —</option>
                                <option value="electricity" {{ old('downtime_reason') === 'electricity' ? 'selected' : '' }}>انقطاع كهرباء</option>
                                <option value="machine_failure" {{ old('downtime_reason') === 'machine_failure' ? 'selected' : '' }}>عطل ماكينة</option>
                                <option value="maintenance" {{ old('downtime_reason') === 'maintenance' ? 'selected' : '' }}>صيانة</option>
                                <option value="other" {{ old('downtime_reason') === 'other' ? 'selected' : '' }}>أخرى</option>
                            </select>
                            @error('downtime_reason')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label class="mb-1.5 block text-sm font-semibold text-gray-800">ساعات ضائعة</label>
                            <input type="number" inputmode="decimal" name="downtime_lost_hours" class="w-full rounded-lg border border-gray-200 bg-white px-3 py-2.5 text-sm tabular-nums @error('downtime_lost_hours') border-red-500 @enderror"
                                   value="{{ old('downtime_lost_hours') }}" min="0" max="24" step="any" placeholder="0">
                            @error('downtime_lost_hours')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                        </div>
                    </div>
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
                            <th class="px-3 py-2 font-medium">ملاحظات</th>
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
                                <td class="px-3 py-2 max-w-[12rem] truncate text-gray-600" title="{{ $log->notes }}">{{ $log->notes }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-3 py-8 text-center text-gray-500">
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

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    var barcodeInput = document.getElementById('item-barcode');
    if (!barcodeInput) return;

    function selectItemById(id) {
        window.dispatchEvent(new CustomEvent('erp-sync-searchable', { detail: { id: 'item_id', value: String(id) } }));
        barcodeInput.value = '';
    }

    function searchByBarcode() {
        var barcode = barcodeInput.value.trim();
        if (!barcode) return;
        var url = '{{ route("operations.production-entry.item-by-barcode") }}?barcode=' + encodeURIComponent(barcode);
        fetch(url, { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } })
            .then(function(r) { return r.json(); })
            .then(function(data) {
                if (data.found && data.id) {
                    selectItemById(data.id);
                }
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

