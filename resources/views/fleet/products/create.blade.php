@extends('layouts.fleet')

@section('title', 'إضافة صنف — '.niche_module_label('fleet'))

@section('content')
<div class="space-y-6 max-w-2xl" dir="rtl">
    <h1 class="text-2xl font-extrabold text-violet-950">إضافة صنف للكتalog</h1>

    <form method="POST" action="{{ route('fleet.products.store') }}" class="fleet-card p-6 space-y-4">
        @csrf
        <div>
            <label class="block text-sm font-semibold mb-1"><x-info field="fleet.product_name" /> الاسم</label>
            <input type="text" name="name" value="{{ old('name') }}" required class="w-full rounded-lg border-gray-300">
            @error('name')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
        </div>
        <div class="grid sm:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-semibold mb-1"><x-info field="fleet.product_sku" /> SKU</label>
                <input type="text" name="sku" value="{{ old('sku') }}" class="w-full rounded-lg border-gray-300" dir="ltr">
            </div>
            <div>
                <label class="block text-sm font-semibold mb-1"><x-info field="fleet.product_sale_price" /> سعر البيع</label>
                <input type="number" name="sale_price" value="{{ old('sale_price', '0') }}" step="0.01" min="0" required class="w-full rounded-lg border-gray-300">
            </div>
        </div>
        <div>
            <label class="block text-sm font-semibold mb-1"><x-info field="fleet.product_image_url" /> رابط الصورة</label>
            <input type="url" name="image_url" value="{{ old('image_url') }}" class="w-full rounded-lg border-gray-300" dir="ltr">
        </div>
        <div>
            <label class="block text-sm font-semibold mb-1"><x-info field="fleet.product_description" /> الوصف</label>
            <textarea name="description" rows="3" class="w-full rounded-lg border-gray-300">{{ old('description') }}</textarea>
        </div>
        <label class="flex items-center gap-2 text-sm">
            <input type="checkbox" name="is_active" value="1" {{ old('is_active', true) ? 'checked' : '' }}>
            <x-info field="fleet.product_is_active" /> نشط
        </label>
        <div class="flex gap-2">
            <button type="submit" class="fleet-btn fleet-btn-primary">حفظ</button>
            <a href="{{ route('fleet.products.index') }}" class="fleet-btn fleet-btn-soft">إلغاء</a>
        </div>
    </form>
</div>
@endsection
