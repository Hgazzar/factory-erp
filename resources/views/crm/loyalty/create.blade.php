@extends('layouts.crm')

@section('title', 'برنامج ولاء جديد — CRM')

@section('breadcrumb')
    <a href="{{ route('dashboard') }}" class="text-gray-500 hover:text-indigo-600">الرئيسية</a>
    <span>›</span>
    <a href="{{ route('crm.dashboard') }}" class="text-gray-500 hover:text-indigo-600">إدارة العملاء</a>
    <span>›</span>
    <a href="{{ route('crm.loyalty.index') }}" class="text-gray-500 hover:text-indigo-600">برامج الولاء</a>
    <span>›</span>
    <span class="text-indigo-900 font-semibold truncate">برنامج جديد</span>
@endsection

@php
    $expChecked = filter_var(old('has_expiration', false), FILTER_VALIDATE_BOOLEAN);
@endphp

@section('content')
<div class="space-y-6" dir="rtl">
    <div class="flex flex-wrap items-center justify-between gap-4">
        <h1 class="text-2xl font-bold text-gray-900 inline-flex items-center gap-2">
            برنامج ولاء جديد
            <x-info field="crm.loyalty_create_intro" />
        </h1>
    </div>

    <form method="POST" action="{{ route('crm.loyalty.store') }}" class="space-y-6" x-data="{ exp: {{ $expChecked ? 'true' : 'false' }} }">
        @csrf

        {{-- الأساسيات (شارة داخل عنوان الصف لئلا تضيق شبكة الحقول) --}}
        <div class="bg-white rounded-lg border border-gray-200 shadow-sm p-6">
            <div class="flex flex-wrap items-center gap-3 mb-4">
                <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-gradient-to-br from-amber-200 via-amber-400 to-amber-600 text-amber-950 shadow-inner ring-1 ring-amber-300/80" aria-hidden="true">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-7 w-7" fill="currentColor" viewBox="0 0 16 16"><path d="M3.612 15.443c-.386.198-.824-.149-.746-.592l.83-4.73L.173 6.765c-.329-.314-.158-.888.282-.95l4.898-.696L7.538.792a.513.513 0 0 1 .927 0l2.184 4.327 4.898.696c.441.062.612.636.282.95l-3.522 3.356.83 4.73c.078.443-.36.79-.746.592L8 13.187z"/></svg>
                </div>
                <h2 class="text-base font-semibold text-gray-900 min-w-0">المعلومات الأساسية</h2>
            </div>
            <div class="space-y-4">
                {{-- سطر واحد: الرمز + الاسم + الاسم بالعربية (تمرير أفقي على الشاشات الضيقة) --}}
                <div class="flex min-w-0 flex-row flex-nowrap items-start gap-4 overflow-x-auto pb-0.5 [-webkit-overflow-scrolling:touch]">
                    <div class="w-[11rem] max-w-[11rem] shrink-0">
                        <label for="loyalty-code-preview" class="block text-sm font-medium text-gray-700 mb-1"><span class="inline-flex items-center gap-1">الرمز <x-info field="crm.loyalty_code" /></span></label>
                        <input id="loyalty-code-preview" type="text" value="{{ $nextCode ?? 'LOY-0001' }}" readonly class="block w-full min-h-[2.75rem] rounded-lg border border-gray-300 bg-gray-50 py-2.5 px-3 text-sm text-gray-700">
                    </div>
                    <div class="min-w-0 flex-1 basis-0">
                        <label for="loyalty-name" class="block text-sm font-medium text-gray-700 mb-1"><span class="inline-flex items-center gap-1">الاسم <x-info field="crm.loyalty_name" /></span></label>
                        <input id="loyalty-name" name="name" type="text" value="{{ old('name') }}" placeholder="مثال: برنامج VIP" dir="auto" autocomplete="organization" class="block w-full min-h-[2.75rem] min-w-0 rounded-lg border border-gray-300 py-2.5 px-3 text-sm text-gray-900 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        @error('name')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                    <div class="min-w-0 flex-1 basis-0">
                        <label for="loyalty-name-ar" class="block text-sm font-medium text-gray-700 mb-1"><span class="inline-flex items-center gap-1">الاسم بالعربية <x-info field="crm.loyalty_name_ar" /></span></label>
                        <input id="loyalty-name-ar" name="name_ar" type="text" value="{{ old('name_ar') }}" placeholder="مثال: برنامج المكافآت" dir="rtl" lang="ar" autocomplete="off" class="block w-full min-h-[2.75rem] min-w-0 max-w-none rounded-lg border border-gray-300 py-2.5 px-3 text-sm text-gray-900 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        @error('name_ar')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
                <div>
                    <label for="loyalty-desc" class="block text-sm font-medium text-gray-700 mb-1"><span class="inline-flex items-center gap-1">الوصف <x-info field="crm.loyalty_description" /></span></label>
                    <textarea id="loyalty-desc" name="description" rows="3" placeholder="ملاحظات داخلية أو وصف مختصر للعميل النهائي" class="block w-full rounded-lg border border-gray-300 py-2.5 px-3 text-sm text-gray-900 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">{{ old('description') }}</textarea>
                    @error('description')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>
        </div>

        {{-- النقاط --}}
        <div class="bg-white rounded-lg border border-gray-200 shadow-sm p-6 space-y-4">
            <h2 class="text-base font-semibold text-gray-900">إعدادات النقاط</h2>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label for="points-name" class="block text-sm font-medium text-gray-700 mb-1"><span class="inline-flex items-center gap-1">اسم النقاط <x-info field="crm.loyalty_points_name" /></span></label>
                    <input id="points-name" name="points_name" type="text" value="{{ old('points_name') }}" placeholder="نقطة" class="block w-full min-h-[2.75rem] rounded-lg border border-gray-300 py-2.5 px-3 text-sm text-gray-900 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    @error('points_name')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label for="earning-rate" class="block text-sm font-medium text-gray-700 mb-1"><span class="inline-flex items-center gap-1">معدل الكسب <x-info field="crm.loyalty_earning_rate" /></span></label>
                    <input id="earning-rate" name="earning_rate" type="number" step="0.01" min="0" value="{{ old('earning_rate') }}" placeholder="10.00" class="block w-full min-h-[2.75rem] rounded-lg border border-gray-300 py-2.5 px-3 text-sm text-gray-900 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    @error('earning_rate')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label for="redemption-rate" class="block text-sm font-medium text-gray-700 mb-1"><span class="inline-flex items-center gap-1">معدل الاستبدال <x-info field="crm.loyalty_redemption_rate" /></span></label>
                    <input id="redemption-rate" name="redemption_rate" type="number" step="0.0001" min="0" value="{{ old('redemption_rate') }}" placeholder="0.1000" class="block w-full min-h-[2.75rem] rounded-lg border border-gray-300 py-2.5 px-3 text-sm text-gray-900 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    @error('redemption_rate')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label for="min-tx" class="block text-sm font-medium text-gray-700 mb-1"><span class="inline-flex items-center gap-1">الحد الأدنى للمعاملة <x-info field="crm.loyalty_min_transaction" /></span></label>
                    <input id="min-tx" name="min_transaction_amount" type="number" step="0.01" min="0" value="{{ old('min_transaction_amount', '0') }}" class="block w-full min-h-[2.75rem] rounded-lg border border-gray-300 py-2.5 px-3 text-sm text-gray-900 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    @error('min_transaction_amount')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label for="min-redeem" class="block text-sm font-medium text-gray-700 mb-1"><span class="inline-flex items-center gap-1">الحد الأدنى للاستبدال <x-info field="crm.loyalty_min_redemption_points" /></span></label>
                    <input id="min-redeem" name="min_redemption_points" type="number" step="0.01" min="0" value="{{ old('min_redemption_points', '0') }}" class="block w-full min-h-[2.75rem] rounded-lg border border-gray-300 py-2.5 px-3 text-sm text-gray-900 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    @error('min_redemption_points')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label for="max-redeem-pct" class="block text-sm font-medium text-gray-700 mb-1"><span class="inline-flex items-center gap-1">أقصى نسبة استبدال % <x-info field="crm.loyalty_max_redemption_percentage" /></span></label>
                    <input id="max-redeem-pct" name="max_redemption_percentage" type="number" step="0.01" min="0" max="100" value="{{ old('max_redemption_percentage') }}" placeholder="اختياري" class="block w-full min-h-[2.75rem] rounded-lg border border-gray-300 py-2.5 px-3 text-sm text-gray-900 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    @error('max_redemption_percentage')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label for="tiers-count" class="block text-sm font-medium text-gray-700 mb-1"><span class="inline-flex items-center gap-1">المستويات <x-info field="crm.loyalty_tiers_count" /></span></label>
                    <input id="tiers-count" name="tiers_count" type="number" min="1" max="20" value="{{ old('tiers_count', 1) }}" class="block w-full min-h-[2.75rem] rounded-lg border border-gray-300 py-2.5 px-3 text-sm text-gray-900 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    @error('tiers_count')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label for="loyalty-status" class="block text-sm font-medium text-gray-700 mb-1"><span class="inline-flex items-center gap-1">الحالة <x-info field="crm.loyalty_status" /></span></label>
                    <x-searchable-select
                        name="status"
                        id="loyalty-status"
                        :options="$statusOptions ?? []"
                        :value="old('status', 'active')"
                        empty-label="اختر"
                        placeholder="اختر الحالة"
                        :searchable="false"
                    />
                    @error('status')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            {{-- مفاتيح --}}
            <div class="bg-white rounded-lg border border-gray-200 shadow-sm p-6 space-y-5">
                <h2 class="text-base font-semibold text-gray-900">خيارات الكسب</h2>
                <div class="space-y-4">
                    <div class="flex items-center justify-between gap-4 rounded-lg border border-gray-100 bg-gray-50/80 px-4 py-3">
                        <div>
                            <p class="text-sm font-medium text-gray-900"><span class="inline-flex items-center gap-1">الكسب على الخصومات <x-info field="crm.loyalty_earn_on_discounts" /></span></p>
                            <p class="text-xs text-gray-500 mt-0.5">عند الإيقاف، الجزء المخفَّض لا يُحتسب في منطق الكسب لاحقاً.</p>
                        </div>
                        <label class="inline-flex cursor-pointer items-center shrink-0">
                            <input type="hidden" name="earn_on_discounts" value="0">
                            <input type="checkbox" name="earn_on_discounts" value="1" @checked(old('earn_on_discounts')) class="h-6 w-6 rounded-lg border-gray-300 text-blue-600 focus:ring-blue-500">
                        </label>
                    </div>
                    <div class="flex items-center justify-between gap-4 rounded-lg border border-gray-100 bg-gray-50/80 px-4 py-3">
                        <div>
                            <p class="text-sm font-medium text-gray-900"><span class="inline-flex items-center gap-1">الكسب على الضريبة <x-info field="crm.loyalty_earn_on_tax" /></span></p>
                            <p class="text-xs text-gray-500 mt-0.5">يحدد ما إذا كان مبلغ الضريبة يدخل في أساس احتساب النقاط.</p>
                        </div>
                        <label class="inline-flex cursor-pointer items-center shrink-0">
                            <input type="hidden" name="earn_on_tax" value="0">
                            <input type="checkbox" name="earn_on_tax" value="1" @checked(old('earn_on_tax')) class="h-6 w-6 rounded-lg border-gray-300 text-blue-600 focus:ring-blue-500">
                        </label>
                    </div>
                </div>
            </div>

            {{-- الصلاحية --}}
            <div class="bg-white rounded-lg border border-gray-200 shadow-sm p-6 space-y-4">
                <h2 class="text-base font-semibold text-gray-900">الصلاحية</h2>
                <div class="flex items-center justify-between gap-4 rounded-lg border border-gray-100 bg-gray-50/80 px-4 py-3">
                    <div>
                        <p class="text-sm font-medium text-gray-900"><span class="inline-flex items-center gap-1">تفعيل انتهاء البرنامج <x-info field="crm.loyalty_has_expiration" /></span></p>
                        <p class="text-xs text-gray-500 mt-0.5">عند التفعيل يجب تحديد تاريخ بداية ونهاية.</p>
                    </div>
                    <label class="inline-flex cursor-pointer items-center shrink-0">
                        <input type="hidden" name="has_expiration" value="0">
                        <input type="checkbox" name="has_expiration" value="1" @checked(old('has_expiration')) @change="exp = $event.target.checked" class="h-6 w-6 rounded-lg border-gray-300 text-blue-600 focus:ring-blue-500">
                    </label>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label for="loyalty-start" class="block text-sm font-medium text-gray-700 mb-1"><span class="inline-flex items-center gap-1">تاريخ البداية <x-info field="crm.loyalty_start_date" /></span></label>
                        <input id="loyalty-start" name="start_date" type="date" value="{{ old('start_date') }}" :disabled="!exp" :class="exp ? 'bg-white' : 'bg-gray-100 text-gray-400'" class="block w-full min-h-[2.75rem] rounded-lg border border-gray-300 py-2.5 px-3 text-sm text-gray-900 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        @error('start_date')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label for="loyalty-end" class="block text-sm font-medium text-gray-700 mb-1"><span class="inline-flex items-center gap-1">تاريخ النهاية <x-info field="crm.loyalty_end_date" /></span></label>
                        <input id="loyalty-end" name="end_date" type="date" value="{{ old('end_date') }}" :disabled="!exp" :class="exp ? 'bg-white' : 'bg-gray-100 text-gray-400'" class="block w-full min-h-[2.75rem] rounded-lg border border-gray-300 py-2.5 px-3 text-sm text-gray-900 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        @error('end_date')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg border border-gray-200 shadow-sm p-4 flex items-center border-t border-gray-200">
            <div class="ms-auto flex items-center gap-2">
                <button type="submit" class="inline-flex items-center justify-center min-h-[2.75rem] px-6 rounded-lg bg-blue-600 text-white text-sm font-semibold hover:bg-blue-700 transition shadow-sm">حفظ</button>
                <a href="{{ route('crm.loyalty.index') }}" class="inline-flex items-center justify-center min-h-[2.75rem] px-4 rounded-lg border border-gray-300 text-gray-800 text-sm font-medium hover:bg-gray-50 transition no-underline">إلغاء</a>
            </div>
        </div>
    </form>
</div>
@endsection
