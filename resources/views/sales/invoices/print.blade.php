@extends('layouts.print')

@section('title', 'طباعة فاتورة ' . $invoice->id . ' - '.config('app.name'))

@section('company_name', $company?->name ?? config('app.name'))
@section('company_tax', ($company && $company->tax_number) ? 'الرقم الضريبي: ' . $company->tax_number : 'الرقم الضريبي: —')

@section('company_logo')
    @if($company && $company->logo_url)
        @if(str_starts_with($company->logo_url, 'company/'))
            <img src="{{ asset('storage/' . $company->logo_url) }}" alt="Logo" class="print-logo-img">
        @else
            <img src="{{ $company->logo_url }}" alt="Logo" class="print-logo-img">
        @endif
    @elseif(file_exists(public_path('images/logo.png')))
        <img src="{{ asset('images/logo.png') }}" alt="Logo" class="print-logo-img">
    @endif
@endsection

@section('stamp')
    @if($invoice->payment_method === 'cash')
        <div class="print-stamp">
            <div class="print-stamp-inner">مـدفـوع</div>
        </div>
    @endif
@endsection

@section('doc_type', 'فاتورة مبيعات')

@section('document_meta')
    <div class="print-meta-item">
        <span class="print-meta-label">رقم الفاتورة:</span>
        <span class="print-meta-value">SINV-{{ $invoice->id }}</span>
    </div>
    <div class="print-meta-item">
        <span class="print-meta-label">العميل:</span>
        <span class="print-meta-value">{{ $invoice->customer?->name ?? '—' }}</span>
    </div>
    <div class="print-meta-item">
        <span class="print-meta-label">تاريخ الإصدار:</span>
        <span class="print-meta-value">{{ $invoice->date?->format('Y-m-d') ?? '—' }}</span>
    </div>
    <div class="print-meta-item">
        <span class="print-meta-label">تاريخ الاستحقاق:</span>
        <span class="print-meta-value">{{ $invoice->due_date?->format('Y-m-d') ?? '—' }}</span>
    </div>
    @if($invoice->reference)
        <div class="print-meta-item">
            <span class="print-meta-label">المرجع:</span>
            <span class="print-meta-value">{{ $invoice->reference }}</span>
        </div>
    @endif
@endsection

@section('document_table')
    <table class="print-table">
        <thead>
            <tr>
                <th style="width:2rem">#</th>
                <th>المنتج</th>
                <th class="num">الكمية</th>
                <th class="num">سعر الوحدة</th>
                <th class="num">إجمالي البند</th>
            </tr>
        </thead>
        <tbody>
            @foreach($invoice->items as $idx => $line)
                <tr>
                    <td>{{ $idx + 1 }}</td>
                    <td>{{ $line->item?->name_ar ?? $line->item?->code ?? '—' }}</td>
                    <td class="num">{{ erp_qty((float) $line->quantity) }}</td>
                    <td class="num">{{ erp_money((float) $line->unit_price) }}</td>
                    <td class="num">{{ erp_money((float) $line->line_total) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
@endsection

@section('document_footer')
    <div class="print-totals">
        @if((float) $invoice->vat_amount > 0)
            <div class="print-totals-row">
                <span>نسبة ضريبة القيمة المضافة:</span>
                <span>{{ erp_qty((float) ($invoice->vat_rate ?? $defaultVatPercent)) }}%</span>
            </div>
            <div class="print-totals-row">
                <span>ضريبة القيمة المضافة:</span>
                <span>SAR {{ erp_money((float) $invoice->vat_amount) }}</span>
            </div>
        @endif
        <div class="print-totals-row grand">
            <span>الإجمالي:</span>
            <span>SAR {{ erp_money((float) $invoice->total) }}</span>
        </div>
    </div>
    @if(!empty(trim($invoice->terms ?? '')))
        <div class="print-terms"><strong>الشروط والأحكام:</strong><br>{{ $invoice->terms }}</div>
    @endif
    <div class="print-qr-wrap">
        <div></div>
        <div class="print-qr-box">
            <img src="https://api.qrserver.com/v1/create-qr-code/?size=90x90&data={{ urlencode(route('sales.invoices.print', $invoice)) }}" alt="QR" width="90" height="90">
            <p class="print-qr-caption">مسح الرمز للتحقق من صحة المستند</p>
        </div>
    </div>
@endsection
