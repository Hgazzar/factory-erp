@extends('layouts.crm')

@section('title', $customer->display_name.' — جهة اتصال — CRM — '.config('app.name'))

@section('breadcrumb')
    <a href="{{ route('dashboard') }}" class="text-gray-500 hover:text-indigo-600">الرئيسية</a>
    <span>›</span>
    <a href="{{ route('crm.dashboard') }}" class="text-gray-500 hover:text-indigo-600">إدارة العملاء</a>
    <span>›</span>
    <a href="{{ route('crm.customers.index') }}" class="text-gray-500 hover:text-indigo-600">جهات الاتصال</a>
    <span>›</span>
    <span class="text-indigo-900 font-semibold truncate">{{ $customer->display_name }}</span>
@endsection

@section('content')
<div class="space-y-6" dir="rtl">
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div class="min-w-0 flex-1">
            <div class="flex flex-wrap items-center gap-2">
                <h1 class="text-2xl font-bold text-gray-900">{{ $customer->display_name }}</h1>
                <span class="inline-flex shrink-0"><x-info field="crm.customer_crm_card_intro" /></span>
            </div>
            @if($customer->code)<p class="text-sm text-gray-500 mt-1 mb-0 tabular-nums">{{ $customer->code }}</p>@endif
        </div>
        <div class="flex flex-wrap items-center gap-2 shrink-0">
            <a href="{{ route('sales.customers.show', $customer) }}" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-lg border border-gray-300 bg-white text-gray-700 text-sm font-medium hover:bg-gray-50 no-underline">بطاقة المبيعات</a>
            <a href="{{ route('crm.customers.index') }}" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-lg bg-blue-600 text-white text-sm font-medium hover:bg-blue-700 no-underline shadow-sm border-0">رجوع لجهات الاتصال</a>
        </div>
    </div>

    @include('crm.partials.customer-profile-body', ['customer' => $customer])
</div>
@endsection
