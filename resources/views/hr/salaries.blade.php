@extends('layouts.app')

@section('title', 'الرواتب — الموارد البشرية')

@section('breadcrumb')
    <a href="{{ route('dashboard') }}" class="text-gray-500 hover:text-indigo-600">الرئيسية</a>
    <span>›</span>
    <a href="{{ route('hr.dashboard') }}" class="text-gray-500 hover:text-indigo-600">الموارد البشرية</a>
    <span>›</span>
    <span class="text-indigo-900 font-semibold truncate">الرواتب</span>
@endsection

@section('content')
<div class="max-w-full rounded-lg border border-gray-200 bg-white p-6 shadow-sm" dir="rtl">
    <h1 class="text-xl font-bold text-gray-900">الرواتب</h1>
    <p class="mt-2 text-sm text-gray-500">سيتم تفعيل هذه الوحدة لاحقاً.</p>
    <a href="{{ route('hr.dashboard') }}" class="mt-6 inline-flex rounded-lg border border-gray-200 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">العودة للوحة الموارد البشرية</a>
</div>
@endsection
