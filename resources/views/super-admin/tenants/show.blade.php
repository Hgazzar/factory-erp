@extends('layouts.app')

@section('title', 'موديولات — '.$summary['name'].' - '.config('app.name'))

@section('breadcrumb')
    <a href="{{ route('dashboard') }}" class="text-gray-500 hover:text-indigo-600">الرئيسية</a>
    <span>›</span>
    <a href="{{ route('super-admin.tenants.index') }}" class="text-gray-500 hover:text-indigo-600">التحكم المركزي</a>
    <span>›</span>
    <span class="text-indigo-900 font-semibold">{{ $summary['name'] }}</span>
@endsection

@section('content')
<div dir="rtl" class="max-w-4xl space-y-6" x-data="superAdminPremiumFeatures()">
    @if(session('success'))
        <div class="p-4 rounded-xl bg-green-50 border border-green-200 text-green-800 text-sm">{{ session('success') }}</div>
    @endif
    @if(session('temporary_password'))
        <div class="p-4 rounded-xl bg-amber-50 border border-amber-200 text-amber-900 text-sm">
            <strong>كلمة المرور المؤقتة للمالك:</strong>
            <code dir="ltr" class="mx-2 font-mono bg-white px-2 py-0.5 rounded">{{ session('temporary_password') }}</code>
            — احرص على إرسالها للعميل ثم تغييرها عند أول دخول.
        </div>
    @endif
    @if(session('error'))
        <div class="p-4 rounded-xl bg-red-50 border border-red-200 text-red-800 text-sm">{{ session('error') }}</div>
    @endif

    <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
        <div class="flex flex-wrap items-start justify-between gap-4 mb-6">
            <div>
                <h1 class="text-xl font-bold text-gray-900">{{ $summary['name'] }}</h1>
                <p class="mt-1 text-sm text-gray-500">{{ $summary['email'] }} — {{ $summary['owner_name'] }}</p>
            </div>
            <a href="{{ route('super-admin.tenants.index') }}" class="text-sm text-indigo-600 hover:text-indigo-800">← رجوع للقائمة</a>
        </div>

        <dl class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-8 text-sm">
            <div class="rounded-lg bg-gray-50 p-4">
                <dt class="text-gray-500 flex items-center gap-1">
                    <x-info field="super_admin_tenant_niche" /> النيش
                </dt>
                <dd class="mt-1 text-lg font-semibold text-gray-900">{{ $summary['niche_name'] ?? '—' }}</dd>
            </div>
            <div class="rounded-lg bg-gray-50 p-4">
                <dt class="text-gray-500 flex items-center gap-1">
                    <x-info field="super_admin_tenant_slug" /> Slug
                </dt>
                <dd class="mt-1 text-lg font-semibold text-gray-900 font-mono" dir="ltr">{{ $summary['slug'] ?? '—' }}</dd>
                @if(!empty($summary['store_url']))
                    <dd class="mt-1 text-xs"><a href="{{ $summary['store_url'] }}" target="_blank" rel="noopener" class="text-indigo-600 hover:underline" dir="ltr">{{ $summary['store_url'] }}</a></dd>
                @endif
            </div>
            <div class="rounded-lg bg-gray-50 p-4">
                <dt class="text-gray-500 flex items-center gap-1">
                    <x-info field="super_admin_tenant_employees" /> عدد الموظفين
                </dt>
                <dd class="mt-1 text-2xl font-bold text-gray-900 tabular-nums">{{ $summary['employee_count'] }}</dd>
            </div>
            <div class="rounded-lg bg-gray-50 p-4">
                <dt class="text-gray-500 flex items-center gap-1">
                    <x-info field="super_admin_tenant_subscribed_at" /> تاريخ الاشتراك
                </dt>
                <dd class="mt-1 text-lg font-semibold text-gray-900">{{ $summary['subscribed_at_label'] ?? '—' }}</dd>
            </div>
        </dl>

        <div class="rounded-lg bg-indigo-50 border border-indigo-100 px-4 py-3 mb-8 text-sm text-indigo-900">
            الموديولات المفعّلة: <strong class="tabular-nums">{{ count($enabledModuleKeys) }}</strong>
        </div>

        <div class="rounded-lg border border-amber-200 bg-amber-50/80 px-4 py-3 mb-6 text-sm text-amber-950">
            <p class="font-medium flex items-center gap-1">
                <x-info field="super_admin_modules_vs_premium" /> الفرق بين القسمين
            </p>
            <ul class="mt-2 space-y-1 list-disc list-inside text-amber-900/90">
                <li><strong>المزايا البريميوم</strong> — إضافات داخل النيش (واتساب، فروع، ربط أجهزة كاشير…).</li>
                <li><strong>الموديولات المتاحة</strong> — أقسام النظام بالكامل (مثل POS يظهر قسم نقاط البيع في القائمة).</li>
                <li>مثال: تفعيل موديول <code class="text-xs bg-white/80 px-1 rounded">pos</code> ≠ تفعيل «ربط أجهزة الكاشير» — الأخير يحتاج POS مفعّلاً ثم يفتح صفحة تسجيل الأجهزة.</li>
            </ul>
        </div>

        <div class="rounded-lg border border-indigo-200 bg-indigo-50/50 p-5 mb-8 flex flex-wrap items-center justify-between gap-3">
            <div>
                <h2 class="text-base font-semibold text-gray-900 flex items-center gap-1">
                    <x-info field="super_admin_premium_features" /> المزايا البريميوم
                </h2>
                <p class="text-sm text-gray-600 mt-1">تُفلتر حسب نيش الشركة ({{ $summary['niche_name'] ?? '—' }}) وتُحفظ لكل مستأجر على حدة.</p>
            </div>
            <button type="button"
                    @click="openFor({{ (int) $tenant->id }})"
                    class="inline-flex items-center rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700">
                إدارة المزايا البريميوم
            </button>
        </div>

        <form method="POST" action="{{ route('super-admin.tenants.slug.update', $tenant) }}" class="rounded-lg border border-gray-200 p-5 mb-8 space-y-4">
            @csrf
            @method('PUT')
            <h2 class="text-base font-semibold text-gray-900 flex items-center gap-1">
                <x-info field="super_admin_tenant_slug" /> تعديل Slug المتجر
            </h2>
            <p class="text-sm text-gray-500">يغيّر رابط المتجر العام <code dir="ltr" class="text-xs bg-gray-100 px-1 rounded">/s/{slug}</code> — يجب أن يكون فريداً.</p>
            <div class="flex flex-wrap items-end gap-3">
                <div class="flex-1 min-w-[200px]">
                    <label for="slug" class="block text-xs font-medium text-gray-600 mb-1">Slug جديد</label>
                    <input type="text" name="slug" id="slug" dir="ltr" required
                           value="{{ old('slug', $summary['slug'] ?? '') }}"
                           pattern="[a-z0-9]+(?:-[a-z0-9]+)*"
                           class="w-full rounded-lg border-gray-300 font-mono text-sm @error('slug') border-red-500 @enderror">
                    @error('slug')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>
                <button type="submit" class="inline-flex items-center rounded-lg bg-gray-900 px-4 py-2 text-sm font-medium text-white hover:bg-gray-800">
                    حفظ Slug
                </button>
            </div>
        </form>

        <form method="POST" action="{{ route('super-admin.tenants.modules.update', $tenant) }}" class="space-y-6">
            @csrf
            @method('PUT')

            <div>
                <h2 class="text-base font-semibold text-gray-900 mb-1 flex items-center gap-1">
                    الموديولات المتاحة
                    <x-info field="super_admin_tenant_modules" />
                </h2>
                <p class="text-sm text-gray-500 mb-4">فعّل أو عطّل الوحدات لهذه الشركة. التغييرات فورية على القائمة الجانبية ومسارات الـ API.</p>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    @foreach($modules as $module)
                        @php
                            $isCore = (bool) $module->is_core;
                            $isChecked = in_array($module->key, $enabledModuleKeys, true);
                        @endphp
                        <label class="flex items-start gap-3 rounded-lg border border-gray-200 p-4 cursor-pointer hover:bg-gray-50 {{ $isCore ? 'opacity-70 cursor-not-allowed bg-gray-50' : '' }}">
                            <input type="checkbox"
                                   name="modules[]"
                                   value="{{ $module->key }}"
                                   class="mt-1 h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                                   @checked(old('modules') ? in_array($module->key, old('modules', []), true) : $isChecked)
                                   @disabled($isCore)>
                            <span class="min-w-0">
                                <span class="block font-medium text-gray-900">{{ $module->name_ar }}</span>
                                <span class="block text-xs text-gray-500 font-mono">{{ $module->key }}</span>
                                @if($isCore)
                                    <span class="inline-block mt-1 text-xs text-gray-400">أساسي — دائماً مفعّل</span>
                                @endif
                            </span>
                        </label>
                    @endforeach
                </div>
                @error('modules')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
                @error('modules.*')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>

            <div class="flex justify-end pt-2">
                <button type="submit" class="inline-flex items-center rounded-lg bg-indigo-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                    حفظ الموديولات
                </button>
            </div>
        </form>
    </div>

    @include('super-admin.tenants._premium-features-modal')
</div>
@endsection
