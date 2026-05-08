@extends('layouts.crm')

@section('title', 'عضوية جديدة — CRM — '.config('app.name'))

@section('breadcrumb')
    <a href="{{ route('dashboard') }}" class="text-gray-500 hover:text-indigo-600">الرئيسية</a>
    <span>›</span>
    <a href="{{ route('crm.dashboard') }}" class="text-gray-500 hover:text-indigo-600">إدارة العملاء</a>
    <span>›</span>
    <a href="{{ route('crm.memberships.index') }}" class="text-gray-500 hover:text-indigo-600">العضويات</a>
    <span>›</span>
    <span class="text-indigo-900 font-semibold truncate">عضوية جديدة</span>
@endsection

@section('content')
<div class="space-y-6" dir="rtl">
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <div class="inline-flex items-center gap-3">
            <span class="inline-flex h-10 w-10 items-center justify-center rounded-xl bg-cyan-100 text-cyan-700">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="currentColor" viewBox="0 0 16 16" aria-hidden="true"><path d="M8 8a3 3 0 1 0 0-6 3 3 0 0 0 0 6m4 1H4a4 4 0 0 0-4 4v1h16v-1a4 4 0 0 0-4-4"/></svg>
            </span>
            <h1 class="text-2xl font-bold text-gray-900 inline-flex items-center gap-2">
                عضوية جديدة
                <x-info field="crm.memberships_create" />
            </h1>
        </div>
            <p class="mt-1 text-xs text-gray-500 font-mono">#{{ $nextMembershipCode ?? 'MEM-001' }}</p>
        </div>
    </div>

    <form method="POST" action="{{ route('crm.memberships.store') }}" class="space-y-6">
        @csrf

        <div class="rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="text-lg font-semibold text-gray-900 mb-4 inline-flex items-center gap-2">
                تفاصيل العضوية
                <x-info field="crm.membership_details" />
            </h2>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label for="customer_id" class="block text-sm font-medium text-gray-700 mb-1"><span class="inline-flex items-center gap-1">العميل <x-info field="crm.membership_customer" /></span></label>
                    <x-searchable-select
                        name="customer_id"
                        id="customer_id"
                        :options="$customerOptions ?? []"
                        :value="old('customer_id', '')"
                        :empty-option="false"
                        placeholder="اختر العميل"
                    />
                    @error('customer_id')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="loyalty_program_id" class="block text-sm font-medium text-gray-700 mb-1"><span class="inline-flex items-center gap-1">الخطة <x-info field="crm.membership_plan" /></span></label>
                    <x-searchable-select
                        name="loyalty_program_id"
                        id="loyalty_program_id"
                        :options="$planOptions ?? []"
                        :value="old('loyalty_program_id', '')"
                        :empty-option="false"
                        placeholder="اختر الخطة"
                    />
                    @error('loyalty_program_id')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="start_date" class="block text-sm font-medium text-gray-700 mb-1"><span class="inline-flex items-center gap-1">تاريخ البدء <x-info field="crm.membership_start_date" /></span></label>
                    <input id="start_date" name="start_date" type="date" value="{{ old('start_date') }}" class="block w-full min-h-[2.75rem] rounded-lg border border-gray-300 py-2.5 px-3 text-sm text-gray-900 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    <p class="mt-1 text-xs text-gray-500">اتركه فارغاً للبدء فوراً.</p>
                    @error('start_date')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div class="flex items-end">
                    <div class="w-full rounded-lg border border-gray-200 bg-gray-50/70 px-4 py-3">
                        <div class="d-flex align-items-center justify-content-between gap-3 flex-wrap">
                            <div class="min-w-0">
                                <p class="mb-0 text-sm font-medium text-gray-800 inline-flex items-center gap-1">تجديد تلقائي <x-info field="crm.membership_auto_renew" /></p>
                                <p class="mb-0 mt-1 text-xs text-gray-500">تجديد العضوية تلقائياً عند انتهائها</p>
                            </div>
                            <div class="form-check form-switch form-switch-lg ps-0 mb-0">
                                <input type="hidden" name="auto_renew" value="0">
                                <input class="form-check-input m-0" type="checkbox" role="switch" id="auto_renew" name="auto_renew" value="1" @checked(old('auto_renew', '1') === '1')>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="mt-4">
                <label for="notes" class="block text-sm font-medium text-gray-700 mb-1"><span class="inline-flex items-center gap-1">ملاحظات <x-info field="crm.membership_notes" /></span></label>
                <textarea id="notes" name="notes" rows="4" class="block w-full rounded-lg border border-gray-300 py-2.5 px-3 text-sm text-gray-900 focus:ring-2 focus:ring-blue-500 focus:border-blue-500" placeholder="أضف أي ملاحظة داخلية...">{{ old('notes') }}</textarea>
                @error('notes')
                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                @enderror
            </div>
        </div>

        <div class="flex flex-wrap items-center gap-3 justify-end w-full">
            <button type="submit" class="px-5 py-2.5 rounded-lg bg-blue-600 text-white text-sm font-semibold hover:bg-blue-700 transition border-0 shadow-sm min-h-[2.75rem] inline-flex items-center justify-center">إنشاء</button>
            <a href="{{ route('crm.memberships.index') }}" class="px-5 py-2.5 rounded-lg border border-gray-300 bg-white text-gray-800 text-sm font-semibold hover:bg-gray-50 transition no-underline min-h-[2.75rem] inline-flex items-center justify-center">إلغاء</a>
        </div>
    </form>
</div>
@endsection

