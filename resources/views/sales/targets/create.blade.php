@extends('layouts.app')

@section('title', 'هدف مبيعات جديد - ' . config('app.name'))

@section('breadcrumb')
    <a href="{{ route('dashboard') }}" class="text-gray-500 hover:text-indigo-600">الرئيسية</a>
    <span>›</span>
    <a href="{{ route('sales.dashboard') }}" class="text-gray-500 hover:text-indigo-600">المبيعات</a>
    <span>›</span>
    <a href="{{ route('sales.targets.index') }}" class="text-gray-500 hover:text-indigo-600">أهداف المبيعات</a>
    <span>›</span>
    <span class="text-indigo-900 font-semibold">هدف جديد</span>
@endsection

@section('content')
<div class="max-w-full">
    <div class="flex flex-wrap items-center justify-between gap-4 mb-6">
        <h1 class="text-2xl font-bold text-gray-900">هدف جديد</h1>
    </div>

    <form method="POST" action="{{ route('sales.targets.store') }}">
        @csrf

        {{-- تفاصيل الهدف --}}
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5 mb-6">
            <h2 class="text-base font-semibold text-gray-900 mb-4">تفاصيل الهدف</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">اسم الهدف <span class="text-red-500">*</span></label>
                    <input type="text" name="name" value="{{ old('name') }}" required class="w-full px-3 py-2.5 pr-4 text-right bg-gray-50 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500" placeholder="مثال: هدف الإيرادات للربع الأول 2026">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">مخصص لـ <span class="text-red-500">*</span></label>
                    <select name="assigned_to_type" x-data @change="document.getElementById('assigned-wrapper').dataset.type = $event.target.value" class="w-full px-3 py-2.5 pr-4 text-right bg-gray-50 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                        <option value="company" {{ old('assigned_to_type') === 'company' ? 'selected' : '' }}>المنشأة</option>
                        <option value="warehouse" {{ old('assigned_to_type') === 'warehouse' ? 'selected' : '' }}>مخزن</option>
                        <option value="customer" {{ old('assigned_to_type') === 'customer' ? 'selected' : '' }}>عميل</option>
                    </select>
                </div>
                <div id="assigned-wrapper" data-type="{{ old('assigned_to_type', 'company') }}" class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">الجهة المخصصة</label>
                    <select name="assigned_to_id" class="w-full px-3 py-2.5 pr-4 text-right bg-gray-50 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                        <option value="">الكل</option>
                        <optgroup label="المخازن">
                            @foreach($warehouses as $w)
                                <option value="{{ $w->id }}" @selected(old('assigned_to_id') == $w->id)>{{ $w->name_ar }}</option>
                            @endforeach
                        </optgroup>
                        <optgroup label="العملاء">
                            @foreach($customers as $c)
                                <option value="{{ $c->id }}" @selected(old('assigned_to_id') == $c->id)>{{ $c->name }}</option>
                            @endforeach
                        </optgroup>
                    </select>
                    <p class="mt-1 text-xs text-gray-500">اترك الحقل فارغاً لاستهداف جميع {{ old('assigned_to_type', 'company') === 'warehouse' ? 'المخازن' : 'العملاء' }}.</p>
                </div>
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">الوصف</label>
                    <textarea name="description" rows="3" class="w-full px-3 py-2.5 pr-4 text-right bg-gray-50 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500" placeholder="وصف الهدف أو ملاحظات إضافية">{{ old('description') }}</textarea>
                </div>
            </div>
        </div>

        {{-- الفترة --}}
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5 mb-6">
            <h2 class="text-base font-semibold text-gray-900 mb-4">الفترة</h2>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">الفترة <span class="text-red-500">*</span></label>
                    <select name="period" class="w-full px-3 py-2.5 pr-4 text-right bg-gray-50 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                        <option value="monthly" @selected(old('period') === 'monthly')>شهري</option>
                        <option value="quarterly" @selected(old('period') === 'quarterly')>ربع سنوي</option>
                        <option value="yearly" @selected(old('period') === 'yearly')>سنوي</option>
                        <option value="custom" @selected(old('period') === 'custom')>مخصص</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">تاريخ البداية <span class="text-red-500">*</span></label>
                    <input type="date" name="start_date" value="{{ old('start_date', now()->startOfMonth()->format('Y-m-d')) }}" class="w-full px-3 py-2.5 pr-4 text-right bg-gray-50 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">تاريخ النهاية <span class="text-red-500">*</span></label>
                    <input type="date" name="end_date" value="{{ old('end_date', now()->endOfMonth()->format('Y-m-d')) }}" class="w-full px-3 py-2.5 pr-4 text-right bg-gray-50 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                </div>
            </div>
        </div>

        {{-- قيم الهدف --}}
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5 mb-6">
            <h2 class="text-base font-semibold text-gray-900 mb-4">قيم الهدف</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">قيمة الهدف <span class="text-red-500">*</span></label>
                    <input type="number" inputmode="decimal" step="any" min="0.01" name="target_amount" value="{{ old('target_amount') }}" class="w-full px-3 py-2.5 pr-4 text-right bg-gray-50 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500" placeholder="SAR 0.00">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">الحد الأدنى للإنجاز (Threshold)</label>
                    <input type="number" inputmode="decimal" step="any" min="0" name="threshold_amount" value="{{ old('threshold_amount') }}" class="w-full px-3 py-2.5 pr-4 text-right bg-gray-50 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500" placeholder="مثال: 70% من قيمة الهدف">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">الهدف الممتد (Stretch Target)</label>
                    <input type="number" inputmode="decimal" step="any" min="0" name="stretch_amount" value="{{ old('stretch_amount') }}" class="w-full px-3 py-2.5 pr-4 text-right bg-gray-50 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500" placeholder="مثال: قيمة أعلى من الهدف الأساسي">
                </div>
            </div>
        </div>

        <div class="flex justify-end gap-3">
            <a href="{{ route('sales.targets.index') }}" class="px-5 py-2.5 rounded-lg border border-gray-300 bg-white text-gray-700 text-sm font-medium hover:bg-gray-50 transition">إلغاء</a>
            <button type="submit" class="px-5 py-2.5 rounded-lg bg-indigo-600 text-white text-sm font-medium hover:bg-indigo-700 transition">إنشاء</button>
        </div>
    </form>
</div>
@endsection

