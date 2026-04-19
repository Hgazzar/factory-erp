@extends('layouts.app')

@section('title', 'عرض أصل - MIRADA ERP')

@section('breadcrumb')
    <a href="{{ route('dashboard') }}" class="text-gray-500 hover:text-blue-600">الرئيسية</a>
    <span class="mx-1 text-gray-400">›</span>
    <a href="{{ route('finance.dashboard') }}" class="text-gray-500 hover:text-blue-600">المحاسبة</a>
    <span class="mx-1 text-gray-400">›</span>
    <a href="{{ route('finance.fixed-assets.index') }}" class="text-gray-500 hover:text-blue-600">الأصول الثابتة</a>
    <span class="mx-1 text-gray-400">›</span>
    <span class="text-blue-900 font-semibold">{{ $asset->asset_code }}</span>
@endsection

@php
    $methodLabels = [
        'straightline' => 'خط مستقيم',
        'reducing_balance' => 'متناقص',
        'units_of_production' => 'وحدات إنتاج',
    ];
    $statusLabels = [
        'in_use' => ['label' => 'مستخدم', 'class' => 'bg-green-100 text-green-700'],
        'stopped' => ['label' => 'متوقف', 'class' => 'bg-gray-100 text-gray-600'],
        'decommissioned' => ['label' => 'خارج الخدمة', 'class' => 'bg-orange-100 text-orange-700'],
    ];
    $st = $statusLabels[$asset->status] ?? ['label' => $asset->status, 'class' => 'bg-gray-100 text-gray-600'];
@endphp

