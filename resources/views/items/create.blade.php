@extends('layouts.app')

@section('title', 'منتج جديد - MIRADA ERP')

@section('breadcrumb')
    <a href="{{ route('dashboard') }}" class="text-gray-500 hover:text-indigo-600">الرئيسية</a>
    <span>›</span>
    <a href="{{ route('inventory.dashboard') }}" class="text-gray-500 hover:text-indigo-600">المخزون</a>
    <span>›</span>
    <a href="{{ route('items.index') }}" class="text-gray-500 hover:text-indigo-600">المنتجات</a>
    <span>›</span>
    <span class="text-indigo-900 font-semibold">إضافة صنف</span>
@endsection

@push('styles')
<style>
    .item-create-card {
        background: #fff;
        border: 1px solid #e5e7eb;
        border-radius: 0.5rem;
        overflow: hidden;
    }
    .item-create-card .card-header {
        background-color: #f9fafb;
        color: #374151;
        font-weight: 600;
        border-bottom: 1px solid #e5e7eb;
        padding: 0.75rem 1rem;
    }
    .item-create-card .card-body { padding: 1.25rem; }
    .item-create-card .form-control,
    .item-create-card .form-select {
        border: 1px solid #e5e7eb;
        background-color: #f9fafb;
        color: #374151;
        border-radius: 0.5rem;
        font-size: 0.875rem;
        height: 2.5rem;
        min-height: 2.5rem;
    }
    .item-create-card .form-control:focus,
    .item-create-card .form-select:focus {
        border-color: #ea580c;
        box-shadow: 0 0 0 0.2rem rgba(234, 88, 12, 0.15);
        background-color: #f9fafb;
    }
    .item-create-card textarea.form-control {
        min-height: 5rem;
        height: auto;
    }
    .item-create-card .form-label { font-weight: 600; color: #374151; }
    .item-create-btns .btn {
        border: 1px solid #e5e7eb;
        border-radius: 0.5rem;
        font-size: 0.875rem;
        height: 2.5rem;
        min-height: 2.5rem;
        padding: 0 1rem;
        display: inline-flex;
        align-items: center;
        justify-content: center;
    }
    .item-create-btns .btn-primary { border-color: #2563eb; background-color: #2563eb; color: #fff; }
    .item-create-btns .btn-outline-secondary { background-color: #fff; color: #374151; }
    .barcode-wrap { display: flex; gap: 0.5rem; align-items: stretch; }
    .barcode-wrap .form-control { flex: 1; }
    .img-preview-wrap {
        border: 1px solid #e5e7eb;
        border-radius: 0.5rem;
        background: #f9fafb;
        min-height: 140px;
        display: flex;
        align-items: center;
        justify-content: center;
        overflow: hidden;
    }
    .img-preview-wrap img { max-width: 100%; max-height: 200px; object-fit: contain; }
</style>
@endpush

@section('content')
<div dir="rtl" class="content-wrap">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h4 mb-1">منتج جديد</h1>
            <p class="text-muted mb-0 small">تسجيل بيانات الصنف وربطه بالمستودع الافتراضي والرصيد الافتتاحي</p>
        </div>
        <a href="{{ route('items.index') }}" class="btn btn-outline-secondary">الرجوع للقائمة</a>
    </div>

    <form action="{{ route('items.store') }}" method="POST" enctype="multipart/form-data" id="itemCreateForm">
        @csrf

        <div class="item-create-card mb-4">
            <div class="card-header">المعلومات الأساسية</div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">اسم المنتج <span class="text-danger">*</span> <x-info field="inventory.item_name_ar" /></label>
                        <input type="text" name="name_ar" class="form-control @error('name_ar') is-invalid @enderror" value="{{ old('name_ar') }}" required placeholder="الاسم بالعربية">
                        @error('name_ar')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">الرمز (SKU) <span class="text-danger">*</span> <x-info field="inventory.item_code" /></label>
                        <input type="text" name="code" class="form-control @error('code') is-invalid @enderror" value="{{ old('code') }}" required maxlength="50" placeholder="مثلاً: ITEM-100">
                        @error('code')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">الاسم بالإنجليزي</label>
                        <input type="text" name="name_en" class="form-control" value="{{ old('name_en') }}" placeholder="Item name">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">الباركود <x-info field="inventory.item_barcode" /></label>
                        <div class="barcode-wrap">
                            <input type="text" name="barcode" id="itemBarcode" class="form-control @error('barcode') is-invalid @enderror" value="{{ old('barcode') }}" maxlength="100" placeholder="للمسح أو توليد تلقائي">
                            <button type="button" class="btn btn-outline-secondary" id="barcodeGenerate">توليد</button>
                        </div>
                        @error('barcode')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">التصنيف <span class="text-danger">*</span> <x-info field="inventory.item_category" /></label>
                        <select name="type" class="form-select @error('type') is-invalid @enderror" required>
                            <option value="raw_material" {{ old('type') === 'raw_material' ? 'selected' : '' }}>مواد خام</option>
                            <option value="finished_good" {{ old('type', 'finished_good') === 'finished_good' ? 'selected' : '' }}>منتج تام</option>
                            <option value="service" {{ old('type') === 'service' ? 'selected' : '' }}>خدمة</option>
                        </select>
                        @error('type')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">وحدة القياس <span class="text-danger">*</span> <x-info field="inventory.item_unit" /></label>
                        <select name="unit_id" class="form-select @error('unit_id') is-invalid @enderror" required>
                            <option value="">-- اختر الوحدة --</option>
                            @foreach($units as $unit)
                                <option value="{{ $unit->id }}" {{ old('unit_id') == $unit->id ? 'selected' : '' }}>{{ $unit->name_ar }} ({{ $unit->code }})</option>
                            @endforeach
                        </select>
                        @error('unit_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-12">
                        <label class="form-label">المورد الرئيسي</label>
                        <input type="text" name="supplier" class="form-control" value="{{ old('supplier') }}" placeholder="اختياري">
                    </div>
                </div>
            </div>
        </div>

        <div class="item-create-card mb-4">
            <div class="card-header">التسعير والمخزون</div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">سعر التكلفة <x-info field="inventory.item_cost_price" /></label>
                        <input type="number" inputmode="decimal" name="cost" class="form-control @error('cost') is-invalid @enderror" value="{{ old('cost') }}" min="0" step="any" placeholder="0.00">
                        @error('cost')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">سعر البيع <x-info field="inventory.item_sale_price" /></label>
                        <input type="number" inputmode="decimal" name="selling_price" class="form-control @error('selling_price') is-invalid @enderror" value="{{ old('selling_price') }}" min="0" step="any" placeholder="0.00">
                        @error('selling_price')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">حد إعادة الطلب <x-info field="inventory.item_reorder_level" /></label>
                        <input type="number" inputmode="decimal" name="min_stock" class="form-control @error('min_stock') is-invalid @enderror" value="{{ old('min_stock', 0) }}" min="0" step="any">
                        @error('min_stock')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">كمية المخزون الافتتاحية <x-info field="inventory.item_initial_stock" /></label>
                        <input type="number" inputmode="decimal" name="initial_quantity" class="form-control @error('initial_quantity') is-invalid @enderror" value="{{ old('initial_quantity', 0) }}" min="0" step="any">
                        @error('initial_quantity')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-12">
                        <label class="form-label">المستودع الافتراضي <span class="text-danger">*</span> <x-info field="inventory.item_default_warehouse" /></label>
                        <select name="warehouse_id" class="form-select @error('warehouse_id') is-invalid @enderror" required>
                            <option value="">-- اختر المستودع --</option>
                            @foreach($warehouses as $warehouse)
                                <option value="{{ $warehouse->id }}" {{ old('warehouse_id') == $warehouse->id ? 'selected' : '' }}>{{ $warehouse->name_ar }}</option>
                            @endforeach
                        </select>
                        @error('warehouse_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>
            </div>
        </div>

        <div class="item-create-card mb-4">
            <div class="card-header">الوصف والصورة</div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">الوصف <x-info field="inventory.item_description" /></label>
                        <textarea name="description" class="form-control @error('description') is-invalid @enderror" rows="3" placeholder="ملاحظات أو وصف تفصيلي">{{ old('description') }}</textarea>
                        @error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">صورة المنتج <x-info field="inventory.item_image" /></label>
                        <input type="file" name="image" id="itemImage" class="form-control form-control-sm" accept="image/*">
                        <div class="img-preview-wrap mt-2" id="imagePreviewWrap">
                            <span class="text-muted small">معاينة الصورة تظهر هنا</span>
                        </div>
                    </div>
                    <div class="col-12">
                        <div class="form-check form-switch">
                            <input type="hidden" name="is_active" value="0">
                            <input type="checkbox" name="is_active" value="1" class="form-check-input" id="is_active" {{ old('is_active', true) ? 'checked' : '' }}>
                            <label class="form-check-label fw-semibold" for="is_active">تفعيل الصنف في النظام <x-info field="inventory.item_is_active" /></label>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="item-create-btns d-flex justify-content-end gap-2">
            <a href="{{ route('items.index') }}" class="btn btn-outline-secondary">إلغاء</a>
            <button type="submit" class="btn btn-primary" id="itemCreateSubmit">
                <span class="submit-label">حفظ</span>
                <span class="submit-spinner d-none ms-2" aria-hidden="true">
                    <span class="spinner-border spinner-border-sm" role="status"></span>
                </span>
            </button>
        </div>
    </form>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    var barcodeInput = document.getElementById('itemBarcode');
    var genBtn = document.getElementById('barcodeGenerate');
    if (genBtn && barcodeInput) {
        genBtn.addEventListener('click', function () {
            var s = '0123456789';
            var out = '';
            for (var i = 0; i < 13; i++) out += s.charAt(Math.floor(Math.random() * s.length));
            barcodeInput.value = out;
        });
    }
    var fileInput = document.getElementById('itemImage');
    var previewWrap = document.getElementById('imagePreviewWrap');
    if (fileInput && previewWrap) {
        fileInput.addEventListener('change', function () {
            var f = this.files[0];
            previewWrap.innerHTML = '';
            if (f && f.type.indexOf('image') === 0) {
                var img = new Image();
                img.onload = function () { previewWrap.appendChild(img); };
                img.src = URL.createObjectURL(f);
            } else {
                previewWrap.innerHTML = '<span class="text-muted small">معاينة الصورة تظهر هنا</span>';
            }
        });
    }

    var form = document.getElementById('itemCreateForm');
    var submitBtn = document.getElementById('itemCreateSubmit');
    if (form && submitBtn) {
        form.addEventListener('submit', function () {
            if (submitBtn.disabled) {
                return;
            }
            submitBtn.disabled = true;
            submitBtn.classList.add('disabled');
            var label = submitBtn.querySelector('.submit-label');
            var spinner = submitBtn.querySelector('.submit-spinner');
            if (label) {
                label.textContent = 'جاري الحفظ...';
            }
            if (spinner) {
                spinner.classList.remove('d-none');
            }
        });
    }
});
</script>
@endpush
@endsection
