@extends('layouts.store-portal')

@section('title', 'تم استلام طلبك — '.$storeName)

@section('content')
<div class="bg-white rounded-3 border p-4 text-center mt-3">
    <div class="display-6 text-success mb-2">✓</div>
    <h1 class="h4 fw-bold">شكراً لك!</h1>
    <p class="text-muted">تم استلام طلبك بنجاح. سنتواصل معك قريباً لتأكيد التوصيل.</p>
    <div class="bg-light rounded-3 p-3 text-start d-inline-block w-100" style="max-width:420px">
        <div><span class="text-muted">رقم الطلب:</span> <strong>{{ $sale->invoice_number ?? $sale->receipt_number }}</strong></div>
        <div><span class="text-muted">الإجمالي:</span> <strong>{{ $currencyCode }} {{ number_format((float) ($sale->total_amount ?? $sale->total_price), 2) }}</strong></div>
        <div><span class="text-muted">الدفع:</span> دفع عند الاستلام</div>
    </div>
    <a href="{{ route('store.portal.home', ['tenant_slug' => $tenantSlug]) }}" class="store-btn d-inline-block mt-4 text-decoration-none" style="width:auto;padding:.65rem 1.5rem">متابعة التسوق</a>
</div>
@endsection
