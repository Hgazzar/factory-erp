@extends('layouts.app')

@section('title', 'مراكز التكلفة - MIRADA ERP')

@section('breadcrumb')
    <a href="{{ route('dashboard') }}" class="text-gray-500 hover:text-indigo-600">الرئيسية</a>
    <span>›</span>
    <a href="{{ route('finance.dashboard') }}" class="text-gray-500 hover:text-indigo-600">المحاسبة</a>
    <span>›</span>
    <span class="text-indigo-900 font-semibold">مراكز التكلفة</span>
@endsection

@section('content')
<div dir="rtl" class="content-wrap">
    <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-8 text-center">
        <div class="w-16 h-16 rounded-full bg-emerald-100 text-emerald-600 flex items-center justify-center mx-auto mb-4">
            <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" fill="currentColor" viewBox="0 0 16 16"><path d="M8 16s6-5.686 6-10A6 6 0 0 0 2 6c0 4.314 6 10 6 10zm0-7a3 3 0 1 1 0-6 3 3 0 0 1 0 6z"/></svg>
        </div>
        <h2 class="text-xl font-bold text-gray-800 mb-2">مراكز التكلفة</h2>
        <p class="text-gray-500 mb-6">هذا القسم قيد التطوير. سيتم تفعيله قريباً.</p>
        <a href="{{ route('finance.dashboard') }}" class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700">← العودة للوحة المحاسبة</a>
    </div>
</div>
@endsection
