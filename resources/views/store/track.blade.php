@extends('layouts.store')

@section('title', 'تتبع الطلب — '.$storeName)

@section('content')
<div class="max-w-xl mx-auto px-4 py-12">
    <a href="{{ $routes['home'] }}" class="flex items-center gap-2 text-gray-500 hover:text-store-primary transition-colors mb-6">
        <i class="fas fa-arrow-right"></i>
        <span class="font-bold">العودة للمتجر</span>
    </a>
    <h1 class="text-3xl font-black text-gray-800 mb-2">تتبع <span class="gradient-text">الطلب</span></h1>
    @if(!empty($pageHelp))
        <p class="text-gray-400 text-sm mb-8">{{ $pageHelp }}</p>
    @endif

    <form method="post" action="{{ $routes['track'] }}" class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 space-y-4 mb-8">
        @csrf
        <div>
            <label class="text-sm font-bold text-gray-600 mb-1 block">رقم الفاتورة</label>
            <input type="text" name="invoice_number" value="{{ old('invoice_number') }}" required
                   class="w-full px-4 py-3 rounded-xl border border-gray-200 focus:border-red-500 outline-none text-sm">
        </div>
        <div>
            <label class="text-sm font-bold text-gray-600 mb-1 block">رقم الجوال</label>
            <input type="tel" name="customer_phone" value="{{ old('customer_phone') }}" required dir="ltr"
                   class="w-full px-4 py-3 rounded-xl border border-gray-200 focus:border-red-500 outline-none text-sm">
        </div>
        <button type="submit" class="w-full py-4 bg-store-gradient text-white rounded-xl font-bold hover-shadow-store transition-all">
            <i class="fas fa-search ml-2"></i>بحث
        </button>
    </form>

    @if(!empty($trackError))
        <div class="bg-red-50 text-red-600 rounded-xl p-4 text-sm font-bold mb-6">{{ $trackError }}</div>
    @endif

    @if(!empty($trackedSale))
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
            <div class="flex justify-between text-sm mb-4">
                <span class="text-gray-500">رقم الطلب</span>
                <span class="font-bold text-store-primary">#{{ $trackedSale->invoice_number }}</span>
            </div>
            <div class="flex justify-between text-sm mb-4">
                <span class="text-gray-500">الحالة</span>
                <span class="font-bold">{{ $trackedSale->status ?? 'قيد المعالجة' }}</span>
            </div>
            <div class="flex justify-between text-sm">
                <span class="text-gray-500">الإجمالي</span>
                <span class="font-black gradient-text">{{ number_format((float) $trackedSale->total_amount, 2) }} {{ $currencyCode }}</span>
            </div>
        </div>
    @endif
</div>
@endsection
