@extends('layouts.app')

@section('title', 'الحسابات البنكية - MIRADA ERP')

@section('breadcrumb')
    <a href="{{ route('dashboard') }}" class="text-gray-500 hover:text-indigo-600">الرئيسية</a>
    <span>›</span>
    <a href="{{ route('finance.dashboard') }}" class="text-gray-500 hover:text-indigo-600">المحاسبة</a>
    <span>›</span>
    <span class="text-indigo-900 font-semibold">الحسابات البنكية</span>
@endsection

@section('content')
<div dir="rtl" class="content-wrap">
    <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-8 text-center">
        <div class="w-16 h-16 rounded-full bg-purple-100 text-purple-600 flex items-center justify-center mx-auto mb-4">
            <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" fill="currentColor" viewBox="0 0 16 16"><path d="M0 3a2 2 0 0 1 2-2h13.5a.5.5 0 0 1 .5.5v1a.5.5 0 0 1-.5.5H15v2a1 1 0 0 1 1 1v3.5a1.5 1.5 0 0 1-1.5 1.5h-12A2.5 2.5 0 0 1 0 12.5V3zm1 1.732V12.5A1.5 1.5 0 0 0 2.5 14h12a.5.5 0 0 0 .5-.5V5H2a1.99 1.99 0 0 1-1-.268zM1 3a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1V2H2a1 1 0 0 0-1 1z"/></svg>
        </div>
        <h2 class="text-xl font-bold text-gray-800 mb-2">الحسابات البنكية</h2>
        <p class="text-gray-500 mb-6">هذا القسم قيد التطوير. سيتم تفعيله قريباً.</p>
        <a href="{{ route('finance.dashboard') }}" class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700">← العودة للوحة المحاسبة</a>
    </div>
</div>
@endsection
