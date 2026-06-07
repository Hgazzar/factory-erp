@extends('layouts.store')

@section('title', 'تم تأكيد الطلب — '.$storeName)

@section('content')
<div class="max-w-lg mx-auto px-4 py-20 text-center">
    <div class="bounce-in">
        <div class="w-28 h-28 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-6">
            <i class="fas fa-check text-5xl text-green-500"></i>
        </div>
    </div>
    <h2 class="text-3xl font-black text-gray-800 mb-3">تم تأكيد طلبك بنجاح! 🎉</h2>
    <p class="text-gray-400 mb-6">شكراً لتسوقك معنا. سيتم شحن طلبك قريباً</p>
    <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100 mb-8 text-right">
        <div class="flex justify-between text-sm mb-3">
            <span class="text-gray-500">رقم الطلب</span>
            <span class="font-bold text-store-primary">#{{ $sale->invoice_number }}</span>
        </div>
        <div class="flex justify-between text-sm mb-3">
            <span class="text-gray-500">المنتجات</span>
            <span class="font-bold">{{ $sale->items->count() }} منتج</span>
        </div>
        <div class="flex justify-between text-sm">
            <span class="text-gray-500">المجموع</span>
            <span class="font-black gradient-text">{{ number_format((float) $sale->total_amount, 2) }} {{ $currencyCode }}</span>
        </div>
    </div>
    <a href="{{ $routes['home'] }}" class="inline-block px-10 py-4 bg-store-gradient text-white rounded-xl font-bold hover-shadow-store transition-all hover:-translate-y-1">
        <i class="fas fa-shopping-bag ml-2"></i>متابعة التسوق
    </a>
</div>
@endsection
