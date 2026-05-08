@extends('layouts.crm')

@section('title', ($pageTitle ?? 'CRM').' - '.config('app.name'))

@section('breadcrumb')
    <a href="{{ route('dashboard') }}" class="text-gray-500 hover:text-indigo-600">الرئيسية</a>
    <span>›</span>
    <a href="{{ route('crm.dashboard') }}" class="text-gray-500 hover:text-indigo-600">إدارة العملاء</a>
    <span>›</span>
    <span class="text-indigo-900 font-semibold truncate">{{ $pageTitle ?? 'CRM' }}</span>
@endsection

@section('content')
<div class="bg-white rounded-lg border border-gray-200 shadow-sm p-8 max-w-2xl mx-auto text-center" dir="rtl">
    <h1 class="text-2xl font-bold text-gray-900 mb-2">{{ $pageTitle ?? '' }}</h1>
    <p class="text-sm text-gray-600 leading-relaxed">{{ $pageDescription ?? '' }}</p>
    <a href="{{ route('crm.dashboard') }}" class="inline-block mt-6 text-indigo-600 font-medium hover:text-indigo-800">العودة للوحة إدارة العملاء</a>
</div>
@endsection
