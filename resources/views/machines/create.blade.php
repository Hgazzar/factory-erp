@extends('layouts.app')

@section('title', 'إضافة ماكينة - '.config('app.name'))

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h4 mb-1">إضافة ماكينة جديدة</h1>
        <p class="text-muted mb-0 small">تعريف ماكينة وربطها بخط إنتاج (اختياري)</p>
    </div>
    <a href="{{ route('machines.index') }}" class="btn btn-outline-secondary">
        الرجوع لقائمة الماكينات
    </a>
</div>

<div class="card shadow-sm border-0">
    <div class="card-body p-4">
        <form action="{{ route('machines.store') }}" method="POST">
            @csrf

            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label fw-bold">كود الماكينة <span class="text-danger">*</span></label>
                    <input type="text" name="code" class="form-control @error('code') is-invalid @enderror"
                           value="{{ old('code') }}" required maxlength="30" placeholder="مثلاً: MC-01">
                    @error('code')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-bold">اسم الماكينة (عربي) <span class="text-danger">*</span></label>
                    <input type="text" name="name_ar" class="form-control @error('name_ar') is-invalid @enderror"
                           value="{{ old('name_ar') }}" required>
                    @error('name_ar')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-md-6">
                    <label class="form-label">اسم الماكينة (إنجليزي)</label>
                    <input type="text" name="name_en" class="form-control"
                           value="{{ old('name_en') }}">
                </div>
                <div class="col-md-6">
                    <label class="form-label">خط الإنتاج</label>
                    <select name="production_line_id" class="form-select @error('production_line_id') is-invalid @enderror">
                        <option value="">-- بدون تحديد --</option>
                        @foreach($productionLines as $line)
                            <option value="{{ $line->id }}" {{ old('production_line_id') == $line->id ? 'selected' : '' }}>
                                {{ $line->name_ar }} ({{ $line->code }})
                            </option>
                        @endforeach
                    </select>
                    @error('production_line_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-md-6">
                    <label class="form-label">الحالة</label>
                    <select name="status" class="form-select">
                        <option value="active" {{ old('status', 'active') === 'active' ? 'selected' : '' }}>نشطة</option>
                        <option value="maintenance" {{ old('status') === 'maintenance' ? 'selected' : '' }}>صيانة</option>
                        <option value="inactive" {{ old('status') === 'inactive' ? 'selected' : '' }}>متوقفة</option>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">ملاحظات</label>
                    <textarea name="description" class="form-control" rows="2">{{ old('description') }}</textarea>
                </div>
            </div>

            <hr class="my-4">

            <div class="d-flex gap-3">
                <button type="submit" class="btn btn-primary px-5">حفظ الماكينة</button>
                <a href="{{ route('machines.index') }}" class="btn btn-light border px-4">إلغاء</a>
            </div>
        </form>
    </div>
</div>
@endsection

@extends('layouts.app')

@section('content')
<div class="container">
    <div class="card mx-auto" style="max-width: 600px;">
        <div class="card-header bg-primary text-white text-center">إضافة ماكينة جديدة</div>
        <div class="card-body">
            <form action="{{ route('machines.store') }}" method="POST">
                @csrf
                
                <div class="mb-3">
                    <label class="form-label">كود الماكينة</label>
                    <input type="text" name="code" class="form-control" placeholder="مثال: MAC-001" required>
                </div>

                <div class="mb-3">
                    <label class="form-label">اسم الماكينة (بالعربي)</label>
                    <input type="text" name="name_ar" class="form-control" placeholder="مثال: ماكينة تعبئة" required>
                </div>

                <div class="mb-3">
                    <label class="form-label">تابعة لخط إنتاج:</label>
                    <select name="production_line_id" class="form-select" required>
                        <option value="">-- اختر خط الإنتاج --</option>
                        @foreach($productionLines as $line)
                            <option value="{{ $line->id }}">{{ $line->name_ar }} ({{ $line->code }})</option>
                        @endforeach
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label">حالة الماكينة</label>
                    <select name="status" class="form-select">
                        <option value="active">تعمل (Active)</option>
                        <option value="maintenance">صيانة (Maintenance)</option>
                        <option value="inactive">متوقفة (Inactive)</option>
                    </select>
                </div>

                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-success flex-grow-1">حفظ الماكينة</button>
                    <a href="{{ route('machines.index') }}" class="btn btn-secondary">رجوع</a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection