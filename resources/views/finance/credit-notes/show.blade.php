@extends('layouts.app')

@section('title', 'عرض إشعار ائتمان - '.config('app.name'))

@section('breadcrumb')
    <a href="{{ route('dashboard') }}" class="text-gray-500 hover:text-blue-600">الرئيسية</a>
    <span class="mx-1 text-gray-400">›</span>
    <a href="{{ route('finance.dashboard') }}" class="text-gray-500 hover:text-blue-600">المحاسبة</a>
    <span class="mx-1 text-gray-400">›</span>
    <a href="{{ route('finance.credit-notes.index') }}" class="text-gray-500 hover:text-blue-600">إشعارات الائتمان</a>
    <span class="mx-1 text-gray-400">›</span>
    <span class="text-blue-900 font-semibold">{{ $creditNote->note_number }}</span>
@endsection

@section('content')
<div dir="rtl" class="mx-auto w-full max-w-full space-y-6">
    <section class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">عرض إشعار الائتمان {{ $creditNote->note_number }}</h1>
                <p class="mt-1 text-sm text-gray-500">تفاصيل إشعار ائتمان العميل</p>
            </div>
            <a href="{{ route('finance.credit-notes.index') }}" class="rounded-lg border border-gray-200 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">رجوع</a>
        </div>
    </section>

    <section class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm">
        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
            <div class="rounded-lg border border-gray-100 bg-gray-50 p-4">
                <p class="text-xs text-gray-500">رقم الإشعار</p>
                <p class="mt-1 text-sm font-semibold text-gray-900">{{ $creditNote->note_number }}</p>
            </div>
            <div class="rounded-lg border border-gray-100 bg-gray-50 p-4">
                <p class="text-xs text-gray-500">العميل</p>
                <p class="mt-1 text-sm font-semibold text-gray-900">{{ $creditNote->customer->name_ar ?: $creditNote->customer->name }}</p>
            </div>
            <div class="rounded-lg border border-gray-100 bg-gray-50 p-4">
                <p class="text-xs text-gray-500">التاريخ</p>
                <p class="mt-1 text-sm font-semibold text-gray-900">{{ optional($creditNote->date)->format('Y-m-d') }}</p>
            </div>
            <div class="rounded-lg border border-gray-100 bg-gray-50 p-4">
                <p class="text-xs text-gray-500">الحالة</p>
                <p class="mt-1 text-sm font-semibold text-gray-900">
                    @if($creditNote->status === 'approved')
                        معتمد
                    @elseif($creditNote->status === 'cancelled')
                        ملغى
                    @else
                        مسودة
                    @endif
                </p>
            </div>
            <div class="rounded-lg border border-gray-100 bg-gray-50 p-4">
                <p class="text-xs text-gray-500">مرجع فاتورة الأصل</p>
                <p class="mt-1 text-sm font-semibold text-gray-900">{{ $creditNote->original_invoice_ref ?: '-' }}</p>
            </div>
            <div class="rounded-lg border border-gray-100 bg-gray-50 p-4">
                <p class="text-xs text-gray-500">المبلغ الإجمالي</p>
                <p class="mt-1 text-sm font-semibold text-gray-900">{{ number_format((float) $creditNote->amount + (float) $creditNote->tax_amount, 2) }}</p>
            </div>
            <div class="rounded-lg border border-gray-100 bg-gray-50 p-4 md:col-span-2">
                <p class="text-xs text-gray-500">السبب</p>
                <p class="mt-1 text-sm font-semibold text-gray-900">{{ $creditNote->reason ?: '-' }}</p>
            </div>
        </div>
    </section>
</div>
@endsection

