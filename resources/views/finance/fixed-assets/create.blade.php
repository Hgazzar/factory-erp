@extends('layouts.app')

@section('title', isset($asset) ? 'تعديل أصل - MIRADA ERP' : 'أصل جديد - MIRADA ERP')

@section('breadcrumb')
    <a href="{{ route('dashboard') }}" class="text-gray-500 hover:text-blue-600">الرئيسية</a>
    <span class="mx-1 text-gray-400">›</span>
    <a href="{{ route('finance.dashboard') }}" class="text-gray-500 hover:text-blue-600">المحاسبة</a>
    <span class="mx-1 text-gray-400">›</span>
    <a href="{{ route('finance.fixed-assets.index') }}" class="text-gray-500 hover:text-blue-600">الأصول الثابتة</a>
    <span class="mx-1 text-gray-400">›</span>
    <span class="text-blue-900 font-semibold">{{ isset($asset) ? 'تعديل أصل' : 'أصل جديد' }}</span>
@endsection

@section('content')
<div dir="rtl" class="mx-auto w-full max-w-full space-y-6">
    <header class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <h1 class="text-2xl font-bold text-gray-900">{{ isset($asset) ? 'تعديل أصل' : 'أصل جديد' }}</h1>
            <a href="{{ route('finance.fixed-assets.index') }}" class="inline-flex items-center rounded-lg border border-gray-200 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">رجوع</a>
        </div>
    </header>

    <form method="POST" action="{{ isset($asset) ? route('finance.fixed-assets.update', $asset) : route('finance.fixed-assets.store') }}" class="space-y-6">
        @csrf
        @if(isset($asset))
            @method('PUT')
        @endif

        <section class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm">
            <h2 class="mb-4 flex items-center gap-2 text-lg font-bold text-gray-900">
                المعلومات الأساسية
                <x-info field="asset_code" />
            </h2>
            <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                <div>
                    <label class="mb-1 flex items-center gap-1 text-sm font-medium text-gray-700">رمز الأصل <x-info field="asset_code" /> <span class="text-red-500">*</span></label>
                    <input type="text" name="asset_code" value="{{ old('asset_code', $asset->asset_code ?? '') }}" required class="h-10 w-full rounded-lg border border-gray-200 bg-gray-50 px-3 text-sm focus:border-blue-500 focus:ring-blue-500">
                    @error('asset_code')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="mb-1 flex items-center gap-1 text-sm font-medium text-gray-700">التصنيف <x-info field="asset_category" /> <span class="text-red-500">*</span></label>
                    <select name="category_id" required class="h-10 w-full rounded-lg border border-gray-200 bg-gray-50 px-3 text-sm focus:border-blue-500 focus:ring-blue-500">
                        <option value="">اختر التصنيف</option>
                        @foreach($categories as $category)
                            <option value="{{ $category->id }}" @selected((string) old('category_id') === (string) $category->id)>
                                {{ $category->code }} - {{ $category->name_ar }}
                            </option>
                        @endforeach
                    </select>
                    @error('category_id')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="mb-1 flex items-center gap-1 text-sm font-medium text-gray-700">مركز التكلفة <x-info field="cost_center" /> <span class="text-red-500">*</span></label>
                    <select name="cost_center_id" required class="h-10 w-full rounded-lg border border-gray-200 bg-gray-50 px-3 text-sm focus:border-blue-500 focus:ring-blue-500">
                        <option value="">اختر مركز التكلفة</option>
                        @foreach($costCenters as $center)
                            <option value="{{ $center->id }}" @selected((string) old('cost_center_id', $asset->cost_center_id ?? '') === (string) $center->id)>
                                {{ $center->code }} - {{ $center->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('cost_center_id')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-700">الاسم <span class="text-red-500">*</span></label>
                    <input type="text" name="name" value="{{ old('name', $asset->name ?? '') }}" required class="h-10 w-full rounded-lg border border-gray-200 bg-gray-50 px-3 text-sm focus:border-blue-500 focus:ring-blue-500">
                    @error('name')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-700">الاسم بالعربية</label>
                    <input type="text" name="name_ar" value="{{ old('name_ar', $asset->name_ar ?? '') }}" class="h-10 w-full rounded-lg border border-gray-200 bg-gray-50 px-3 text-sm focus:border-blue-500 focus:ring-blue-500">
                    @error('name_ar')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-700">الموقع</label>
                    <input type="text" name="location" value="{{ old('location', $asset->location ?? '') }}" class="h-10 w-full rounded-lg border border-gray-200 bg-gray-50 px-3 text-sm focus:border-blue-500 focus:ring-blue-500">
                    @error('location')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>

                <div class="md:col-span-2">
                    <label class="mb-1 block text-sm font-medium text-gray-700">الوصف</label>
                    <textarea name="description" rows="3" class="w-full rounded-lg border border-gray-200 bg-gray-50 px-3 py-2 text-sm focus:border-blue-500 focus:ring-blue-500">{{ old('description', $asset->description ?? '') }}</textarea>
                    @error('description')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>
            </div>
        </section>

        <section class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm">
            <h2 class="mb-4 flex items-center gap-2 text-lg font-bold text-gray-900">تفاصيل الاقتناء <x-info field="acquisition_cost" /></h2>
            <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-700">تاريخ الاقتناء <span class="text-red-500">*</span></label>
                    <input type="date" name="acquisition_date" value="{{ old('acquisition_date', isset($asset->acquisition_date) ? $asset->acquisition_date->format('Y-m-d') : '') }}" required class="h-10 w-full rounded-lg border border-gray-200 bg-gray-50 px-3 text-sm focus:border-blue-500 focus:ring-blue-500">
                    @error('acquisition_date')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="mb-1 flex items-center gap-1 text-sm font-medium text-gray-700">تكلفة الاقتناء <x-info field="acquisition_cost" /> <span class="text-red-500">*</span></label>
                    <div class="flex h-10 overflow-hidden rounded-lg border border-gray-200 bg-white focus-within:border-blue-500 focus-within:ring-2 focus-within:ring-blue-100">
                        <span class="flex w-14 items-center justify-center border-r border-gray-200 bg-gray-50 text-xs font-medium text-gray-500">SAR</span>
                        <input type="number" inputmode="decimal" min="0" step="any" name="acquisition_cost" value="{{ old('acquisition_cost', $asset->acquisition_cost ?? '') }}" required class="h-full flex-1 border-0 bg-white px-3 text-sm focus:outline-none focus:ring-0">
                    </div>
                    @error('acquisition_cost')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>
            </div>
        </section>

        <section class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm">
            <h2 class="mb-4 flex items-center gap-2 text-lg font-bold text-gray-900">إعدادات الإهلاك <x-info field="depreciation_method" /></h2>
            <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                <div>
                    <label class="mb-1 flex items-center gap-1 text-sm font-medium text-gray-700">طريقة الإهلاك <x-info field="depreciation_method" /></label>
                    <select name="depreciation_method" class="h-10 w-full rounded-lg border border-gray-200 bg-gray-50 px-3 text-sm focus:border-blue-500 focus:ring-blue-500">
                        <option value="">اختر الطريقة</option>
                        <option value="straightline" @selected(old('depreciation_method', $asset->depreciation_method ?? '') === 'straightline')>القسط الثابت</option>
                        <option value="reducing_balance" @selected(old('depreciation_method', $asset->depreciation_method ?? '') === 'reducing_balance')>الرصيد المتناقص</option>
                        <option value="units_of_production" @selected(old('depreciation_method', $asset->depreciation_method ?? '') === 'units_of_production')>وحدات الإنتاج</option>
                    </select>
                    @error('depreciation_method')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-700">العمر الإنتاجي (سنوات)</label>
                    <input type="number" inputmode="decimal" min="0" max="100" step="any" name="useful_life_years" value="{{ old('useful_life_years', $asset->useful_life_years ?? 0) }}" class="h-10 w-full rounded-lg border border-gray-200 bg-gray-50 px-3 text-sm focus:border-blue-500 focus:ring-blue-500">
                    @error('useful_life_years')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-700">العمر الإنتاجي (شهور)</label>
                    <input type="number" inputmode="decimal" min="0" max="1200" step="any" name="useful_life_months" value="{{ old('useful_life_months', $asset->useful_life_months ?? 0) }}" class="h-10 w-full rounded-lg border border-gray-200 bg-gray-50 px-3 text-sm focus:border-blue-500 focus:ring-blue-500">
                    @error('useful_life_months')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="mb-1 flex items-center gap-1 text-sm font-medium text-gray-700">قيمة الخردة <x-info field="salvage_value" /></label>
                    <div class="flex h-10 overflow-hidden rounded-lg border border-gray-200 bg-white focus-within:border-blue-500 focus-within:ring-2 focus-within:ring-blue-100">
                        <span class="flex w-14 items-center justify-center border-r border-gray-200 bg-gray-50 text-xs font-medium text-gray-500">SAR</span>
                        <input type="number" inputmode="decimal" min="0" step="any" name="salvage_value" value="{{ old('salvage_value', $asset->salvage_value ?? 0) }}" class="h-full flex-1 border-0 bg-white px-3 text-sm focus:outline-none focus:ring-0">
                    </div>
                    @error('salvage_value')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>

                <div class="md:col-span-2">
                    <label class="mb-1 block text-sm font-medium text-gray-700">تاريخ بدء الإهلاك</label>
                    <input type="date" name="depreciation_start_date" value="{{ old('depreciation_start_date', isset($asset->depreciation_start_date) ? $asset->depreciation_start_date->format('Y-m-d') : '') }}" class="h-10 w-full rounded-lg border border-gray-200 bg-gray-50 px-3 text-sm focus:border-blue-500 focus:ring-blue-500">
                    @error('depreciation_start_date')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>
            </div>
        </section>

        <section class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm">
            <h2 class="mb-4 text-lg font-bold text-gray-900">تفاصيل الأصل</h2>
            <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-700">الرقم التسلسلي</label>
                    <input type="text" name="serial_number" value="{{ old('serial_number', $asset->serial_number ?? '') }}" class="h-10 w-full rounded-lg border border-gray-200 bg-gray-50 px-3 text-sm focus:border-blue-500 focus:ring-blue-500">
                    @error('serial_number')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-700">الموديل</label>
                    <input type="text" name="model" value="{{ old('model', $asset->model ?? '') }}" class="h-10 w-full rounded-lg border border-gray-200 bg-gray-50 px-3 text-sm focus:border-blue-500 focus:ring-blue-500">
                    @error('model')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-700">الشركة المصنعة</label>
                    <input type="text" name="manufacturer" value="{{ old('manufacturer', $asset->manufacturer ?? '') }}" class="h-10 w-full rounded-lg border border-gray-200 bg-gray-50 px-3 text-sm focus:border-blue-500 focus:ring-blue-500">
                    @error('manufacturer')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-700">انتهاء الضمان</label>
                    <input type="date" name="warranty_end_date" value="{{ old('warranty_end_date', isset($asset->warranty_end_date) ? $asset->warranty_end_date->format('Y-m-d') : '') }}" class="h-10 w-full rounded-lg border border-gray-200 bg-gray-50 px-3 text-sm focus:border-blue-500 focus:ring-blue-500">
                    @error('warranty_end_date')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-700">وثيقة التأمين</label>
                    <input type="text" name="insurance_document" value="{{ old('insurance_document', $asset->insurance_document ?? '') }}" class="h-10 w-full rounded-lg border border-gray-200 bg-gray-50 px-3 text-sm focus:border-blue-500 focus:ring-blue-500">
                    @error('insurance_document')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-700">انتهاء التأمين</label>
                    <input type="date" name="insurance_end_date" value="{{ old('insurance_end_date', isset($asset->insurance_end_date) ? $asset->insurance_end_date->format('Y-m-d') : '') }}" class="h-10 w-full rounded-lg border border-gray-200 bg-gray-50 px-3 text-sm focus:border-blue-500 focus:ring-blue-500">
                    @error('insurance_end_date')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>
            </div>
        </section>

        <div class="flex justify-end gap-3 pb-2">
            <a href="{{ route('finance.fixed-assets.index') }}" class="rounded-lg border border-gray-200 bg-white px-5 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50">إلغاء</a>
            <button type="submit" class="rounded-lg bg-blue-600 px-6 py-2.5 text-sm font-semibold text-white hover:bg-blue-700">حفظ</button>
        </div>
    </form>
</div>
@endsection
