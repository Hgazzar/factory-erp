@extends('layouts.app')

@section('title', 'إضافة خط إنتاج - '.config('app.name'))

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h4 mb-1">إضافة خط إنتاج جديد</h1>
        <p class="text-muted mb-0 small">تعريف خط إنتاج رئيسي داخل المصنع</p>
    </div>
    <a href="{{ route('production-lines.index') }}" class="btn btn-outline-secondary">
        الرجوع لقائمة الخطوط
    </a>
</div>

<div class="card shadow-sm border-0">
    <div class="card-body p-4">
        <form action="{{ route('production-lines.store') }}" method="POST">
            @csrf

            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label fw-bold">كود الخط <span class="text-danger">*</span></label>
                    <input type="text" name="code" class="form-control @error('code') is-invalid @enderror"
                           value="{{ old('code') }}" required maxlength="30" placeholder="مثلاً: PL-01">
                    @error('code')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-bold">اسم الخط (عربي) <span class="text-danger">*</span></label>
                    <input type="text" name="name_ar" class="form-control @error('name_ar') is-invalid @enderror"
                           value="{{ old('name_ar') }}" required>
                    @error('name_ar')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-md-6">
                    <label class="form-label">اسم الخط (إنجليزي)</label>
                    <input type="text" name="name_en" class="form-control"
                           value="{{ old('name_en') }}">
                </div>
                <div class="col-md-6">
                    <label class="form-label">وصف مختصر</label>
                    <input type="text" name="description" class="form-control"
                           value="{{ old('description') }}">
                </div>
            </div>

            <hr class="my-4">

            <div class="d-flex gap-3">
                <button type="submit" class="btn btn-primary px-5">حفظ الخط</button>
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
        <div class="card-header bg-primary text-white text-center">إضافة خط إنتاج جديد</div>
        <div class="card-body">
            <form action="{{ route('production-lines.store') }}" method="POST">
                @csrf
                
                <div class="mb-3">
                    <label class="form-label">كود الخط</label>
                    <input type="text" name="code" class="form-control" required placeholder="مثال: PL-001">
                </div>

                <div class="mb-3">
                    <label class="form-label">اسم خط الإنتاج (بالعربي)</label>
                    <input type="text" name="name_ar" class="form-control" required placeholder="مثال: خط إنتاج العلب">
                </div>

                <div class="mb-3">
                    <label class="form-label">اسم خط الإنتاج (بالإنجليزي)</label>
                    <input type="text" name="name_en" class="form-control" placeholder="Example: Cans Line">
                </div>

                <button type="submit" class="btn btn-success w-100">حفظ البيانات</button>
            </form>
        </div>
    </div>
</div>
@endsection