@section('content')
<div dir="rtl" class="mx-auto w-full max-w-full space-y-6">
    <header class="flex flex-wrap items-center justify-between gap-4 rounded-lg border border-gray-200 bg-white p-5 shadow-sm">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">{{ $asset->name }}</h1>
            <p class="mt-1 text-sm text-gray-500">
                <span class="font-mono text-gray-800">{{ $asset->asset_code }}</span>
                @if($asset->name_ar)
                    <span class="mx-1 text-gray-300">·</span>
                    {{ $asset->name_ar }}
                @endif
            </p>
        </div>
        <div class="flex flex-wrap items-center gap-3">
            <a href="{{ route('finance.fixed-assets.index') }}" class="inline-flex items-center rounded-lg border border-gray-200 bg-white px-4 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50">رجوع للقائمة</a>
            <a href="{{ route('finance.fixed-assets.edit', $asset) }}" class="inline-flex items-center rounded-lg bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-blue-700">تعديل</a>
        </div>
    </header>

    <section class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm">
        <h2 class="mb-6 text-lg font-bold text-gray-900">المعلومات الأساسية</h2>
        <dl class="grid grid-cols-1 gap-6 md:grid-cols-2">
            <div>
                <dt class="mb-1 flex items-center gap-1 text-xs font-medium text-gray-500">رمز الأصل <x-info field="asset_code" /></dt>
                <dd class="text-sm font-semibold text-gray-900">{{ $asset->asset_code }}</dd>
            </div>
            <div>
                <dt class="mb-1 flex items-center gap-1 text-xs font-medium text-gray-500">التصنيف <x-info field="asset_category" /></dt>
                <dd class="text-sm text-gray-800">{{ $asset->categoryRef?->name_ar ?? $asset->category ?? '—' }}</dd>
            </div>
            <div>
                <dt class="mb-1 flex items-center gap-1 text-xs font-medium text-gray-500">مركز التكلفة <x-info field="cost_center" /></dt>
                <dd class="text-sm text-gray-800">{{ $asset->costCenter ? $asset->costCenter->code.' — '.$asset->costCenter->name : '—' }}</dd>
            </div>
            <div>
                <dt class="mb-1 text-xs font-medium text-gray-500">الموقع</dt>
                <dd class="text-sm text-gray-800">{{ $asset->location ?: '—' }}</dd>
            </div>
            <div class="md:col-span-2">
                <dt class="mb-1 text-xs font-medium text-gray-500">الوصف</dt>
                <dd class="text-sm text-gray-700 whitespace-pre-wrap">{{ $asset->description ?: '—' }}</dd>
            </div>
            <div>
                <dt class="mb-1 text-xs font-medium text-gray-500">الحالة</dt>
                <dd>
                    <span class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium {{ $st['class'] }}">{{ $st['label'] }}</span>
                </dd>
            </div>
        </dl>
    </section>

    <section class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm">
        <h2 class="mb-6 text-lg font-bold text-gray-900">الاقتناء والقيمة</h2>
        <dl class="grid grid-cols-1 gap-6 md:grid-cols-2">
            <div>
                <dt class="mb-1 text-xs font-medium text-gray-500">تاريخ الاقتناء</dt>
                <dd class="text-sm text-gray-800">{{ optional($asset->acquisition_date)->format('Y-m-d') ?? '—' }}</dd>
            </div>
            <div>
                <dt class="mb-1 flex items-center gap-1 text-xs font-medium text-gray-500">تكلفة الاقتناء <x-info field="acquisition_cost" /></dt>
                <dd class="text-sm font-semibold text-gray-900">{{ number_format((float) $asset->acquisition_cost, 2) }} SAR</dd>
            </div>
            <div>
                <dt class="mb-1 flex items-center gap-1 text-xs font-medium text-gray-500">القيمة الدفترية <x-info field="book_value" /></dt>
                <dd class="text-sm font-semibold text-gray-900">{{ number_format((float) $asset->calculated_book_value, 2) }} SAR</dd>
            </div>
            <div>
                <dt class="mb-1 flex items-center gap-1 text-xs font-medium text-gray-500">طريقة الإهلاك <x-info field="depreciation_method" /></dt>
                <dd class="text-sm text-gray-800">{{ $asset->depreciation_method ? ($methodLabels[$asset->depreciation_method] ?? $asset->depreciation_method) : '—' }}</dd>
            </div>
            <div>
                <dt class="mb-1 text-xs font-medium text-gray-500">بداية الإهلاك</dt>
                <dd class="text-sm text-gray-800">{{ optional($asset->depreciation_start_date)->format('Y-m-d') ?? '—' }}</dd>
            </div>
            <div>
                <dt class="mb-1 flex items-center gap-1 text-xs font-medium text-gray-500">القيمة المتبقية <x-info field="salvage_value" /></dt>
                <dd class="text-sm text-gray-800">{{ $asset->salvage_value !== null ? number_format((float) $asset->salvage_value, 2).' SAR' : '—' }}</dd>
            </div>
            <div>
                <dt class="mb-1 text-xs font-medium text-gray-500">العمر الإنتاجي (سنوات / أشهر)</dt>
                <dd class="text-sm text-gray-800">{{ (int) ($asset->useful_life_years ?? 0) }} / {{ (int) ($asset->useful_life_months ?? 0) }}</dd>
            </div>
        </dl>
    </section>

    <section class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm">
        <h2 class="mb-6 text-lg font-bold text-gray-900">تفاصيل إضافية</h2>
        <dl class="grid grid-cols-1 gap-6 md:grid-cols-2">
            <div>
                <dt class="mb-1 text-xs font-medium text-gray-500">الرقم التسلسلي</dt>
                <dd class="text-sm text-gray-800">{{ $asset->serial_number ?: '—' }}</dd>
            </div>
            <div>
                <dt class="mb-1 text-xs font-medium text-gray-500">الموديل / الصانع</dt>
                <dd class="text-sm text-gray-800">{{ trim(implode(' — ', array_filter([$asset->model, $asset->manufacturer]))) ?: '—' }}</dd>
            </div>
            <div>
                <dt class="mb-1 text-xs font-medium text-gray-500">انتهاء الضمان</dt>
                <dd class="text-sm text-gray-800">{{ optional($asset->warranty_end_date)->format('Y-m-d') ?? '—' }}</dd>
            </div>
            <div>
                <dt class="mb-1 text-xs font-medium text-gray-500">وثيقة التأمين / انتهاء التأمين</dt>
                <dd class="text-sm text-gray-800">
                    @if($asset->insurance_document || $asset->insurance_end_date)
                        {{ $asset->insurance_document ?: '—' }}
                        @if($asset->insurance_end_date)
                            <span class="text-gray-400"> · </span>{{ $asset->insurance_end_date->format('Y-m-d') }}
                        @endif
                    @else
                        —
                    @endif
                </dd>
            </div>
        </dl>
    </section>
</div>
@endsection
