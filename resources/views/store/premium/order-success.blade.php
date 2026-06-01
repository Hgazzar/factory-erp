@extends('layouts.store-premium')

@section('title', 'شكراً لك — '.$storeName)

@section('content')
<div class="ak-container ak-section">
    <div style="max-width:28rem;margin:0 auto;text-align:center">
        <div class="ak-success-icon">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" width="32" height="32"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-3.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>
        </div>
        <p class="ak-eyebrow">تم بنجاح</p>
        <h1 class="ak-section-title" style="margin:var(--ak-3) 0">شكراً لك</h1>
        <p class="ak-body-lg" style="margin-bottom:var(--ak-8)">
            استلمنا طلبك. رقم الفاتورة:
            <strong>{{ $sale->invoice_number }}</strong>
        </p>

        <div class="ak-panel" style="text-align:right;margin-bottom:var(--ak-8)">
            <div style="display:flex;justify-content:space-between;padding:var(--ak-2) 0">
                <span class="ak-caption">الإجمالي</span>
                <strong>{{ number_format((float) $sale->total_amount, 2) }} {{ $currencyCode }}</strong>
            </div>
            @if((float) ($sale->discount_amount ?? 0) > 0)
            <div style="display:flex;justify-content:space-between;padding:var(--ak-2) 0;color:#059669">
                <span class="ak-caption">الخصم</span>
                <span>{{ number_format((float) $sale->discount_amount, 2) }}</span>
            </div>
            @endif
            <div style="display:flex;justify-content:space-between;padding:var(--ak-2) 0">
                <span class="ak-caption">العميل</span>
                <span>{{ $sale->customer_name }}</span>
            </div>
        </div>

        <a href="{{ $routes['home'] }}" class="ak-btn ak-btn--primary">العودة للمتجر</a>
    </div>
</div>
@endsection
