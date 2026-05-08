@extends('layouts.crm')

@section('title', 'شريحة جديدة — CRM')

@section('breadcrumb')
    <a href="{{ route('dashboard') }}" class="text-gray-500 hover:text-indigo-600">الرئيسية</a>
    <span>›</span>
    <a href="{{ route('crm.dashboard') }}" class="text-gray-500 hover:text-indigo-600">إدارة العملاء</a>
    <span>›</span>
    <a href="{{ route('crm.segments.index') }}" class="text-gray-500 hover:text-indigo-600">شرائح العملاء</a>
    <span>›</span>
    <span class="text-indigo-900 font-semibold truncate">شريحة جديدة</span>
@endsection

@section('content')
<div class="space-y-6" dir="rtl">
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div class="min-w-0">
            <h1 class="text-2xl font-bold text-gray-900 flex flex-wrap items-center gap-2">
                شريحة جديدة
                <span class="inline-flex items-center shrink-0"><x-info field="crm.segments_create_intro" /></span>
            </h1>
            <p class="text-sm text-gray-500 mt-1">حدد المعايير وسيتم ربط أعضاء الشريحة تلقائياً من جدول العملاء.</p>
        </div>
        <a href="{{ route('crm.segments.index') }}" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-lg border border-gray-300 bg-white text-gray-700 text-sm font-medium hover:bg-gray-50 transition">
            رجوع
        </a>
    </div>

    <form method="POST" action="{{ route('crm.segments.store') }}" class="bg-white rounded-lg border border-gray-200 shadow-sm p-6 space-y-6">
        @csrf

        <div class="rounded-lg border border-gray-200 p-5 space-y-5">
            <h2 class="text-base font-semibold text-gray-900">بيانات الشريحة</h2>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label for="segment-code-preview" class="block text-sm font-medium text-gray-700 mb-1">
                        <span class="inline-flex items-center gap-1">الرمز <x-info field="crm.segments_code" /></span>
                    </label>
                    <input id="segment-code-preview" type="text" value="{{ $nextSegmentCode ?? 'SEG-0001' }}" readonly class="block w-full min-h-[2.75rem] rounded-lg border border-gray-300 bg-gray-50 py-2.5 px-3 text-sm text-gray-700">
                    <p class="mt-1 text-xs text-gray-500">يتم توليد الرمز تلقائيًا عند الإنشاء.</p>
                </div>
                <div>
                    <label for="segment-type" class="block text-sm font-medium text-gray-700 mb-1"><span class="inline-flex items-center gap-1">النوع <x-info field="crm.segments_type" /></span></label>
                    <x-searchable-select
                        name="type"
                        id="segment-type"
                        :options="$segmentTypeOptions ?? []"
                        :value="old('type', '')"
                        empty-label="اختر"
                        placeholder="اختر النوع"
                        :searchable="false"
                    />
                    @error('type')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label for="segment-status" class="block text-sm font-medium text-gray-700 mb-1"><span class="inline-flex items-center gap-1">الحالة <x-info field="crm.segments_status" /></span></label>
                    <x-searchable-select
                        name="status"
                        id="segment-status"
                        :options="$segmentStatusOptions ?? []"
                        :value="old('status', 'active')"
                        empty-label="اختر"
                        placeholder="اختر الحالة"
                        :searchable="false"
                    />
                    @error('status')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>
                <div class="md:col-span-3">
                    <label for="segment-name" class="block text-sm font-medium text-gray-700 mb-1"><span class="inline-flex items-center gap-1">اسم الشريحة <x-info field="crm.segments_name" /></span></label>
                    <input id="segment-name" type="text" name="name" value="{{ old('name') }}" placeholder="مثال: العملاء النشطون - المنطقة الوسطى" class="block w-full min-h-[2.75rem] rounded-lg border border-gray-300 py-2.5 px-3 text-sm text-gray-900 placeholder:text-gray-400 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    @error('name')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>
                <div class="md:col-span-3">
                    <label class="block text-sm font-medium text-gray-700 mb-2"><span class="inline-flex items-center gap-1">اللون <x-info field="crm.segments_color" /></span></label>
                    @php
                        $selectedColor = strtoupper((string) old('color', '#2563EB'));
                    @endphp
                    <div class="flex flex-wrap items-center gap-2">
                        @foreach(($segmentColorOptions ?? []) as $color)
                            @php
                                $hex = strtoupper((string) $color);
                                $isSelected = $selectedColor === $hex;
                            @endphp
                            <label class="inline-flex cursor-pointer items-center">
                                <input type="radio" name="color" value="{{ $hex }}" class="sr-only peer" @checked($isSelected)>
                                <span class="inline-flex h-7 w-7 rounded-full border-2 {{ $isSelected ? 'border-gray-900' : 'border-white' }} ring-1 ring-gray-300 peer-focus:ring-2 peer-focus:ring-blue-500" style="background-color: {{ $hex }}"></span>
                            </label>
                        @endforeach
                    </div>
                    @error('color')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>
        </div>

        <div class="rounded-lg border border-gray-200 p-5 space-y-4">
            <h2 class="text-base font-semibold text-gray-900">معايير أعضاء الشريحة</h2>
            <p class="text-xs text-gray-500">سيتم جلب أعضاء الشريحة مباشرة من جدول العملاء حسب المعايير المحددة أدناه.</p>
            <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-4">
                <div>
                    <label for="segment-crm-status" class="block text-sm font-medium text-gray-700 mb-1"><span class="inline-flex items-center gap-1">حالة CRM <x-info field="crm.crm_status" /></span></label>
                    <x-searchable-select
                        name="crm_status"
                        id="segment-crm-status"
                        :options="collect($crmStatusOptions ?? [])->map(fn($label, $value) => ['value' => (string) $value, 'label' => (string) $label])->values()->all()"
                        :value="old('crm_status', '')"
                        empty-label="الكل"
                        placeholder="كل الحالات"
                        :searchable="false"
                    />
                    @error('crm_status')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label for="segment-source" class="block text-sm font-medium text-gray-700 mb-1"><span class="inline-flex items-center gap-1">المصدر <x-info field="crm.crm_source" /></span></label>
                    <x-searchable-select
                        name="source"
                        id="segment-source"
                        :options="collect($sourceOptions ?? [])->map(fn($label, $value) => ['value' => (string) $value, 'label' => (string) $label])->values()->all()"
                        :value="old('source', '')"
                        empty-label="الكل"
                        placeholder="كل المصادر"
                    />
                    @error('source')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label for="segment-region" class="block text-sm font-medium text-gray-700 mb-1"><span class="inline-flex items-center gap-1">المنطقة <x-info field="sales.customers_region_filter" /></span></label>
                    <input id="segment-region" type="text" name="region" value="{{ old('region') }}" placeholder="مثال: الرياض" class="block w-full min-h-[2.75rem] rounded-lg border border-gray-300 py-2.5 px-3 text-sm text-gray-900 placeholder:text-gray-400 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    @error('region')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>
                <div class="grid grid-cols-2 gap-2">
                    <div>
                        <label for="segment-rating-min" class="block text-sm font-medium text-gray-700 mb-1"><span class="inline-flex items-center gap-1">تقييم من <x-info field="crm.leads_rating_column" /></span></label>
                        <input id="segment-rating-min" type="number" min="1" max="5" name="rating_min" value="{{ old('rating_min') }}" class="block w-full min-h-[2.75rem] rounded-lg border border-gray-300 py-2.5 px-3 text-sm text-gray-900 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        @error('rating_min')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label for="segment-rating-max" class="block text-sm font-medium text-gray-700 mb-1">تقييم إلى</label>
                        <input id="segment-rating-max" type="number" min="1" max="5" name="rating_max" value="{{ old('rating_max') }}" class="block w-full min-h-[2.75rem] rounded-lg border border-gray-300 py-2.5 px-3 text-sm text-gray-900 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        @error('rating_max')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>
        </div>

        <div class="flex items-center border-t border-gray-200 pt-4">
            <div class="ms-auto flex items-center gap-2">
                <button type="submit" class="inline-flex items-center justify-center min-h-[2.75rem] px-6 rounded-lg bg-blue-600 text-white text-sm font-semibold hover:bg-blue-700 transition shadow-sm">إنشاء الشريحة</button>
                <a href="{{ route('crm.segments.index') }}" class="inline-flex items-center justify-center min-h-[2.75rem] px-4 rounded-lg border border-gray-300 text-gray-800 text-sm font-medium hover:bg-gray-50 transition no-underline">إلغاء</a>
            </div>
        </div>
    </form>
</div>
@endsection
