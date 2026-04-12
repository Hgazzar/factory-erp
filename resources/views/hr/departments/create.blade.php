@extends('layouts.app')

@section('title', 'قسم جديد - MIRADA ERP')

@section('breadcrumb')
    <a href="{{ route('dashboard') }}" class="text-gray-500 hover:text-indigo-600">الرئيسية</a>
    <span>›</span>
    <a href="{{ route('hr.departments.index') }}" class="text-gray-500 hover:text-indigo-600">الأقسام</a>
    <span>›</span>
    <span class="text-indigo-900 font-semibold truncate">جديد</span>
@endsection

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h4 mb-0">إضافة قسم</h1>
    <a href="{{ route('hr.departments.index') }}" class="btn btn-outline-secondary rounded-lg">رجوع</a>
</div>

<div class="card shadow-sm border-0 rounded-lg">
    <div class="card-body p-4">
        <form method="POST" action="{{ route('hr.departments.store') }}">
            @csrf
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label fw-semibold">اسم القسم <span class="text-danger">*</span> <x-info field="hr.dept_name" /></label>
                    <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name') }}" required maxlength="255">
                    @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label">مدير القسم <x-info field="hr.dept_manager" /></label>
                    <select name="manager_id" class="form-select @error('manager_id') is-invalid @enderror">
                        <option value="">— بدون —</option>
                        @foreach($employees as $emp)
                            <option value="{{ $emp->id }}" @selected(old('manager_id') == $emp->id)>{{ $emp->name }} ({{ $emp->code }})</option>
                        @endforeach
                    </select>
                    @error('manager_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
            </div>
            <hr class="my-4">
            <button type="submit" class="btn btn-primary rounded-lg px-5">حفظ</button>
        </form>
    </div>
</div>
@endsection
