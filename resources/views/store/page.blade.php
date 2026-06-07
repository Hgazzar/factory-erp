@extends('layouts.store')

@section('title', ($pageTitle ?? 'صفحة').' — '.$storeName)

@section('content')
<div class="max-w-3xl mx-auto px-4 py-12">
    <a href="{{ $routes['home'] }}" class="flex items-center gap-2 text-gray-500 hover:text-store-primary transition-colors mb-6">
        <i class="fas fa-arrow-right"></i>
        <span class="font-bold">العودة للمتجر</span>
    </a>
    <h1 class="text-3xl font-black text-gray-800 mb-8">{{ $pageTitle }}</h1>
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-8 prose prose-sm max-w-none text-gray-600 leading-relaxed">
        @if(!empty($pageBody))
            {!! nl2br(e(strip_tags($pageBody))) !!}
        @else
            <p class="text-gray-400">لم تُضف محتوى هذه الصفحة بعد من إعدادات المتجر.</p>
        @endif
    </div>
</div>
@endsection
