@extends('layouts.app')

@section('title', (isset($editOrderModel) ? 'تعديل أمر شراء' : 'أمر شراء جديد') . ' - MIRADA ERP')

@php($isEdit = isset($editOrderModel))
@section('breadcrumb')
    <a href="{{ route('dashboard') }}" class="text-gray-500 hover:text-indigo-600">الرئيسية</a>
    <span>›</span>
    <a href="{{ route('purchases.dashboard') }}" class="text-gray-500 hover:text-indigo-600">المشتريات</a>
    <span>›</span>
    <a href="{{ route('purchases.orders.index') }}" class="text-gray-500 hover:text-indigo-600">أوامر الشراء</a>
    <span>›</span>
    <span class="text-indigo-900 font-semibold">{{ $isEdit ? 'تعديل أمر شراء' : 'أمر شراء جديد' }}</span>
@endsection

@push('styles')
<style>
    .po-card { background: #fff; border-radius: 1rem; border: 1px solid #e5e7eb; box-shadow: 0 1px 3px rgba(0,0,0,0.06); }
    .po-card-title { font-weight: 600; color: #1f2937; font-size: 1rem; margin-bottom: 1rem; display: flex; align-items: center; gap: 0.5rem; }
    .po-btn-save { background: #1e40af; color: #fff; font-weight: 600; padding: 0.625rem 1.5rem; border-radius: 1rem; border: none; box-shadow: 0 1px 2px rgba(30, 64, 175, 0.3); transition: background 0.2s; }
    .po-btn-save:hover { background: #1e3a8a; color: #fff; }
    .po-icon-muted { color: #7c3aed; opacity: 0.85; }
    .po-max-stock-warn { background: #fef3c7; border: 1px solid #f59e0b; color: #92400e; padding: 0.35rem 0.5rem; border-radius: 0.5rem; font-size: 0.8rem; margin-top: 0.25rem; }
</style>
@endpush

@section('content')
<div class="max-w-full" dir="rtl" x-data="purchaseOrderCreateForm(@js($items), @js($suppliers), @js($prefillLines ?? []), @js($editOrderPayload ?? null))" x-cloak>
    <div class="flex flex-wrap items-center justify-between gap-4 mb-6">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-2xl flex items-center justify-center flex-shrink-0 po-icon-muted" style="background: rgba(124, 58, 237, 0.2);">
                <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" fill="currentColor" viewBox="0 0 16 16"><path d="M0 1.5A.5.5 0 0 1 .5 1H2a.5.5 0 0 1 .485.379L2.89 3H14.5a.5.5 0 0 1 .491.592l-1.5 8A.5.5 0 0 1 13 12H4a.5.5 0 0 1-.491-.408L2.01 3.607 1.61 2H.5a.5.5 0 0 1-.5-.5z"/></svg>
            </div>
            <h1 class="text-2xl font-bold text-gray-900">{{ $isEdit ? 'تعديل أمر شراء' : 'أمر شراء جديد' }}</h1>
        </div>
        <a href="{{ $isEdit ? route('purchases.orders.show', $editOrderModel) : route('purchases.orders.index') }}" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-2xl border border-gray-300 bg-white text-gray-700 font-medium text-sm hover:bg-gray-50 transition">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" viewBox="0 0 16 16"><path d="M12 8a4 4 0 1 1-8 0 4 4 0 0 1 8 0zM5.5 8a2.5 2.5 0 1 0 5 0 2.5 2.5 0 0 0-5 0z"/><path d="M8 0a8 8 0 1 0 0 16A8 8 0 0 0 8 0zM4.5 7.5a.5.5 0 0 1 0 1h5.793l-2.147 2.146a.5.5 0 0 1-.708.708l-3-3a.5.5 0 0 1 0-.708l3-3a.5.5 0 1 1 .708.708L10.293 7.5H4.5z"/></svg>
            رجوع
        </a>
    </div>

    @if(session('error'))
        <div class="erp-alert-error">
            {{ session('error') }}
        </div>
    @endif

    <form method="POST" action="{{ $isEdit ? route('purchases.orders.update', $editOrderModel) : route('purchases.orders.store') }}" enctype="multipart/form-data">
        @csrf
        @if($isEdit)
            @method('PUT')
        @endif

        {{-- كرت بيانات المورد وتفاصيل الطلب --}}
        <div class="po-card p-5 mb-6">
            <h2 class="po-card-title">
                <span class="w-8 h-8 rounded-lg flex items-center justify-center" style="background: rgba(124, 58, 237, 0.15);">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" viewBox="0 0 16 16" class="po-icon-muted"><path d="M0 1.5A.5.5 0 0 1 .5 1H2a.5.5 0 0 1 .485.379L2.89 3H14.5a.5.5 0 0 1 .491.592l-1.5 8A.5.5 0 0 1 13 12H4a.5.5 0 0 1-.491-.408L2.01 3.607 1.61 2H.5a.5.5 0 0 1-.5-.5z"/></svg>
                </span>
                تفاصيل الطلب
            </h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1"><x-info field="procurement.purchase_order_code" /> رقم أمر الشراء</label>
                    <input type="text" value="{{ $isEdit ? ($editOrderModel->display_order_number ?? '') : ($nextOrderNumber ?? '') }}" readonly class="w-full py-2.5 pr-4 text-right bg-gray-100 border border-gray-200 rounded-2xl text-sm text-gray-600 cursor-not-allowed" title="{{ $isEdit ? 'رقم الأمر' : 'يُسجَّل تلقائياً عند الحفظ' }}">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">المورد <span class="text-red-500">*</span> <x-info field="procurement.purchase_order_supplier" /></label>
                    <select name="supplier_id" x-model="supplierId" required class="w-full px-3 py-2.5 pr-4 text-right bg-gray-50 border border-gray-300 rounded-2xl text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 @error('supplier_id') border-red-500 @enderror">
                        <option value="">اختر المورد</option>
                        <template x-for="s in suppliers" :key="s.id">
                            <option :value="s.id" x-text="s.name + (s.code ? ' (' + s.code + ')' : '')"></option>
                        </template>
                    </select>
                    @error('supplier_id')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">تاريخ الأمر <span class="text-red-500">*</span> <x-info field="procurement.purchase_order_date" /></label>
                    <input type="date" name="order_date" x-model="orderDate" required class="w-full py-2.5 pl-10 pr-4 text-right bg-gray-50 border border-gray-300 rounded-2xl text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                </div>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">العملة</label>
                    <select name="currency" class="w-full px-3 py-2.5 pr-4 text-right bg-gray-50 border border-gray-300 rounded-2xl text-sm focus:ring-2 focus:ring-indigo-500">
                        @php($cur = old('currency', $isEdit ? ($editOrderModel->currency ?? 'SAR') : 'SAR'))
                        <option value="SAR" @selected($cur === 'SAR')>SAR</option>
                        <option value="USD" @selected($cur === 'USD')>USD</option>
                        <option value="EUR" @selected($cur === 'EUR')>EUR</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">المرجع</label>
                    <input type="text" name="reference" value="{{ old('reference', $prefillReference ?? ($isEdit ? $editOrderModel->reference : null) ?? '') }}" placeholder="المرجع" class="w-full px-3 py-2.5 pr-4 text-right bg-gray-50 border border-gray-300 rounded-2xl text-sm focus:ring-2 focus:ring-indigo-500">
                </div>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">تاريخ التسليم المتوقع <x-info field="procurement.purchase_order_expected_delivery" /></label>
                    <input type="date" name="expected_delivery_date" x-model="expectedDeliveryDate" class="w-full py-2.5 pr-4 text-right bg-gray-50 border border-gray-300 rounded-2xl text-sm focus:ring-2 focus:ring-indigo-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">عنوان التسليم</label>
                    <input type="text" name="delivery_address" value="{{ old('delivery_address', $isEdit ? $editOrderModel->delivery_address : null) }}" placeholder="عنوان التسليم" class="w-full px-3 py-2.5 pr-4 text-right bg-gray-50 border border-gray-300 rounded-2xl text-sm focus:ring-2 focus:ring-indigo-500">
                </div>
            </div>
        </div>

        {{-- كرت بنود الطلب --}}
        <div class="po-card p-5 mb-6">
            <div class="flex items-center justify-between flex-wrap gap-3 mb-4">
                <h2 class="po-card-title">
                    <span class="w-8 h-8 rounded-lg flex items-center justify-center" style="background: rgba(124, 58, 237, 0.15);">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" viewBox="0 0 16 16" class="po-icon-muted"><path d="M1 2.5A1.5 1.5 0 0 1 2.5 1h3A1.5 1.5 0 0 1 7 2.5v3A1.5 1.5 0 0 1 5.5 7h-3A1.5 1.5 0 0 1 1 5.5v-3zM2.5 2a.5.5 0 0 0-.5.5v3a.5.5 0 0 0 .5.5h3a.5.5 0 0 0 .5-.5v-3a.5.5 0 0 0-.5-.5h-3zm6.5.5A1.5 1.5 0 0 1 10.5 1h3A1.5 1.5 0 0 1 15 2.5v3A1.5 1.5 0 0 1 13.5 7h-3A1.5 1.5 0 0 1 9 5.5v-3zm1.5-.5a.5.5 0 0 0-.5.5v3a.5.5 0 0 0 .5.5h3a.5.5 0 0 0 .5-.5v-3a.5.5 0 0 0-.5-.5h-3z"/></svg>
                    </span>
                    بنود الطلب <x-info field="procurement.purchase_order_items" />
                </h2>
                <button type="button" @click="addLine()" class="inline-flex items-center gap-2 px-4 py-2 rounded-2xl border border-gray-300 bg-white text-gray-700 text-sm font-medium hover:bg-gray-50 transition">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" viewBox="0 0 16 16"><path d="M8 4a.5.5 0 0 1 .5.5v3h3a.5.5 0 0 1 0 1h-3v3a.5.5 0 0 1-1 0v-3h-3a.5.5 0 0 1 0-1h3v-3A.5.5 0 0 1 8 4z"/></svg>
                    إضافة بند
                </button>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm text-right">
                    <thead class="bg-gray-50 border-b border-gray-200">
                        <tr>
                            <th class="py-3 px-3 font-medium text-gray-600">المنتج</th>
                            <th class="py-3 px-3 font-medium text-gray-600 min-w-[120px]">الوصف</th>
                            <th class="py-3 px-3 font-medium text-gray-600 w-24">الكمية</th>
                            <th class="py-3 px-3 font-medium text-gray-600 w-28">سعر الوحدة</th>
                            <th class="py-3 px-3 font-medium text-gray-600 w-20">الخصم %</th>
                            <th class="py-3 px-3 font-medium text-gray-600 w-20">ض.ق.م %</th>
                            <th class="py-3 px-3 font-medium text-gray-600 w-28 text-left">إجمالي البند</th>
                            <th class="py-3 px-3 font-medium w-12 text-left"></th>
                        </tr>
                    </thead>
                    <tbody>
                        <template x-for="(line, index) in lines" :key="index">
                            <tr class="border-b border-gray-100">
                                <td class="py-2 px-3">
                                    <select :name="'lines[' + index + '][item_id]'" x-model="line.item_id" @change="onItemSelect(index)" required class="w-full min-w-[140px] px-3 py-2 pr-4 text-right bg-gray-50 border border-gray-300 rounded-xl text-sm focus:ring-2 focus:ring-indigo-500">
                                        <option value="">اختر</option>
                                        <template x-for="item in items" :key="item.id">
                                            <option :value="item.id" x-text="(item.name_ar || item.code) + ' - ' + (item.code || '')"></option>
                                        </template>
                                    </select>
                                    <div x-show="maxStockWarning(line)" x-cloak class="po-max-stock-warn" x-transition>
                                        <span x-text="'تنبيه: الكمية المطلوبة ستجعل المخزون يتجاوز الحد الأقصى'"></span>
                                        <x-info field="procurement.purchase_order_max_stock_warning" />
                                    </div>
                                </td>
                                <td class="py-2 px-3">
                                    <input type="text" :name="'lines[' + index + '][description]'" x-model="line.description" placeholder="الوصف" class="w-full px-3 py-2 pr-4 text-right bg-gray-50 border border-gray-300 rounded-xl text-sm focus:ring-2 focus:ring-indigo-500">
                                </td>
                                <td class="py-2 px-3">
                                    <input type="number" inputmode="decimal" min="0.0001" step="any" :name="'lines[' + index + '][quantity]'" x-model.number="line.quantity" class="w-full px-3 py-2 pr-4 text-right bg-gray-50 border border-gray-300 rounded-xl text-sm focus:ring-2 focus:ring-indigo-500">
                                </td>
                                <td class="py-2 px-3">
                                    <input type="number" inputmode="decimal" min="0" step="any" :name="'lines[' + index + '][unit_price]'" x-model.number="line.unit_price" class="w-full px-3 py-2 pr-4 text-right bg-gray-50 border border-gray-300 rounded-xl text-sm focus:ring-2 focus:ring-indigo-500">
                                </td>
                                <td class="py-2 px-3">
                                    <input type="number" inputmode="decimal" min="0" max="100" step="any" :name="'lines[' + index + '][discount_percent]'" x-model.number="line.discount_percent" class="w-full px-3 py-2 pr-4 text-right bg-gray-50 border border-gray-300 rounded-xl text-sm focus:ring-2 focus:ring-indigo-500">
                                </td>
                                <td class="py-2 px-3">
                                    <input type="number" inputmode="decimal" min="0" max="100" step="any" :name="'lines[' + index + '][tax_percent]'" x-model.number="line.tax_percent" class="w-full px-3 py-2 pr-4 text-right bg-gray-50 border border-gray-300 rounded-xl text-sm focus:ring-2 focus:ring-indigo-500">
                                </td>
                                <td class="py-2 px-3 text-gray-900 font-medium text-left" x-text="'SAR ' + lineTotal(line).toFixed(2)"></td>
                                <td class="py-2 px-3 text-left">
                                    <button type="button" @click="removeLine(index)" x-show="lines.length > 1" class="p-1.5 rounded-lg text-red-600 hover:bg-red-50 transition" title="حذف">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" viewBox="0 0 16 16"><path d="M5.5 5.5A.5.5 0 0 1 6 6v6a.5.5 0 0 1-1 0V6a.5.5 0 0 1 .5-.5zm2.5 0a.5.5 0 0 1 .5.5v6a.5.5 0 0 1-1 0V6a.5.5 0 0 1 .5-.5zm3 .5a.5.5 0 0 0-1 0v6a.5.5 0 0 0 1 0V6z"/><path fill-rule="evenodd" d="M14.5 3a1 1 0 0 1-1 1H13v9a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V4h-.5a1 1 0 0 1-1-1V2a1 1 0 0 1 1-1H6a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1h3.5a1 1 0 0 1 1 1v1zM4.118 4 4 4.059V13a1 1 0 0 0 1 1h6a1 1 0 0 0 1-1V4.059L11.882 4H4.118zM2.5 3V2h11v1h-11z"/></svg>
                                    </button>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>

            {{-- ملخص الحسابات (مثبت على اليسار) --}}
            <div class="flex justify-end mt-4">
                <div class="w-full max-w-xs space-y-2 text-left">
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-600">المجموع الفرعي</span>
                        <span class="text-gray-900" x-text="'SAR ' + subtotal.toFixed(2)"></span>
                    </div>
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-600">الخصم</span>
                        <span class="text-gray-900" x-text="'SAR ' + totalDiscount.toFixed(2) + '-'"></span>
                    </div>
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-600">ضريبة القيمة المضافة</span>
                        <span class="text-gray-900" x-text="'SAR ' + totalTax.toFixed(2)"></span>
                    </div>
                    <div class="flex justify-between items-center text-sm gap-4">
                        <span class="text-gray-600">تكلفة الشحن</span>
                        <div class="flex items-center gap-2">
                            <input type="number" inputmode="decimal" name="shipping_cost" x-model.number="shippingCost" min="0" step="any" class="w-28 px-2 py-1.5 text-right border border-gray-200 rounded-xl text-sm">
                            <span class="text-gray-900 min-w-[5rem]" x-text="'SAR ' + (Number(shippingCost) || 0).toFixed(2)"></span>
                        </div>
                    </div>
                    <div class="flex justify-between text-sm pt-2 border-t border-gray-200">
                        <span class="font-semibold text-gray-900">الإجمالي</span>
                        <span class="text-lg font-bold text-gray-900" x-text="'SAR ' + grandTotal.toFixed(2)"></span>
                    </div>
                </div>
            </div>
        </div>

        {{-- معلومات إضافية --}}
        <div class="po-card p-5 mb-6">
            <h2 class="po-card-title">
                <span class="w-8 h-8 rounded-lg flex items-center justify-center" style="background: rgba(124, 58, 237, 0.15);">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" viewBox="0 0 16 16" class="po-icon-muted"><path d="M14 14V4.5L9.5 0H4a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h8a2 2 0 0 0 2-2zM9.5 3A1.5 1.5 0 0 0 11 4.5h2V14a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1V2a1 1 0 0 1 1-1h5.5v2z"/></svg>
                </span>
                معلومات إضافية
            </h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">ملاحظات داخلية</label>
                    <textarea name="internal_notes" rows="3" class="w-full px-3 py-2.5 pr-4 text-right bg-gray-50 border border-gray-300 rounded-2xl text-sm focus:ring-2 focus:ring-indigo-500" placeholder="ملاحظات داخلية">{{ old('internal_notes', $isEdit ? $editOrderModel->internal_notes : null) }}</textarea>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">ملاحظات</label>
                    <textarea name="notes" rows="3" class="w-full px-3 py-2.5 pr-4 text-right bg-gray-50 border border-gray-300 rounded-2xl text-sm focus:ring-2 focus:ring-indigo-500" placeholder="ملاحظات">{{ old('notes', $isEdit ? $editOrderModel->notes : null) }}</textarea>
                </div>
            </div>
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">الشروط والأحكام</label>
                <textarea name="terms_and_conditions" rows="3" class="w-full px-3 py-2.5 pr-4 text-right bg-gray-50 border border-gray-300 rounded-2xl text-sm focus:ring-2 focus:ring-indigo-500" placeholder="الشروط والأحكام">{{ old('terms_and_conditions', $isEdit ? $editOrderModel->terms_and_conditions : null) }}</textarea>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1"><x-info field="procurement.purchase_order_attachments" /> المرفقات</label>
                @if($isEdit && isset($editOrderModel) && $editOrderModel->attachments->isNotEmpty())
                    <ul class="mb-3 space-y-1.5 rounded-lg border border-gray-100 bg-gray-50 px-3 py-2 text-sm text-gray-800">
                        @foreach($editOrderModel->attachments as $att)
                            <li class="flex flex-wrap items-center justify-between gap-2">
                                <a href="{{ asset('storage/'.$att->file_path) }}" target="_blank" rel="noopener noreferrer" class="font-medium text-blue-600 hover:text-blue-800 break-all">{{ $att->file_name }}</a>
                                <span class="text-xs text-gray-500 tabular-nums">{{ $att->file_size ? number_format((int) $att->file_size / 1024, 1).' ك.ب' : '—' }}</span>
                            </li>
                        @endforeach
                    </ul>
                @endif
                <input type="file" name="attachments[]" multiple accept=".pdf,.jpg,.jpeg,.png,.gif,.webp,.doc,.docx,.xls,.xlsx,.txt,.csv" class="w-full px-3 py-2 border border-gray-300 rounded-2xl text-sm text-right file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:bg-gray-100 file:text-gray-700">
                <p class="mt-1 text-xs text-gray-500">
                    @if($isEdit)
                        اختياري — أضف مرفقات جديدة؛ تُحفظ مع الأمر (حتى 20 ملفاً، 10 ميجابايت لكل ملف).
                    @else
                        اختياري — حتى 20 ملفاً، بحد أقصى 10 ميجابايت لكل ملف.
                    @endif
                </p>
                @error('attachments')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                @error('attachments.*')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>
        </div>

        <div class="flex flex-wrap items-center gap-3 justify-end">
            <a href="{{ $isEdit ? route('purchases.orders.show', $editOrderModel) : route('purchases.orders.index') }}" class="px-5 py-2.5 rounded-2xl border border-gray-300 bg-white text-gray-700 text-sm font-medium hover:bg-gray-50 transition">إلغاء</a>
            <button type="submit" class="po-btn-save">{{ $isEdit ? 'حفظ التعديلات' : 'حفظ' }}</button>
        </div>
    </form>
</div>

@push('scripts')
<script>
document.addEventListener('alpine:init', () => {
    window.purchaseOrderCreateForm = function(items, suppliers, prefillLines, editPayload) {
        const defaultLine = () => ({
            item_id: '', description: '', quantity: 1, unit_price: 0, discount_percent: 0, tax_percent: 10
        });
        const buildLines = () => {
            if (editPayload && Array.isArray(editPayload.lines) && editPayload.lines.length) {
                return editPayload.lines.map((p) => ({
                    item_id: String(p.item_id),
                    description: p.description || '',
                    quantity: parseFloat(p.quantity) || 1,
                    unit_price: p.unit_price != null ? parseFloat(p.unit_price) : 0,
                    discount_percent: parseFloat(p.discount_percent) || 0,
                    tax_percent: p.tax_percent != null ? parseFloat(p.tax_percent) : 10,
                }));
            }
            const list = Array.isArray(prefillLines) ? prefillLines : [];
            if (!list.length) {
                return [defaultLine()];
            }
            return list.map((p) => ({
                item_id: String(p.item_id),
                description: p.description || '',
                quantity: parseFloat(p.quantity) || 1,
                unit_price: p.unit_price != null ? parseFloat(p.unit_price) : 0,
                discount_percent: 0,
                tax_percent: 10,
            }));
        };
        const today = new Date().toISOString().slice(0, 10);
        return {
            items: items || [],
            suppliers: suppliers || [],
            supplierId: (editPayload && editPayload.supplier_id != null) ? String(editPayload.supplier_id) : ('{{ old("supplier_id") }}' || ''),
            orderDate: (editPayload && editPayload.order_date) ? editPayload.order_date : ('{{ old("order_date", now()->format("Y-m-d")) }}' || today),
            expectedDeliveryDate: (editPayload && editPayload.expected_delivery_date) ? editPayload.expected_delivery_date : ('{{ old("expected_delivery_date") }}' || ''),
            shippingCost: (editPayload && editPayload.shipping_cost != null) ? Number(editPayload.shipping_cost) : {{ old('shipping_cost', 0) }},
            lines: buildLines(),
            onItemSelect(index) {
                const line = this.lines[index];
                if (!line || !line.item_id) return;
                const item = this.items.find(i => String(i.id) === String(line.item_id));
                if (item && item.cost != null) line.unit_price = item.cost;
            },
            addLine() { this.lines.push(defaultLine()); },
            removeLine(i) { if (this.lines.length > 1) this.lines.splice(i, 1); },
            lineTotal(line) {
                const q = parseFloat(line.quantity) || 0, p = parseFloat(line.unit_price) || 0;
                const d = parseFloat(line.discount_percent) || 0, t = parseFloat(line.tax_percent) || 0;
                const afterDiscount = q * p * (1 - d / 100);
                return afterDiscount * (1 + t / 100);
            },
            maxStockWarning(line) {
                if (!line.item_id) return false;
                const item = this.items.find(i => String(i.id) === String(line.item_id));
                if (!item || item.max_stock == null) return false;
                const qty = parseFloat(line.quantity) || 0;
                const current = parseFloat(item.total_stock) || 0;
                return (current + qty) > item.max_stock;
            },
            get subtotal() {
                return this.lines.reduce((sum, line) => sum + ((parseFloat(line.quantity) || 0) * (parseFloat(line.unit_price) || 0)), 0);
            },
            get totalDiscount() {
                return this.lines.reduce((sum, line) => {
                    const q = parseFloat(line.quantity) || 0, p = parseFloat(line.unit_price) || 0, d = parseFloat(line.discount_percent) || 0;
                    return sum + (q * p * d / 100);
                }, 0);
            },
            get totalTax() {
                return this.lines.reduce((sum, line) => {
                    const q = parseFloat(line.quantity) || 0, p = parseFloat(line.unit_price) || 0, d = parseFloat(line.discount_percent) || 0, t = parseFloat(line.tax_percent) || 0;
                    const afterDiscount = q * p * (1 - d / 100);
                    return sum + (afterDiscount * t / 100);
                }, 0);
            },
            get grandTotal() {
                return this.subtotal - this.totalDiscount + this.totalTax + (Number(this.shippingCost) || 0);
            }
        };
    };
});
</script>
@endpush
@endsection
