@extends('layouts.app')

@section('title', 'تعديل صنف - '.config('app.name'))

@php
    $unitSelectOptions = $units->map(fn ($u) => [
        'value' => (string) $u->id,
        'label' => $u->name_ar.' ('.$u->code.')',
    ])->values()->all();
    $typeSelectOptions = [
        ['value' => \App\Models\Item::TYPE_RAW_MATERIAL, 'label' => 'مادة خام'],
        ['value' => \App\Models\Item::TYPE_FINISHED_GOOD, 'label' => 'منتج تام'],
        ['value' => \App\Models\Item::TYPE_SERVICE, 'label' => 'خدمة'],
    ];
@endphp

@section('breadcrumb')
    <a href="{{ route('dashboard') }}" class="text-gray-500 hover:text-indigo-600">الرئيسية</a>
    <span>›</span>
    <a href="{{ route('inventory.dashboard') }}" class="text-gray-500 hover:text-indigo-600">المخزون</a>
    <span>›</span>
    <a href="{{ route('items.index') }}" class="text-gray-500 hover:text-indigo-600">المنتجات</a>
    <span>›</span>
    <span class="text-indigo-900 font-semibold">تعديل صنف</span>
@endsection

@section('content')
<div class="max-w-full space-y-6" dir="rtl">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">تعديل صنف</h1>
            <p class="mt-1 text-sm text-gray-500">{{ $item->name_ar }} — تحديث البيانات والمرفقات.</p>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            @if($item->type === \App\Models\Item::TYPE_FINISHED_GOOD)
                <a href="{{ route('items.show', $item) }}" class="inline-flex items-center justify-center rounded-lg border border-indigo-200 bg-indigo-50 px-4 py-2.5 text-sm font-semibold text-indigo-800 hover:bg-indigo-100">وصفة التصنيع (BOM)</a>
            @endif
            <a href="{{ route('items.index') }}" class="inline-flex items-center justify-center rounded-lg border border-gray-200 bg-white px-4 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50">رجوع للقائمة</a>
        </div>
    </div>

    <form action="{{ route('items.update', $item) }}" method="POST" enctype="multipart/form-data" class="space-y-6">
        @csrf
        @method('PUT')

        <section class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm sm:p-6">
            <h2 class="mb-5 border-b border-gray-100 pb-3 text-lg font-bold text-gray-900">البيانات الأساسية</h2>
            <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                <div>
                    <label class="mb-1.5 block text-sm font-semibold text-gray-800">كود الصنف <span class="text-red-600">*</span> <x-info field="inventory.item_code" /></label>
                    <input type="text" name="code" value="{{ old('code', $item->code) }}" required maxlength="50" class="w-full rounded-lg border border-gray-200 bg-white px-3 py-2.5 text-sm @error('code') border-red-500 ring-1 ring-red-200 @enderror">
                    @error('code')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-semibold text-gray-800">الاسم بالعربي <span class="text-red-600">*</span> <x-info field="inventory.item_name_ar" /></label>
                    <input type="text" name="name_ar" value="{{ old('name_ar', $item->name_ar) }}" required class="w-full rounded-lg border border-gray-200 bg-white px-3 py-2.5 text-sm @error('name_ar') border-red-500 ring-1 ring-red-200 @enderror">
                    @error('name_ar')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-semibold text-gray-800">الاسم بالإنجليزي</label>
                    <input type="text" name="name_en" value="{{ old('name_en', $item->name_en) }}" class="w-full rounded-lg border border-gray-200 bg-white px-3 py-2.5 text-sm @error('name_en') border-red-500 ring-1 ring-red-200 @enderror">
                    @error('name_en')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-semibold text-gray-800">الباركود <x-info field="inventory.item_barcode" /></label>
                    <input type="text" name="barcode" value="{{ old('barcode', $item->barcode) }}" placeholder="للمسح في تسجيل الإنتاج" class="w-full rounded-lg border border-gray-200 bg-white px-3 py-2.5 text-sm @error('barcode') border-red-500 ring-1 ring-red-200 @enderror">
                    @error('barcode')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-semibold text-gray-800">وحدة القياس <span class="text-red-600">*</span> <x-info field="inventory.item_unit" /></label>
                    <x-custom-select
                        name="unit_id"
                        id="item_edit_unit_id"
                        :options="$unitSelectOptions"
                        :value="old('unit_id', (string) $item->unit_id)"
                        :required="true"
                        :error="$errors->has('unit_id')"
                        :empty-option="false"
                        empty-label=""
                        placeholder="ابحث بوحدة أو الكود..."
                    />
                    @error('unit_id')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
                <div class="md:col-span-2">
                    <label class="mb-1.5 block text-sm font-semibold text-gray-800">الوصف <x-info field="inventory.item_description" /></label>
                    <textarea name="description" rows="3" class="w-full rounded-lg border border-gray-200 bg-white px-3 py-2.5 text-sm @error('description') border-red-500 ring-1 ring-red-200 @enderror">{{ old('description', $item->description) }}</textarea>
                    @error('description')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
            </div>
        </section>

        <section class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm sm:p-6">
            <h2 class="mb-5 border-b border-gray-100 pb-3 text-lg font-bold text-gray-900">التصنيف والتكلفة والمورد</h2>
            <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
                <div>
                    <label class="mb-1.5 block text-sm font-semibold text-gray-800">نوع الصنف <span class="text-red-600">*</span> <x-info field="inventory.item_category" /></label>
                    <x-custom-select
                        name="type"
                        id="item_edit_type"
                        :options="$typeSelectOptions"
                        :value="old('type', $item->type)"
                        :required="true"
                        :error="$errors->has('type')"
                        :empty-option="false"
                        empty-label=""
                        placeholder="اختر النوع"
                        :searchable="false"
                    />
                    @error('type')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-semibold text-gray-800">الحد الأدنى للمخزون <x-info field="inventory.item_reorder_level" /></label>
                    <input type="number" inputmode="decimal" name="min_stock" value="{{ old('min_stock', $item->min_stock) }}" min="0" step="any" class="w-full rounded-lg border border-gray-200 bg-white px-3 py-2.5 text-sm tabular-nums @error('min_stock') border-red-500 ring-1 ring-red-200 @enderror">
                    @error('min_stock')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-semibold text-gray-800">التكلفة (متوسط مرجح) <x-info field="inventory.item_cost_price" /></label>
                    <input type="text" readonly
                        value="SAR {{ number_format((float) ($item->cost ?? 0), 4) }}"
                        class="w-full cursor-not-allowed rounded-lg border border-gray-200 bg-gray-100 px-3 py-2.5 text-sm tabular-nums text-gray-700"
                        title="يُحدَّث تلقائياً من حركات المخزون (WAC)">
                    <p class="mt-1 text-xs text-gray-500">لا يمكن تعديل التكلفة يدوياً؛ تُحسب من الإضافة والتصنيع.</p>
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-semibold text-gray-800">المورد</label>
                    <input type="text" name="supplier" value="{{ old('supplier', $item->supplier) }}" class="w-full rounded-lg border border-gray-200 bg-white px-3 py-2.5 text-sm @error('supplier') border-red-500 ring-1 ring-red-200 @enderror">
                    @error('supplier')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-semibold text-gray-800">نوع الخامة</label>
                    <input type="text" name="material_type" value="{{ old('material_type', $item->material_type) }}" class="w-full rounded-lg border border-gray-200 bg-white px-3 py-2.5 text-sm @error('material_type') border-red-500 ring-1 ring-red-200 @enderror">
                    @error('material_type')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
                <div class="flex items-end">
                    <label class="inline-flex cursor-pointer items-center gap-3 rounded-lg border border-gray-200 bg-gray-50/80 px-4 py-3">
                        <input type="hidden" name="is_active" value="0">
                        <input type="checkbox" name="is_active" value="1" class="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500" id="item_edit_is_active" {{ old('is_active', $item->is_active) ? 'checked' : '' }}>
                        <span class="text-sm font-semibold text-gray-800">نشط <x-info field="inventory.item_is_active" /></span>
                    </label>
                </div>
            </div>
        </section>

        <section class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm sm:p-6">
            <h2 class="mb-5 border-b border-gray-100 pb-3 text-lg font-bold text-gray-900">المعاينة والمرفقات</h2>
            <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
                <div>
                    <label class="mb-1.5 block text-sm font-semibold text-gray-800">معاينة القائمة <x-info field="inventory.item_image" /></label>
                    <div class="flex min-h-[8rem] items-center justify-center overflow-hidden rounded-lg border border-gray-200 bg-gray-50 p-4">
                        @php $thumb = $item->catalogThumbUrl(); @endphp
                        @if($thumb)
                            <img src="{{ $thumb }}" alt="{{ $item->name_ar }}" class="max-h-28 max-w-full object-contain">
                        @else
                            <span class="text-sm text-gray-500">لا توجد صورة في المرفقات بعد.</span>
                        @endif
                    </div>
                </div>
                <div>
                    <x-attachment-handler
                        hint-field="inventory.item_attachments"
                        title="مرفقات الصنف"
                        :existing="$item->attachments"
                        :allow-delete="true"
                        help-text="إضافة ملفات جديدة دون حذف المرفقات الحالية. أول صورة تُستخدم كمعاينة في القائمة."
                    />
                </div>
            </div>
        </section>

        <div class="flex flex-wrap items-center gap-3">
            <button type="submit" class="inline-flex items-center justify-center rounded-lg bg-indigo-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-indigo-700">حفظ التعديلات</button>
            <a href="{{ route('items.index') }}" class="inline-flex items-center justify-center rounded-lg border border-gray-200 bg-white px-5 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50">إلغاء</a>
        </div>
    </form>
</div>
@endsection
