@extends('layouts.crm')

@section('title', 'إعدادات CRM')

@section('breadcrumb')
    <a href="{{ route('dashboard') }}" class="text-gray-500 hover:text-indigo-600">الرئيسية</a>
    <span>›</span>
    <a href="{{ route('crm.dashboard') }}" class="text-gray-500 hover:text-indigo-600">إدارة العملاء</a>
    <span>›</span>
    <span class="text-indigo-900 font-semibold truncate">الإعدادات</span>
@endsection

@section('content')
<div class="bg-white rounded-xl border border-gray-200 shadow-sm p-8 text-center text-gray-600">
    <h1 class="text-2xl font-bold text-gray-900 mb-2">إعدادات إدارة العملاء</h1>
    <p class="text-sm">سيتم إضافة إعدادات الموديول (مصادر، مسارات، تكاملات) لاحقاً.</p>
    <a href="{{ route('crm.dashboard') }}" class="inline-block mt-6 text-indigo-600 font-medium hover:text-indigo-800">العودة للوحة CRM</a>
</div>
@endsection
