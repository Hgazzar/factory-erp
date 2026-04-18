@extends('layouts.app')

@section('title', 'تعديل صنف - MIRADA ERP')

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
<div dir="rtl" class="content-wrap">
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0">تعديل صنف: {{ $item->name_ar }}</h1>
    <div class="d-flex gap-2">
        @if($item->type === \App\Models\Item::TYPE_FINISHED_GOOD)
            <a href="{{ route('items.show', $item) }}" class="btn btn-outline-primary">وصفة التصنيع (BOM)</a>
        @endif
        <a href="{{ route('items.index') }}" class="btn btn-outline-secondary">رجوع</a>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <form action="{{ route('items.update', $item) }}" method="POST" enctype="multipart/form-data">
            @csrf
            @method('PUT')

            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">كود الصنف <span class="text-danger">*</span></label>
                    <input type="text" name="code" class="form-control @error('code') is-invalid @enderror" value="{{ old('code', $item->code) }}" required maxlength="50">
                    @error('code')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label">الاسم بالعربي <span class="text-danger">*</span></label>
                    <input type="text" name="name_ar" class="form-control @error('name_ar') is-invalid @enderror" value="{{ old('name_ar', $item->name_ar) }}" required>
                    @error('name_ar')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label">الاسم بالإنجليزي</label>
                    <input type="text" name="name_en" class="form-control @error('name_en') is-invalid @enderror" value="{{ old('name_en', $item->name_en) }}">
                    @error('name_en')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label">الباركود</label>
                    <input type="text" name="barcode" class="form-control @error('barcode') is-invalid @enderror" value="{{ old('barcode', $item->barcode) }}" placeholder="للمسح في تسجيل الإنتاج">
                    @error('barcode')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label">وحدة القياس <span class="text-danger">*</span></label>
                    <select name="unit_id" class="form-select @error('unit_id') is-invalid @enderror" required>
                        @foreach($units as $unit)
                            <option value="{{ $unit->id }}" {{ old('unit_id', $item->unit_id) == $unit->id ? 'selected' : '' }}>{{ $unit->name_ar }} ({{ $unit->code }})</option>
                        @endforeach
                    </select>
                    @error('unit_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-12">
                    <label class="form-label">الوصف</label>
                    <textarea name="description" class="form-control @error('description') is-invalid @enderror" rows="2">{{ old('description', $item->description) }}</textarea>
                    @error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-4">
                    <label class="form-label">نوع الصنف <span class="text-danger">*</span></label>
                    <select name="type" class="form-select @error('type') is-invalid @enderror" required>
                        <option value="raw_material" {{ old('type', $item->type) === 'raw_material' ? 'selected' : '' }}>مادة خام</option>
                        <option value="finished_good" {{ old('type', $item->type) === 'finished_good' ? 'selected' : '' }}>منتج تام</option>
                        <option value="service" {{ old('type', $item->type) === 'service' ? 'selected' : '' }}>خدمة</option>
                    </select>
                    @error('type')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-4">
                    <label class="form-label">الحد الأدنى للمخزون</label>
                    <input type="number" inputmode="decimal" name="min_stock" class="form-control @error('min_stock') is-invalid @enderror" value="{{ old('min_stock', $item->min_stock) }}" min="0" step="any">
                    @error('min_stock')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-4">
                    <label class="form-label">التكلفة</label>
                    <input type="number" inputmode="decimal" name="cost" class="form-control @error('cost') is-invalid @enderror" value="{{ old('cost', $item->cost) }}" min="0" step="any">
                    @error('cost')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label">المورد</label>
                    <input type="text" name="supplier" class="form-control @error('supplier') is-invalid @enderror" value="{{ old('supplier', $item->supplier) }}">
                    @error('supplier')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label">نوع الخامة</label>
                    <input type="text" name="material_type" class="form-control @error('material_type') is-invalid @enderror" value="{{ old('material_type', $item->material_type) }}">
                    @error('material_type')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-12">
                    <div class="form-check">
                        <input type="hidden" name="is_active" value="0">
                        <input type="checkbox" name="is_active" value="1" class="form-check-input" id="is_active" {{ old('is_active', $item->is_active) ? 'checked' : '' }}>
                        <label class="form-check-label" for="is_active">نشط</label>
                    </div>
                </div>
            </div>

            <hr>
            <div class="row g-3 mb-3">
                <div class="col-md-6">
                    <label class="form-label d-flex align-items-center gap-1">معاينة القائمة <x-info field="inventory.item_image" /></label>
                    <div class="border rounded p-2 bg-light d-flex align-items-center justify-content-center" style="min-height: 120px;">
                        @php $thumb = $item->catalogThumbUrl(); @endphp
                        @if($thumb)
                            <img src="{{ $thumb }}" alt="{{ $item->name_ar }}" style="max-height: 100px; max-width: 100%; object-fit: contain;">
                        @else
                            <span class="text-muted small">لا توجد صورة في المرفقات بعد.</span>
                        @endif
                    </div>
                </div>
                <div class="col-md-6">
                    <x-attachment-handler
                        theme="bootstrap"
                        hint-field="inventory.item_attachments"
                        title="مرفقات الصنف"
                        :existing="$item->attachments"
                        :allow-delete="true"
                        help-text="إضافة ملفات جديدة دون حذف المرفقات الحالية. أول صورة تُستخدم كمعاينة في القائمة."
                    />
                </div>
            </div>

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary">حفظ التعديلات</button>
                <a href="{{ route('items.index') }}" class="btn btn-outline-secondary">إلغاء</a>
            </div>
        </form>
    </div>
</div>
</div>
@endsection
