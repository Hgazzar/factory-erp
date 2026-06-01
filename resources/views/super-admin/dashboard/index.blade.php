@extends('layouts.app')

@section('title', 'لوحة المالك — '.config('app.name'))

@section('breadcrumb')
    <a href="{{ route('dashboard') }}" class="text-gray-500 hover:text-indigo-600">الرئيسية</a>
    <span>›</span>
    <span class="text-indigo-900 font-semibold">لوحة المالك</span>
@endsection

@section('content')
<div dir="rtl" class="max-w-5xl space-y-6">
    @if(session('success'))
        <div class="p-4 rounded-xl bg-green-50 border border-green-200 text-green-800 text-sm">{{ session('success') }}</div>
    @endif

    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">لوحة المالك المركزية</h1>
            <p class="mt-1 text-sm text-gray-500">إدارة منصة Akwad SaaS — المستأجرين، النيشات، والموديولات.</p>
        </div>
        <a href="{{ route('super-admin.tenants.create') }}"
           class="inline-flex items-center rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700">
            + إنشاء مستأجر
        </a>
    </div>

    <div class="grid gap-4 sm:grid-cols-3">
        <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
            <p class="text-sm text-gray-500"><x-info field="super_admin_stats_tenants_total" /> إجمالي المستأجرين</p>
            <p class="mt-2 text-3xl font-bold text-indigo-900 tabular-nums">{{ $stats['tenants_total'] ?? 0 }}</p>
        </div>
        <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
            <p class="text-sm text-gray-500"><x-info field="super_admin_stats_tenants_profiled" /> مستأجرون بنيش محدد</p>
            <p class="mt-2 text-3xl font-bold text-indigo-900 tabular-nums">{{ $stats['tenants_with_profile'] ?? 0 }}</p>
        </div>
        <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
            <p class="text-sm text-gray-500"><x-info field="super_admin_stats_niches" /> النيشات المتاحة</p>
            <p class="mt-2 text-3xl font-bold text-indigo-900 tabular-nums">{{ $stats['niches_available'] ?? 0 }}</p>
        </div>
    </div>

    <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
        <h2 class="text-lg font-semibold text-gray-900">الإجراءات السريعة</h2>
        <div class="mt-4 flex flex-wrap gap-3">
            <a href="{{ route('super-admin.tenants.index') }}"
               class="inline-flex items-center rounded-lg border border-gray-200 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">
                عرض كل المستأجرين
            </a>
            <a href="{{ route('super-admin.tenants.create') }}"
               class="inline-flex items-center rounded-lg border border-indigo-200 bg-indigo-50 px-4 py-2 text-sm font-medium text-indigo-800 hover:bg-indigo-100">
                إنشاء مستأجر جديد
            </a>
        </div>
    </div>
</div>
@endsection
