@extends('layouts.app')

@section('title', 'مفاتيح الربط (API) - '.config('app.name'))

@section('breadcrumb')
    <a href="{{ route('dashboard') }}" class="text-gray-500 hover:text-indigo-600">الرئيسية</a>
    <span>›</span>
    <a href="{{ route('settings.company.edit') }}" class="text-gray-500 hover:text-indigo-600">الإعدادات</a>
    <span>›</span>
    <span class="text-indigo-900 font-semibold">مفاتيح الربط</span>
@endsection

@section('content')
<div dir="rtl" class="max-w-4xl space-y-6">
    @if(session('success'))
        <div class="p-4 rounded-xl bg-green-50 border border-green-200 text-green-800 text-sm">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="p-4 rounded-xl bg-red-50 border border-red-200 text-red-800 text-sm">{{ session('error') }}</div>
    @endif

    @if($newPlainToken)
        <div class="rounded-xl border-2 border-amber-300 bg-amber-50 p-5 shadow-sm" x-data="{ copied: false }">
            <h2 class="text-base font-bold text-amber-900 mb-2 flex items-center gap-1">
                <x-info field="settings_api_token_once" /> مفتاح الربط الجديد
                @if($newTokenName)
                    <span class="font-normal text-amber-800">({{ $newTokenName }})</span>
                @endif
            </h2>
            <p class="text-sm text-amber-800 mb-3">انسخ المفتاح الآن — <strong>لن يُعرض مرة أخرى</strong> لأسباب أمنية.</p>
            <div class="flex flex-wrap items-stretch gap-2">
                <code id="new-api-token-value" class="flex-1 min-w-0 break-all rounded-lg bg-white border border-amber-200 px-3 py-2 text-xs font-mono text-gray-900 dir-ltr text-left">{{ $newPlainToken }}</code>
                <button type="button"
                        class="inline-flex items-center rounded-lg bg-amber-600 px-4 py-2 text-sm font-medium text-white hover:bg-amber-700"
                        @click="navigator.clipboard.writeText(document.getElementById('new-api-token-value').textContent); copied = true; setTimeout(() => copied = false, 2000)">
                    <span x-show="!copied">نسخ</span>
                    <span x-show="copied" x-cloak>تم النسخ ✓</span>
                </button>
            </div>
            <p class="mt-3 text-xs text-amber-700">استخدمه في رأس الطلب: <code class="bg-white/80 px-1 rounded dir-ltr">Authorization: Bearer …</code></p>
        </div>
    @endif

    <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
        <h1 class="text-xl font-bold text-gray-900 mb-1">مفاتيح الربط (API Tokens)</h1>
        <p class="text-sm text-gray-500 mb-6">اربط تطبيقات خارجية (متجر، تطبيق مناديب، تكاملات) بنظام {{ config('app.name') }} بأمان.</p>

        @if(! $canManageTokens)
            <div class="rounded-lg border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900">
                هذا الحساب لا ينتمي لمستأجر محدّد. استخدم حساب <strong>admin</strong> للشركة لإدارة مفاتيح الربط.
            </div>
        @else
            <form method="POST" action="{{ route('settings.api-tokens.store') }}" class="mb-8 rounded-lg border border-gray-100 bg-gray-50/80 p-4">
                @csrf
                <label for="device_name" class="mb-1 flex flex-wrap items-center gap-1 text-sm font-medium text-gray-700">
                    اسم التطبيق / الربط
                    <x-info field="settings_api_token_device_name" />
                </label>
                <div class="flex flex-wrap gap-2 mt-1">
                    <input type="text"
                           id="device_name"
                           name="device_name"
                           value="{{ old('device_name') }}"
                           required
                           maxlength="255"
                           placeholder="مثال: متجر شوبيفاي"
                           class="flex-1 min-w-[12rem] rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500">
                    <button type="submit" class="inline-flex items-center rounded-lg bg-indigo-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-indigo-700">
                        توليد مفتاح جديد
                    </button>
                </div>
                @error('device_name')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </form>

            <h2 class="text-base font-semibold text-gray-900 mb-3 flex items-center gap-1">
                المفاتيح النشطة
                <x-info field="settings_api_token_list" />
            </h2>

            @if($tokens === [])
                <p class="text-sm text-gray-500 py-6 text-center border border-dashed border-gray-200 rounded-lg">لا توجد مفاتيح نشطة بعد.</p>
            @else
                <div class="overflow-x-auto rounded-lg border border-gray-200">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-right font-semibold text-gray-700">الاسم</th>
                                <th class="px-4 py-3 text-right font-semibold text-gray-700">آخر استخدام</th>
                                <th class="px-4 py-3 text-right font-semibold text-gray-700">تاريخ الإنشاء</th>
                                <th class="px-4 py-3 text-right font-semibold text-gray-700"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach($tokens as $token)
                                <tr>
                                    <td class="px-4 py-3 font-medium text-gray-900">{{ $token['name'] }}</td>
                                    <td class="px-4 py-3 text-gray-600">{{ $token['last_used_at_label'] ?? '—' }}</td>
                                    <td class="px-4 py-3 text-gray-600">{{ $token['created_at_label'] ?? '—' }}</td>
                                    <td class="px-4 py-3">
                                        <form method="POST" action="{{ route('settings.api-tokens.destroy', $token['id']) }}" onsubmit="return confirm('إلغاء هذا المفتاح؟ أي تطبيق يستخدمه سيتوقف فوراً.');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-sm font-medium text-red-600 hover:text-red-800">إلغاء</button>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif

            <p class="mt-4 text-xs text-gray-500">
                المستأجر المرتبط: #{{ $tenantUserId }} — جميع الطلبات عبر هذه المفاتيح تمر بعزل {{ config('app.name') }} (TenantContext).
            </p>
        @endif
    </div>
</div>
@endsection
