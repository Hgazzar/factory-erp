@extends('layouts.app')

@section('title', 'التحكم المركزي — الشركات - '.config('app.name'))

@section('breadcrumb')
    <a href="{{ route('dashboard') }}" class="text-gray-500 hover:text-indigo-600">الرئيسية</a>
    <span>›</span>
    <span class="text-indigo-900 font-semibold">التحكم المركزي</span>
@endsection

@section('content')
<div dir="rtl" class="max-w-6xl space-y-6" x-data="superAdminPremiumFeatures()">
    @if(session('success'))
        <div class="p-4 rounded-xl bg-green-50 border border-green-200 text-green-800 text-sm">{{ session('success') }}</div>
    @endif

    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">إدارة الشركات (المستأجرين)</h1>
            <p class="mt-1 text-sm text-gray-500">لوحة مشغّل المنصة — عرض الشركات والتحكم في الموديولات المفعّلة.</p>
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('super-admin.dashboard') }}"
               class="inline-flex items-center rounded-lg border border-gray-200 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">
                لوحة المالك
            </a>
            <a href="{{ route('super-admin.tenants.create') }}"
               class="inline-flex items-center rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700">
                + إنشاء مستأجر
            </a>
        </div>
    </div>

    <div class="rounded-xl border border-gray-200 bg-white shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-right font-semibold text-gray-700">
                            <x-info field="super_admin_tenant_company" /> الشركة
                        </th>
                        <th class="px-4 py-3 text-right font-semibold text-gray-700">البريد</th>
                        <th class="px-4 py-3 text-right font-semibold text-gray-700">
                            <x-info field="super_admin_tenant_niche" /> النيش
                        </th>
                        <th class="px-4 py-3 text-right font-semibold text-gray-700">
                            <x-info field="super_admin_tenant_slug" /> Slug
                        </th>
                        <th class="px-4 py-3 text-right font-semibold text-gray-700">
                            <x-info field="super_admin_tenant_employees" /> الموظفون
                        </th>
                        <th class="px-4 py-3 text-right font-semibold text-gray-700">
                            <x-info field="super_admin_tenant_subscribed_at" /> تاريخ الاشتراك
                        </th>
                        <th class="px-4 py-3 text-right font-semibold text-gray-700">الموديولات</th>
                        <th class="px-4 py-3 text-right font-semibold text-gray-700"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 bg-white">
                    @forelse($tenants as $tenant)
                        <tr class="hover:bg-gray-50/80">
                            <td class="px-4 py-3">
                                <div class="font-medium text-gray-900">{{ $tenant['name'] }}</div>
                                <div class="text-xs text-gray-500">{{ $tenant['owner_name'] }}</div>
                            </td>
                            <td class="px-4 py-3 text-gray-700">{{ $tenant['email'] }}</td>
                            <td class="px-4 py-3 text-gray-700">{{ $tenant['niche_name'] ?? '—' }}</td>
                            <td class="px-4 py-3 text-gray-700 font-mono text-xs" dir="ltr">{{ $tenant['slug'] ?? $tenant['domain'] ?? '—' }}</td>
                            <td class="px-4 py-3 tabular-nums text-gray-700">{{ $tenant['employee_count'] }}</td>
                            <td class="px-4 py-3 text-gray-700">{{ $tenant['subscribed_at_label'] ?? '—' }}</td>
                            <td class="px-4 py-3">
                                <span class="inline-flex items-center rounded-full bg-indigo-50 px-2.5 py-0.5 text-xs font-medium text-indigo-800">
                                    {{ $tenant['enabled_modules_count'] }} موديول
                                </span>
                                @if(! ($tenant['has_explicit_module_registry'] ?? false))
                                    <span class="mr-1 text-xs text-amber-600" title="وصول كامل (legacy)">●</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-left">
                                <div class="flex flex-wrap items-center gap-2 justify-end">
                                    <button type="button"
                                            @click="openFor({{ (int) $tenant['id'] }})"
                                            class="inline-flex items-center rounded-lg border border-indigo-200 bg-white px-3 py-1.5 text-xs font-medium text-indigo-700 hover:bg-indigo-50">
                                        إدارة الموديولات
                                    </button>
                                    <a href="{{ route('super-admin.tenants.show', $tenant['id']) }}"
                                       class="inline-flex items-center rounded-lg border border-gray-200 bg-white px-3 py-1.5 text-xs font-medium text-gray-600 hover:bg-gray-50">
                                        تفاصيل
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-4 py-10 text-center text-gray-500">لا توجد شركات (مستأجرين) مسجّلة بعد.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($tenants->hasPages())
            <div class="border-t border-gray-100 px-4 py-3">
                {{ $tenants->links() }}
            </div>
        @endif
    </div>

    @include('super-admin.tenants._premium-features-modal')
</div>
@endsection
