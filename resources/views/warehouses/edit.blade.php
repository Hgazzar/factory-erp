@extends('layouts.app')

@section('title', 'تعديل مستودع - '.config('app.name'))

@section('breadcrumb')
    <a href="{{ route('dashboard') }}" class="text-gray-500 hover:text-indigo-600">الرئيسية</a>
    <span>›</span>
    <a href="{{ route('inventory.dashboard') }}" class="text-gray-500 hover:text-indigo-600">المخزون</a>
    <span>›</span>
    <a href="{{ route('warehouses.index') }}" class="text-gray-500 hover:text-indigo-600">المستودعات</a>
    <span>›</span>
    <span class="text-indigo-900 font-semibold">تعديل مستودع</span>
@endsection

@push('styles')
<style>
    .wh-create-card { background: #fff; border: 1px solid #e5e7eb; border-radius: .5rem; overflow: hidden; }
    .wh-create-card .card-header { background-color: #f9fafb; color: #374151; font-weight: 600; border-bottom: 1px solid #e5e7eb; padding: .75rem 1rem; }
    .wh-create-card .card-body { padding: 1.25rem; }
    .wh-create-card .form-control, .wh-create-card .form-select { border: 1px solid #e5e7eb; background: #f9fafb; color: #374151; border-radius: .5rem; font-size: .875rem; height: 2.5rem; }
    .wh-create-card .form-control:focus, .wh-create-card .form-select:focus { border-color: #2563eb; box-shadow: 0 0 0 .2rem rgba(37,99,235,.2); background: #f9fafb; }
    .wh-create-card textarea.form-control { min-height: 4rem; height: auto; }
    .wh-create-card .form-label { font-weight: 600; color: #374151; }
    .wh-create-btns .btn { border: 1px solid #e5e7eb; border-radius: .5rem; font-size: .875rem; height: 2.5rem; min-height: 2.5rem; padding: 0 1rem; display: inline-flex; align-items: center; justify-content: center; }
    .wh-create-btns .btn-primary { background: #2563eb; border-color: #2563eb; color: #fff; }
    .wh-create-btns .btn-outline-secondary { background: #fff; color: #374151; }
</style>
@endpush

@section('content')
<div dir="rtl" class="content-wrap">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h4 mb-1">تعديل: {{ $warehouse->name_ar }}</h1>
            <p class="text-muted mb-0 small">تحديث بيانات المستودع وفق الهوية الموحدة للنظام</p>
        </div>
        <a href="{{ route('warehouses.index') }}" class="btn btn-outline-secondary">الرجوع للقائمة</a>
    </div>

    <form action="{{ route('warehouses.update', $warehouse) }}" method="POST">
        @csrf
        @method('PUT')

        <div class="wh-create-card mb-4">
            <div class="card-header">المعلومات الأساسية</div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">اسم المستودع <span class="text-danger">*</span> <x-info field="inventory.wh_form_name_ar" /></label>
                        <input type="text" name="name_ar" class="form-control @error('name_ar') is-invalid @enderror" value="{{ old('name_ar', $warehouse->name_ar) }}" required>
                        @error('name_ar')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">الرمز <span class="text-danger">*</span> <x-info field="inventory.wh_form_code" /></label>
                        <input type="text" name="code" class="form-control @error('code') is-invalid @enderror" value="{{ old('code', $warehouse->code) }}" required maxlength="30">
                        @error('code')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">الاسم بالإنجليزية <x-info field="inventory.wh_form_name_en" /></label>
                        <input type="text" name="name_en" class="form-control" value="{{ old('name_en', $warehouse->name_en) }}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">الحالة <x-info field="inventory.wh_form_status" /></label>
                        <div class="d-flex align-items-center pt-2">
                            <input type="hidden" name="is_active" value="0">
                            <input type="checkbox" name="is_active" value="1" class="form-check-input" id="is_active" {{ old('is_active', $warehouse->is_active) ? 'checked' : '' }}>
                            <label class="form-check-label ms-2" for="is_active">نشط</label>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="wh-create-card mb-4">
            <div class="card-header">الموقع والتواصل</div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">المدينة <x-info field="inventory.wh_form_city" /></label>
                        <input type="text" name="city" class="form-control" value="{{ old('city', $warehouse->city) }}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">العنوان التفصيلي <x-info field="inventory.wh_form_address" /></label>
                        <input type="text" name="address" class="form-control" value="{{ old('address', $warehouse->address) }}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">المسؤول عن المستودع <x-info field="inventory.wh_form_manager" /></label>
                        <input type="text" name="manager" class="form-control" value="{{ old('manager', $warehouse->manager) }}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">رقم الهاتف <x-info field="inventory.wh_form_phone" /></label>
                        <input type="text" name="phone" class="form-control" value="{{ old('phone', $warehouse->phone) }}">
                    </div>
                </div>
            </div>
        </div>

        <div class="wh-create-card mb-4">
            <div class="card-header">الإعدادات</div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label d-block">المستودع الافتراضي <x-info field="inventory.wh_form_default" /></label>
                        <div class="d-flex align-items-center pt-2">
                            <input type="hidden" name="is_default" value="0">
                            <input type="checkbox" name="is_default" value="1" class="form-check-input" id="is_default" {{ old('is_default', $warehouse->is_default) ? 'checked' : '' }}>
                            <label class="form-check-label ms-2" for="is_default">تعيينه كمستودع افتراضي للعمليات الجديدة</label>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">الموقع على الخريطة / رابط جوجل ماب <x-info field="inventory.wh_form_map" /></label>
                        <input type="text" name="map_location" class="form-control" value="{{ old('map_location', $warehouse->map_location) }}">
                    </div>
                    <div class="col-12">
                        <label class="form-label">الوصف <x-info field="inventory.wh_form_description" /></label>
                        <textarea name="description" class="form-control" rows="2">{{ old('description', $warehouse->description) }}</textarea>
                    </div>
                </div>
            </div>
        </div>

        <div class="wh-create-btns d-flex justify-content-end gap-2">
            <a href="{{ route('warehouses.index') }}" class="btn btn-outline-secondary">إلغاء</a>
            <button type="submit" class="btn btn-primary">تحديث</button>
        </div>
    </form>
</div>
@endsection
