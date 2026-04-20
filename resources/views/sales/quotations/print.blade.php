@extends('layouts.print')

@push('print_styles')
<style>
    /* عرض سعر: محاذاة الجدول كما في المعاينة الاحترافية */
    .print-table th,
    .print-table td { text-align: center; }
    .print-table .num { text-align: center; }
</style>
@endpush

@section('title', 'طباعة عرض السعر ' . $quotation->id . ' - MIRADA ERP')

@section('company_name', $company?->name ?? 'MIRADA ERP')
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

@section('watermark')
    @if($quotation->status === \App\Models\Quotation::STATUS_DRAFT)
        <div class="print-watermark">
            <span class="print-watermark-text">مـسـودة</span>
        </div>
    @endif
@endsection

@section('doc_type', 'عرض سعر')

@section('document_meta')
    <div class="print-meta-item">
        <span class="print-meta-label">رقم العرض:</span>
        <span class="print-meta-value">{{ $quotation->quotation_number ?? ('QT-'.str_pad((string) $quotation->id, 3, '0', STR_PAD_LEFT)) }}</span>
    </div>
    <div class="print-meta-item">
        <span class="print-meta-label">العميل:</span>
        <span class="print-meta-value">{{ $quotation->customer?->display_name ?? '—' }}</span>
    </div>
    <div class="print-meta-item">
        <span class="print-meta-label">تاريخ العرض:</span>
        <span class="print-meta-value">{{ $quotation->date?->format('Y-m-d') ?? '—' }}</span>
    </div>
    <div class="print-meta-item">
        <span class="print-meta-label">صالح حتى:</span>
        <span class="print-meta-value">{{ $quotation->valid_until?->format('Y-m-d') ?? '—' }}</span>
    </div>
@endsection

@section('document_table')
    <table class="print-table">
        <thead>
            <tr>
                <th style="width:2rem">#</th>
                <th>المنتج</th>
                <th class="num">الكمية</th>
                <th class="num">سعر الوحدة</th>
                <th class="num">الخصم %</th>
                <th class="num">الضريبة %</th>
                <th class="num">إجمالي البند</th>
            </tr>
        </thead>
        <tbody>
            @foreach($quotation->items as $idx => $line)
                <tr>
                    <td>{{ $idx + 1 }}</td>
                    <td>{{ $line->item?->name_ar ?? $line->item?->code ?? '—' }}</td>
                    <td class="num">{{ erp_qty((float) $line->quantity) }}</td>
                    <td class="num">{{ erp_money((float) $line->unit_price) }}</td>
                    <td class="num">{{ erp_qty((float) $line->discount_percent) }}</td>
                    <td class="num">{{ erp_qty((float) $line->tax_percent) }}</td>
                    <td class="num">{{ erp_money((float) $line->line_total) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
@endsection

@section('document_footer')
    <div class="print-totals">
        <div class="print-totals-row grand">
            <span>الإجمالي:</span>
            <span>SAR {{ erp_money((float) $quotation->total_amount) }}</span>
        </div>
    </div>
    @if(!empty(trim($quotation->terms ?? '')))
        <div class="print-terms"><strong>الشروط والأحكام:</strong><br>{{ $quotation->terms }}</div>
    @endif
    <div class="print-qr-wrap">
        <div></div>
        <div class="print-qr-box">
            <img src="https://api.qrserver.com/v1/create-qr-code/?size=90x90&data={{ urlencode(route('sales.quotations.print', $quotation)) }}" alt="QR" width="90" height="90">
            <p class="print-qr-caption">مسح الرمز للتحقق من صحة المستند</p>
        </div>
    </div>
@endsection
