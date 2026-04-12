@extends('layouts.app')

@section('title', 'تعديل خط إنتاج - Factory ERP')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h4 mb-1">تعديل خط الإنتاج</h1>
        <p class="text-muted mb-0 small">تحديث بيانات خط الإنتاج</p>
    </div>
    <a href="{{ route('production-lines.index') }}" class="btn btn-outline-secondary">
        الرجوع لقائمة الخطوط
    </a>
</div>

<div class="card shadow-sm border-0">
    <div class="card-body p-4">
        <form action="{{ route('production-lines.update', $line) }}" method="POST">
            @csrf
            @method('PUT')

            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label fw-bold">كود الخط <span class="text-danger">*</span></label>
                    <input type="text" name="code" class="form-control @error('code') is-invalid @enderror"
                           value="{{ old('code', $line->code) }}" required maxlength="30">
                    @error('code')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-bold">اسم الخط (عربي) <span class="text-danger">*</span></label>
                    <input type="text" name="name_ar" class="form-control @error('name_ar') is-invalid @enderror"
                           value="{{ old('name_ar', $line->name_ar) }}" required>
                    @error('name_ar')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-md-6">
                    <label class="form-label">اسم الخط (إنجليزي)</label>
                    <input type="text" name="name_en" class="form-control"
                           value="{{ old('name_en', $line->name_en) }}">
                </div>
                <div class="col-md-6">
                    <label class="form-label">وصف مختصر</label>
                    <input type="text" name="description" class="form-control"
                           value="{{ old('description', $line->description) }}">
                </div>
            </div>

            <hr class="my-4">

            <div class="d-flex gap-3">
                <button type="submit" class="btn btn-primary px-5">تحديث الخط</button>
                <a href="{{ route('production-lines.index') }}" class="btn btn-light border px-4">إلغاء</a>
            </div>
        </form>
    </div>
</div>
@endsection

@extends('layouts.app')

@section('content')
<div class="container">
    <div class="card mx-auto" style="max-width: 500px;">
        <div class="card-header bg-info text-white text-center">تعديل خط إنتاج: {{ $line->name_ar }}</div>
        <div class="card-body">
            <form action="{{ route('production-lines.update', $line->id) }}" method="POST">
                @csrf
                @method('PATCH')
                
                <div class="mb-3">
                    <label class="form-label">كود الخط</label>
                    <input type="text" name="code" class="form-control" value="{{ $line->code }}" required>
                </div>

                <div class="mb-3">
                    <label class="form-label">اسم خط الإنتاج (بالعربي)</label>
                    <input type="text" name="name_ar" class="form-control" value="{{ $line->name_ar }}" required>
                </div>

                <div class="mb-3">
                    <label class="form-label">حالة الخط</label>
                    <select name="is_active" class="form-select">
                        <option value="1" {{ $line->is_active ? 'selected' : '' }}>نشط</option>
                        <option value="0" {{ !$line->is_active ? 'selected' : '' }}>متوقف</option>
                    </select>
                </div>

                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-success flex-grow-1">تحديث البيانات</button>
                    <a href="{{ route('production-lines.index') }}" class="btn btn-secondary">إلغاء</a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection