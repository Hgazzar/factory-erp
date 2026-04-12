@extends('layouts.app')

@section('title', 'تعديل ماكينة - Factory ERP')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h4 mb-1">تعديل بيانات الماكينة</h1>
        <p class="text-muted mb-0 small">تحديث بيانات الماكينة وخط الإنتاج المرتبطة به</p>
    </div>
    <a href="{{ route('machines.index') }}" class="btn btn-outline-secondary">
        الرجوع لقائمة الماكينات
    </a>
</div>

<div class="card shadow-sm border-0">
    <div class="card-body p-4">
        <form action="{{ route('machines.update', $machine) }}" method="POST">
            @csrf
            @method('PUT')

            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label fw-bold">كود الماكينة <span class="text-danger">*</span></label>
                    <input type="text" name="code" class="form-control @error('code') is-invalid @enderror"
                           value="{{ old('code', $machine->code) }}" required maxlength="30">
                    @error('code')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-bold">اسم الماكينة (عربي) <span class="text-danger">*</span></label>
                    <input type="text" name="name_ar" class="form-control @error('name_ar') is-invalid @enderror"
                           value="{{ old('name_ar', $machine->name_ar) }}" required>
                    @error('name_ar')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-md-6">
                    <label class="form-label">اسم الماكينة (إنجليزي)</label>
                    <input type="text" name="name_en" class="form-control"
                           value="{{ old('name_en', $machine->name_en) }}">
                </div>
                <div class="col-md-6">
                    <label class="form-label">خط الإنتاج</label>
                    <select name="production_line_id" class="form-select @error('production_line_id') is-invalid @enderror">
                        <option value="">-- بدون تحديد --</option>
                        @foreach($productionLines as $line)
                            <option value="{{ $line->id }}" {{ old('production_line_id', $machine->production_line_id) == $line->id ? 'selected' : '' }}>
                                {{ $line->name_ar }} ({{ $line->code }})
                            </option>
                        @endforeach
                    </select>
                    @error('production_line_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-md-6">
                    <label class="form-label">الحالة</label>
                    <select name="status" class="form-select">
                        <option value="active" {{ old('status', $machine->status) === 'active' ? 'selected' : '' }}>نشطة</option>
                        <option value="maintenance" {{ old('status', $machine->status) === 'maintenance' ? 'selected' : '' }}>صيانة</option>
                        <option value="inactive" {{ old('status', $machine->status) === 'inactive' ? 'selected' : '' }}>متوقفة</option>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">ملاحظات</label>
                    <textarea name="description" class="form-control" rows="2">{{ old('description', $machine->description) }}</textarea>
                </div>
            </div>

            <hr class="my-4">

            <div class="d-flex gap-3">
                <button type="submit" class="btn btn-primary px-5">تحديث الماكينة</button>
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
        <div class="card-header bg-info text-white text-center">تعديل الماكينة: {{ $machine->name_ar }}</div>
        <div class="card-body">
            <form action="{{ route('machines.update', $machine->id) }}" method="POST">
                @csrf
                @method('PATCH')
                
                <div class="mb-3">
                    <label class="form-label">كود الماكينة</label>
                    <input type="text" name="code" class="form-control" value="{{ $machine->code }}" required>
                </div>

                <div class="mb-3">
                    <label class="form-label">اسم الماكينة (بالعربي)</label>
                    <input type="text" name="name_ar" class="form-control" value="{{ $machine->name_ar }}" required>
                </div>

                <div class="mb-3">
                    <label class="form-label">خط الإنتاج</label>
                    <select name="production_line_id" class="form-select" required>
                        @foreach($productionLines as $line)
                            <option value="{{ $line->id }}" {{ $machine->production_line_id == $line->id ? 'selected' : '' }}>
                                {{ $line->name_ar }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label">الحالة الفنية</label>
                    <select name="status" class="form-select">
                        <option value="active" {{ $machine->status == 'active' ? 'selected' : '' }}>تعمل (Active)</option>
                        <option value="maintenance" {{ $machine->status == 'maintenance' ? 'selected' : '' }}>صيانة (Maintenance)</option>
                        <option value="inactive" {{ $machine->status == 'inactive' ? 'selected' : '' }}>متوقفة (Inactive)</option>
                    </select>
                </div>

                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-success flex-grow-1">تحديث البيانات</button>
                    <a href="{{ route('machines.index') }}" class="btn btn-secondary">إلغاء</a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection