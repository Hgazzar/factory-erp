@extends('layouts.app')

@section('title', 'إعدادات المنشأة - MIRADA ERP')

@section('breadcrumb')
    <a href="{{ route('dashboard') }}" class="text-gray-500 hover:text-indigo-600">الرئيسية</a>
    <span>›</span>
    <span class="text-indigo-900 font-semibold">إعدادات المنشأة</span>
@endsection

@section('content')
<div dir="rtl" class="max-w-4xl space-y-6">
    @if(session('success'))
        <div class="mb-4 p-4 rounded-xl bg-green-50 border border-green-200 text-green-800 text-sm">
            {{ session('success') }}
        </div>
    @endif
    @if(session('error'))
        <div class="mb-4 p-4 rounded-xl bg-red-50 border border-red-200 text-red-800 text-sm">
            {{ session('error') }}
        </div>
    @endif

    <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
        <h1 class="mb-6 text-xl font-bold text-gray-900">إعدادات المنشأة</h1>

        <form method="POST" action="{{ route('settings.company.update') }}" enctype="multipart/form-data" class="space-y-8">
            @csrf
            @method('PUT')

            <div class="space-y-4">
                <div>
                    <label for="name" class="block text-sm font-medium text-gray-700 mb-1">اسم المنشأة</label>
                    <input type="text" id="name" name="name" value="{{ old('name', $setting->name) }}" class="w-full px-3 py-2.5 pr-4 text-right border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500" placeholder="مثال: MIRADA ERP">
                    @error('name')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="tax_number" class="block text-sm font-medium text-gray-700 mb-1">الرقم الضريبي</label>
                    <input type="text" id="tax_number" name="tax_number" value="{{ old('tax_number', $setting->tax_number) }}" class="w-full px-3 py-2.5 pr-4 text-right border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500" placeholder="الرقم الضريبي">
                    @error('tax_number')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="default_vat_percent" class="mb-1 flex flex-wrap items-center gap-1 text-sm font-medium text-gray-700">
                        نسبة الضريبة الافتراضية (%)
                        <x-info field="company_default_vat_percent" />
                    </label>
                    <input
                        type="number"
                        id="default_vat_percent"
                        name="default_vat_percent"
                        value="{{ old('default_vat_percent', $setting->default_vat_percent ?? config('accounting.default_vat_percent', 15)) }}"
                        min="0"
                        max="100"
                        step="0.01"
                        inputmode="decimal"
                        class="w-full max-w-xs px-3 py-2.5 pr-4 text-right border border-gray-300 rounded-lg text-sm tabular-nums focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                        required
                    >
                    <p class="mt-1 text-xs text-gray-500">القيمة الاحتياطية من الإعداد العام {{ erp_qty((float) config('accounting.default_vat_percent', 15)) }}٪ حتى تُحفظ من هنا.</p>
                    @error('default_vat_percent')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="currency_code" class="mb-1 flex flex-wrap items-center gap-1 text-sm font-medium text-gray-700">
                        رمز العملة (عرض)
                        <x-info field="company_currency_code" />
                    </label>
                    <input
                        type="text"
                        id="currency_code"
                        name="currency_code"
                        value="{{ old('currency_code', $setting->currency_code ?? 'SAR') }}"
                        maxlength="10"
                        class="w-full max-w-xs px-3 py-2.5 pr-4 text-right border border-gray-300 rounded-lg text-sm uppercase tabular-nums focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                        placeholder="SAR"
                        autocomplete="off"
                    >
                    <p class="mt-1 text-xs text-gray-500">يُستخدم في شاشات نقاط البيع والتقارير؛ القيمة الافتراضية SAR.</p>
                    @error('currency_code')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="commercial_register" class="block text-sm font-medium text-gray-700 mb-1">السجل التجاري</label>
                    <input type="text" id="commercial_register" name="commercial_register" value="{{ old('commercial_register', $setting->commercial_register) }}" class="w-full px-3 py-2.5 pr-4 text-right border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500" placeholder="رقم السجل التجاري">
                    @error('commercial_register')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="address" class="block text-sm font-medium text-gray-700 mb-1">العنوان</label>
                    <textarea id="address" name="address" rows="3" class="w-full px-3 py-2.5 pr-4 text-right border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500" placeholder="عنوان المنشأة">{{ old('address', $setting->address) }}</textarea>
                    @error('address')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="logo_url" class="block text-sm font-medium text-gray-700 mb-1">رابط اللوجو (اختياري)</label>
                    <input type="text" id="logo_url" name="logo_url" value="{{ old('logo_url', $setting->logo_url && !str_starts_with($setting->logo_url, 'company/') ? $setting->logo_url : '') }}" class="w-full px-3 py-2.5 pr-4 text-right border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500" placeholder="https://... أو اتركه فارغاً واستخدم رفع الملف">
                    @error('logo_url')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="logo_file" class="block text-sm font-medium text-gray-700 mb-1">رفع لوجو (اختياري)</label>
                    <input type="file" id="logo_file" name="logo_file" accept="image/*" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm text-right">
                    @if($setting->logo_url && str_starts_with($setting->logo_url, 'company/'))
                        <p class="mt-1 text-sm text-gray-500">اللوجو الحالي: <img src="{{ asset('storage/' . $setting->logo_url) }}" alt="Logo" class="h-10 inline-block align-middle ml-1"></p>
                    @endif
                    @error('logo_file')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
            </div>

            <div class="rounded-lg border border-gray-100 bg-gray-50/80 p-6 space-y-6">
                <h2 class="text-lg font-semibold text-gray-900">الربط المحاسبي العام</h2>
                <p class="text-sm text-gray-600">حسابات وسيطة تُستخدم في فواتير البيع والشراء والخصومات الافتراضية.</p>
                <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                    <div class="md:col-span-2">
                        <label class="mb-1 flex items-center gap-1 text-sm font-medium text-gray-700">
                            <x-info field="settings.company_default_receivable_account" />
                            الحساب الافتراضي للعملاء (ذمم مدينة)
                            <span class="text-red-500">*</span>
                        </label>
                        <x-searchable-select
                            name="default_receivable_account_id"
                            id="default_receivable_account_id"
                            :options="$receivableOpts ?? []"
                            :value="old('default_receivable_account_id', $setting->default_receivable_account_id)"
                            :required="true"
                            :error="$errors->has('default_receivable_account_id')"
                            empty-label="اختر حساباً"
                            placeholder="ابحث بالرمز أو الاسم..."
                        />
                        @error('default_receivable_account_id')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>
                    <div class="md:col-span-2">
                        <label class="mb-1 flex items-center gap-1 text-sm font-medium text-gray-700">
                            <x-info field="settings.company_default_payable_account" />
                            الحساب الافتراضي للموردين (ذمم دائنة)
                            <span class="text-red-500">*</span>
                        </label>
                        <x-searchable-select
                            name="default_payable_account_id"
                            id="default_payable_account_id"
                            :options="$payableOpts ?? []"
                            :value="old('default_payable_account_id', $setting->default_payable_account_id)"
                            :required="true"
                            :error="$errors->has('default_payable_account_id')"
                            empty-label="اختر حساباً"
                            placeholder="ابحث بالرمز أو الاسم..."
                        />
                        @error('default_payable_account_id')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="mb-1 flex items-center gap-1 text-sm font-medium text-gray-700">
                            <x-info field="settings.company_purchase_discount_ledger_account" />
                            حساب خصم المشتريات (مكتسب)
                            <span class="text-red-500">*</span>
                        </label>
                        <x-searchable-select
                            name="purchase_discount_ledger_account_id"
                            id="purchase_discount_ledger_account_id"
                            :options="$purchaseDiscOpts ?? []"
                            :value="old('purchase_discount_ledger_account_id', $setting->purchase_discount_ledger_account_id)"
                            :required="true"
                            :error="$errors->has('purchase_discount_ledger_account_id')"
                            empty-label="اختر حساب مصروف"
                            placeholder="ابحث بالرمز أو الاسم..."
                        />
                        @error('purchase_discount_ledger_account_id')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="mb-1 flex items-center gap-1 text-sm font-medium text-gray-700">
                            <x-info field="settings.company_sales_allowed_discount_ledger_account" />
                            حساب خصم المبيعات (مسموح)
                            <span class="text-red-500">*</span>
                        </label>
                        <x-searchable-select
                            name="sales_allowed_discount_ledger_account_id"
                            id="sales_allowed_discount_ledger_account_id"
                            :options="$salesDiscOpts ?? []"
                            :value="old('sales_allowed_discount_ledger_account_id', $setting->sales_allowed_discount_ledger_account_id)"
                            :required="true"
                            :error="$errors->has('sales_allowed_discount_ledger_account_id')"
                            empty-label="اختر حساب إيراد"
                            placeholder="ابحث بالرمز أو الاسم..."
                        />
                        @error('sales_allowed_discount_ledger_account_id')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>
                </div>
            </div>

            <div id="payroll-accounts" class="rounded-lg border border-indigo-100 bg-indigo-50/40 p-6 space-y-6 scroll-mt-24">
                <h2 class="text-lg font-semibold text-gray-900">الربط المحاسبي — مسير الرواتب</h2>
                <p class="text-sm text-gray-600">يُستخدَم تلقائياً عند اعتماد المسير (إثبات الاستحقاق) وعند دفع الرواتب (صرف فعلي).</p>
                <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                    <div class="md:col-span-2">
                        <label class="mb-1 flex items-center gap-1 text-sm font-medium text-gray-700">
                            <x-info field="settings.company_payroll_wage_expense_account" />
                            حساب مصروف الأجور (يُسجَّل عند الاعتماد)
                            <span class="text-amber-600 text-xs" title="مطلوب قبل اعتماد مسير بصافي &gt; 0">موصى به</span>
                        </label>
                        <x-searchable-select
                            name="payroll_wage_expense_account_id"
                            id="payroll_wage_expense_account_id"
                            :options="$payrollExpenseOpts ?? []"
                            :value="old('payroll_wage_expense_account_id', $setting->payroll_wage_expense_account_id)"
                            :required="false"
                            :error="$errors->has('payroll_wage_expense_account_id')"
                            :emptyOption="true"
                            empty-label="اختر حساب مصروف"
                            placeholder="ابحث بالرمز أو الاسم..."
                        />
                        @error('payroll_wage_expense_account_id')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>
                    <div class="md:col-span-2">
                        <label class="mb-1 flex items-center gap-1 text-sm font-medium text-gray-700">
                            <x-info field="settings.company_payroll_wages_payable_account" />
                            حساب الأجور المستحقة (التزم حتى الدفع)
                            <span class="text-amber-600 text-xs" title="مطلوب لاعتماد مسير بصافي &gt; 0">موصى به</span>
                        </label>
                        <x-searchable-select
                            name="payroll_wages_payable_account_id"
                            id="payroll_wages_payable_account_id"
                            :options="$payrollPayableOpts ?? []"
                            :value="old('payroll_wages_payable_account_id', $setting->payroll_wages_payable_account_id)"
                            :required="false"
                            :error="$errors->has('payroll_wages_payable_account_id')"
                            :emptyOption="true"
                            empty-label="اختر حساب خصوم"
                            placeholder="ابحث بالرمز أو الاسم..."
                        />
                        @error('payroll_wages_payable_account_id')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>
                    <div class="md:col-span-2">
                        <label class="mb-1 flex items-center gap-1 text-sm font-medium text-gray-700">
                            <x-info field="settings.company_payroll_default_payment_account" />
                            الحساب الافتراضي للصرف (خزينة/بنك) — اقتراح عند دفع الرواتب
                        </label>
                        <x-searchable-select
                            name="payroll_default_payment_account_id"
                            id="payroll_default_payment_account_id"
                            :options="$payrollCashOpts ?? []"
                            :value="old('payroll_default_payment_account_id', $setting->payroll_default_payment_account_id)"
                            :required="false"
                            :error="$errors->has('payroll_default_payment_account_id')"
                            :emptyOption="true"
                            empty-label="— اختر (اختياري) —"
                            placeholder="ابحث بالرمز أو الاسم..."
                        />
                        @error('payroll_default_payment_account_id')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>
                </div>
            </div>

            <div class="mt-6 flex gap-2">
                <button type="submit" class="px-4 py-2.5 rounded-lg text-white text-sm font-medium" style="background: #2563eb;">حفظ</button>
                <a href="{{ route('dashboard') }}" class="px-4 py-2.5 rounded-lg border border-gray-300 bg-white text-gray-700 text-sm font-medium hover:bg-gray-50">إلغاء</a>
            </div>
        </form>
    </div>

    @if(\App\Support\ErpRoles::canRunSystemFinancialMaintenance(auth()->user()))
        @php
            $purgeOpts = collect($purgeUserOpts ?? [])->map(fn ($u) => [
                'value' => $u->id,
                'label' => trim((string) $u->name).' ('.(string) $u->email.')',
            ])->values()->all();
        @endphp
        <div class="rounded-xl border border-red-200 bg-red-50/40 p-6 shadow-sm space-y-6">
            <div>
                <h2 class="flex flex-wrap items-center gap-2 text-lg font-semibold text-gray-900">
                    <x-info field="settings_system_maintenance_heading" />
                    صيانة النظام
                </h2>
                <p class="mt-2 text-sm text-gray-700">مسح جراحي للبيانات المالية لمستخدم محدد؛ لا يمكن التراجع.</p>
            </div>
            <form method="POST" action="{{ route('settings.system-maintenance.super-purge') }}" class="flex flex-col gap-4 lg:flex-row lg:items-end lg:gap-6" onsubmit="return confirm('تأكيد: سيتم حذف جميع المدفوعات (بما فيها المصروفات) والقيود اليومية ودليل الحسابات لهذا المستخدم نهائياً. لا يمكن التراجع.');">
                @csrf
                <div class="min-w-0 flex-1 max-w-xl">
                    <label for="super_purge_target_user_id" class="mb-1 flex flex-wrap items-center gap-2 text-sm font-medium text-gray-800">
                        <x-info field="settings_system_maintenance_purge_user" />
                        المستخدم المستهدف
                    </label>
                    <x-searchable-select
                        name="target_user_id"
                        id="super_purge_target_user_id"
                        :options="$purgeOpts"
                        :value="old('target_user_id')"
                        :required="true"
                        empty-label="اختر مستخدماً"
                        placeholder="بحث بالاسم أو البريد..."
                        class="[&_button]:h-11 [&_button]:rounded-lg"
                    />
                    @error('target_user_id')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
                <div class="flex flex-wrap items-center gap-3">
                    <button type="submit" class="inline-flex h-11 shrink-0 items-center justify-center rounded-lg bg-red-600 px-6 text-sm font-semibold text-white shadow-sm hover:bg-red-700">
                        مسح البيانات المالية للمستخدم
                    </button>
                    <x-info field="settings_system_maintenance_purge_button" />
                </div>
            </form>
        </div>
    @endif
</div>
@endsection
