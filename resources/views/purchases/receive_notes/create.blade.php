@extends('layouts.app')

@section('title', 'سند استلام جديد - '.config('app.name'))

@section('breadcrumb')
    <a href="{{ route('dashboard') }}" class="text-gray-500 hover:text-indigo-600">الرئيسية</a>
    <span>›</span>
    <a href="{{ route('purchases.dashboard') }}" class="text-gray-500 hover:text-indigo-600">المشتريات</a>
    <span>›</span>
    <a href="{{ route('purchases.receive-notes.index') }}" class="text-gray-500 hover:text-indigo-600">سندات الاستلام</a>
    <span>›</span>
    <span class="text-indigo-900 font-semibold">سند استلام جديد</span>
@endsection

@push('styles')
<style>
    .rn-card { background: #fff; border-radius: 1rem; border: 1px solid #e5e7eb; box-shadow: 0 1px 3px rgba(0,0,0,0.06); }
    .rn-card-title { font-weight: 600; color: #1f2937; font-size: 1rem; margin-bottom: 1rem; display: flex; align-items: center; gap: 0.5rem; }
</style>
@endpush

@section('content')
@php
    $receiveNoteSupplierOptions = $suppliers->map(fn ($s) => [
        'value' => $s->id,
        'label' => trim((string) ($s->name ?? '').' ('.(string) ($s->code ?? '').')'),
    ])->all();
@endphp
<div class="max-w-full" dir="rtl" x-data="receiveNoteForm()">
    <div class="flex flex-wrap items-center justify-between gap-4 mb-6">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-2xl flex items-center justify-center flex-shrink-0" style="background: rgba(124, 58, 237, 0.2); color: #7c3aed;">
                <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" fill="currentColor" viewBox="0 0 16 16"><path d="M14 14V4.5L9.5 0H4a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h8a2 2 0 0 0 2-2zM9.5 3A1.5 1.5 0 0 0 11 4.5h2V14a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1V2a1 1 0 0 1 1-1h5.5v2z"/></svg>
            </div>
            <h1 class="text-2xl font-bold text-gray-900">سند استلام جديد</h1>
        </div>
        <a href="{{ route('purchases.receive-notes.index') }}" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-2xl border border-gray-300 bg-white text-gray-700 font-medium text-sm hover:bg-gray-50 transition">
            رجوع
        </a>
    </div>

    <form method="POST" action="{{ route('purchases.receive-notes.store') }}">
        @csrf

        <div class="space-y-6">
            {{-- تفاصيل الاستلام --}}
            <div class="rn-card p-5">
                <h2 class="rn-card-title">تفاصيل الاستلام</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1" for="supplier_id-trigger">المورد <span class="text-red-500">*</span></label>
                        <x-searchable-select
                            class="w-full"
                            name="supplier_id"
                            id="supplier_id"
                            :options="$receiveNoteSupplierOptions"
                            :value="old('supplier_id')"
                            :required="true"
                            empty-label="اختر المورد"
                            placeholder="ابحث باسم المورد أو الرمز..."
                        />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">أمر الشراء</label>
                        <select name="purchase_order_id" class="w-full px-3 py-2.5 rounded-2xl border border-gray-300 text-sm focus:ring-2 focus:ring-indigo-500">
                            <option value="">— لا يوجد —</option>
                            @foreach($purchaseOrders as $po)
                                <option value="{{ $po->id }}" {{ old('purchase_order_id') == $po->id ? 'selected' : '' }}>{{ $po->reference ?: 'أمر #' . $po->id }} — {{ $po->supplier->name ?? '' }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">المستودع <span class="text-red-500">*</span></label>
                        <select name="warehouse_id" required class="w-full px-3 py-2.5 rounded-2xl border border-gray-300 text-sm focus:ring-2 focus:ring-indigo-500">
                            <option value="">اختر المستودع</option>
                            @foreach($warehouses as $w)
                                <option value="{{ $w->id }}" {{ old('warehouse_id') == $w->id ? 'selected' : '' }}>{{ $w->name_ar ?? $w->name_en ?? $w->code }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">تاريخ الاستلام <span class="text-red-500">*</span></label>
                        <input type="date" name="receive_date" value="{{ old('receive_date', date('Y-m-d')) }}" required class="w-full px-3 py-2.5 rounded-2xl border border-gray-300 text-sm focus:ring-2 focus:ring-indigo-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">المرجع</label>
                        <input type="text" name="reference" value="{{ old('reference') }}" class="w-full px-3 py-2.5 rounded-2xl border border-gray-300 text-sm focus:ring-2 focus:ring-indigo-500" placeholder="المرجع">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">إشعار تسليم المورد</label>
                        <input type="text" name="supplier_delivery_notice" value="{{ old('supplier_delivery_notice') }}" class="w-full px-3 py-2.5 rounded-2xl border border-gray-300 text-sm focus:ring-2 focus:ring-indigo-500" placeholder="رقم إشعار التسليم">
                    </div>
                    <div class="md:col-span-2 lg:col-span-3 flex items-center gap-3">
                        <label class="inline-flex items-center gap-2 cursor-pointer">
                            <input type="hidden" name="requires_inspection" value="0">
                            <input type="checkbox" name="requires_inspection" value="1" {{ old('requires_inspection') ? 'checked' : '' }} class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                            <span class="text-sm font-medium text-gray-700">يتطلب فحص</span>
                        </label>
                        <span class="text-sm text-gray-500">سيتطلب هذا السند فحص جودة قبل التأكيد</span>
                    </div>
                </div>
            </div>

            {{-- بنود الاستلام --}}
            <div class="rn-card p-5">
                <div class="flex flex-wrap items-center justify-between gap-4 mb-4">
                    <h2 class="rn-card-title mb-0">بنود الاستلام</h2>
                    <button type="button" @click="addRow()" class="inline-flex items-center gap-2 px-3 py-2 rounded-xl border border-indigo-300 bg-indigo-50 text-indigo-700 text-sm font-medium hover:bg-indigo-100 transition">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16"><path d="M8 4a.5.5 0 0 1 .5.5v3h3a.5.5 0 0 1 0 1h-3v3a.5.5 0 0 1-1 0v-3h-3a.5.5 0 0 1 0-1h3v-3A.5.5 0 0 1 8 4z"/></svg>
                        إضافة بند
                    </button>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-right min-w-[900px]">
                        <thead class="bg-gray-50 border-b border-gray-200">
                            <tr>
                                <th class="py-2 px-2 font-medium text-gray-600 w-10"></th>
                                <th class="py-2 px-2 font-medium text-gray-600 min-w-[140px]">المنتج</th>
                                <th class="py-2 px-2 font-medium text-gray-600 min-w-[100px]">الوصف</th>
                                <th class="py-2 px-2 font-medium text-gray-600 w-20">مطلوب</th>
                                <th class="py-2 px-2 font-medium text-gray-600 w-20">مستلم</th>
                                <th class="py-2 px-2 font-medium text-gray-600 w-20">مقبول</th>
                                <th class="py-2 px-2 font-medium text-gray-600 w-20">مرفوض</th>
                                <th class="py-2 px-2 font-medium text-gray-600 min-w-[90px]">تكلفة الوحدة</th>
                                <th class="py-2 px-2 font-medium text-gray-600 min-w-[90px]">تكلفة البند</th>
                            </tr>
                        </thead>
                        <tbody>
                            <template x-for="(row, index) in rows" :key="index">
                                <tr class="border-b border-gray-100 align-top">
                                    <td class="py-2 px-2">
                                        <button type="button" @click="removeRow(index)" class="p-1.5 rounded-lg text-red-600 hover:bg-red-50" title="حذف">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" viewBox="0 0 16 16"><path d="M5.5 5.5A.5.5 0 0 1 6 6v6a.5.5 0 0 1-1 0V6a.5.5 0 0 1 .5-.5zm2.5 0a.5.5 0 0 1 .5.5v6a.5.5 0 0 1-1 0V6a.5.5 0 0 1 .5-.5zm3 .5a.5.5 0 0 0-1 0v6a.5.5 0 0 0 1 0V6z"/><path fill-rule="evenodd" d="M14.5 3a1 1 0 0 1-1 1H13v9a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V4h-.5a1 1 0 0 1-1-1V2a1 1 0 0 1 1-1H6a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1h3.5a1 1 0 0 1 1 1v1zM4.118 4 4 4.059V13a1 1 0 0 0 1 1h6a1 1 0 0 0 1-1V4.059L11.882 4H4.118zM2.5 3V2h11v1h-11z"/></svg>
                                        </button>
                                    </td>
                                    <td class="py-2 px-2">
                                        <select :name="'items[' + index + '][item_id]'" class="w-full px-2 py-2 rounded-xl border border-gray-300 text-sm focus:ring-2 focus:ring-indigo-500" x-model="row.item_id" @change="onItemSelect(index)">
                                            <option value="">اختر</option>
                                            @foreach($items as $item)
                                                <option value="{{ $item->id }}" data-unit="{{ optional($item->unit)->name_ar ?? optional($item->unit)->symbol ?? '' }}" data-cost="{{ $item->cost ?? 0 }}">{{ $item->name_ar ?? $item->name_en ?? $item->code }}</option>
                                            @endforeach
                                        </select>
                                        <input type="hidden" :name="'items[' + index + '][unit]'" x-model="row.unit">
                                    </td>
                                    <td class="py-2 px-2">
                                        <input type="text" :name="'items[' + index + '][description]'" class="w-full px-2 py-2 rounded-xl border border-gray-300 text-sm" placeholder="الوصف" x-model="row.description">
                                    </td>
                                    <td class="py-2 px-2">
                                        <input type="number" inputmode="decimal" step="any" min="0" :name="'items[' + index + '][quantity_required]'" class="w-full px-2 py-2 rounded-xl border border-gray-300 text-sm" x-model="row.quantity_required" @input="calcLineCost(index)">
                                    </td>
                                    <td class="py-2 px-2">
                                        <input type="number" inputmode="decimal" step="any" min="0" :name="'items[' + index + '][quantity]'" required class="w-full px-2 py-2 rounded-xl border border-gray-300 text-sm" x-model="row.quantity" @input="calcLineCost(index)" placeholder="0">
                                    </td>
                                    <td class="py-2 px-2">
                                        <input type="number" inputmode="decimal" step="any" min="0" :name="'items[' + index + '][quantity_accepted]'" class="w-full px-2 py-2 rounded-xl border border-gray-300 text-sm" x-model="row.quantity_accepted" placeholder="0">
                                    </td>
                                    <td class="py-2 px-2">
                                        <input type="number" inputmode="decimal" step="any" min="0" :name="'items[' + index + '][quantity_rejected]'" class="w-full px-2 py-2 rounded-xl border border-gray-300 text-sm" x-model="row.quantity_rejected" placeholder="0">
                                    </td>
                                    <td class="py-2 px-2">
                                        <input type="number" inputmode="decimal" step="any" min="0" :name="'items[' + index + '][unit_cost]'" class="w-full px-2 py-2 rounded-xl border border-gray-300 text-sm" x-model="row.unit_cost" @input="calcLineCost(index)" placeholder="0">
                                    </td>
                                    <td class="py-2 px-2">
                                        <input type="hidden" :name="'items[' + index + '][line_cost]'" :value="row.line_cost">
                                        <span class="block w-full px-2 py-2 rounded-xl border border-gray-200 text-sm bg-gray-50 text-gray-700" x-text="formatMoney(row.line_cost)"></span>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
                {{-- إجماليات --}}
                <div class="flex flex-wrap items-center gap-6 mt-4 pt-4 border-t border-gray-200">
                    <div class="flex items-center gap-2">
                        <span class="text-sm text-gray-600">إجمالي المستلم:</span>
                        <span class="font-semibold text-gray-900" x-text="totalReceived"></span>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="text-sm text-gray-600">إجمالي المقبول:</span>
                        <span class="font-semibold text-gray-900" x-text="totalAccepted"></span>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="text-sm text-gray-600">إجمالي المرفوض:</span>
                        <span class="font-semibold text-gray-900" x-text="totalRejected"></span>
                    </div>
                    <div class="flex items-center gap-2 mr-auto">
                        <span class="text-sm text-gray-600">إجمالي التكلفة:</span>
                        <span class="font-semibold text-gray-900">SAR <span x-text="totalCost.toFixed(2)"></span></span>
                    </div>
                </div>
            </div>

            {{-- معلومات إضافية --}}
            <div class="rn-card p-5">
                <h2 class="rn-card-title">معلومات إضافية</h2>
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">ملاحظات داخلية</label>
                        <textarea name="internal_notes" rows="3" class="w-full px-3 py-2.5 rounded-2xl border border-gray-300 text-sm focus:ring-2 focus:ring-indigo-500" placeholder="ملاحظات داخلية">{{ old('internal_notes') }}</textarea>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">ملاحظات</label>
                        <textarea name="notes" rows="3" class="w-full px-3 py-2.5 rounded-2xl border border-gray-300 text-sm focus:ring-2 focus:ring-indigo-500" placeholder="ملاحظات">{{ old('notes') }}</textarea>
                    </div>
                </div>
            </div>
        </div>

        <div class="flex flex-wrap items-center gap-3 mt-6 justify-end">
            <a href="{{ route('purchases.receive-notes.index') }}" class="px-5 py-2.5 rounded-2xl border border-gray-300 bg-white text-gray-700 text-sm font-medium hover:bg-gray-50 transition">إلغاء</a>
            <button type="submit" class="px-5 py-2.5 rounded-2xl text-white text-sm font-semibold transition bg-blue-600 hover:bg-blue-700 shadow-sm">
                حفظ
            </button>
        </div>
    </form>
</div>

<script>
function receiveNoteForm() {
    return {
        rows: [{
            item_id: '', description: '', unit: '',
            quantity_required: '', quantity: '', quantity_accepted: '', quantity_rejected: '',
            unit_cost: '', line_cost: 0
        }],
        get totalReceived() {
            return this.rows.reduce((s, r) => s + (parseFloat(r.quantity) || 0), 0);
        },
        get totalAccepted() {
            return this.rows.reduce((s, r) => s + (parseFloat(r.quantity_accepted) || 0), 0);
        },
        get totalRejected() {
            return this.rows.reduce((s, r) => s + (parseFloat(r.quantity_rejected) || 0), 0);
        },
        get totalCost() {
            return this.rows.reduce((s, r) => s + (parseFloat(r.line_cost) || 0), 0);
        },
        addRow() {
            this.rows.push({
                item_id: '', description: '', unit: '',
                quantity_required: '', quantity: '', quantity_accepted: '', quantity_rejected: '',
                unit_cost: '', line_cost: 0
            });
        },
        removeRow(i) {
            if (this.rows.length <= 1) return;
            this.rows.splice(i, 1);
        },
        onItemSelect(index) {
            const sel = document.querySelector(`select[name="items[${index}][item_id]"]`);
            if (!sel?.selectedOptions[0]) return;
            const opt = sel.selectedOptions[0];
            this.rows[index].unit = opt.getAttribute('data-unit') || '';
            const cost = opt.getAttribute('data-cost') || 0;
            if (cost) this.rows[index].unit_cost = cost;
            this.calcLineCost(index);
        },
        calcLineCost(index) {
            const r = this.rows[index];
            const qty = parseFloat(r.quantity) || 0;
            const cost = parseFloat(r.unit_cost) || 0;
            this.rows[index].line_cost = (qty * cost).toFixed(4);
        },
        formatMoney(v) {
            const n = parseFloat(v) || 0;
            return 'SAR ' + n.toFixed(2);
        }
    };
}
</script>
@endsection
