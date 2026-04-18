@extends('layouts.print')

@section('title', 'طباعة مصروف ' . ($expense->expense_number ?? $expense->id) . ' - MIRADA ERP')

@section('company_name', $company?->name ?? 'MIRADA ERP')
@section('company_tax', ($company && $company->tax_number) ? 'الرقم الضريبي: ' . $company->tax_number : 'الرقم الضريبي: —')

@section('company_logo')
    @if($company && $company->logo_url)
        @if(str_starts_with($company->logo_url, 'company/'))
            <img src="{{ asset('storage/' . $company->logo_url) }}" alt="" class="print-logo-img">
        @else
            <img src="{{ $company->logo_url }}" alt="" class="print-logo-img">
        @endif
    @elseif(file_exists(public_path('images/logo.png')))
        <img src="{{ asset('images/logo.png') }}" alt="" class="print-logo-img">
    @endif
@endsection

@push('print_styles')
<style>
    .print-expense-desc { white-space: pre-wrap; line-height: 1.6; color: #334155; font-size: 0.9rem; }
    .print-receipt-block { margin-top: 1.25rem; page-break-inside: avoid; }
    .print-receipt-title { font-size: 0.95rem; font-weight: 600; color: #1e3a5f; margin-bottom: 0.5rem; }
    .print-receipt-img {
        max-width: 100%;
        height: auto;
        max-height: 70vh;
        object-fit: contain;
        border: 1px solid #e2e8f0;
        border-radius: 6px;
        display: block;
        margin: 0 auto;
    }
    .print-signatures {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 2rem;
        margin-top: 2rem;
        padding-top: 1rem;
        border-top: 1px solid #e2e8f0;
        font-size: 0.9rem;
        color: #475569;
    }
    .print-sig-line { margin-top: 2.5rem; border-top: 1px solid #94a3b8; padding-top: 0.35rem; text-align: center; }
    @media print {
        .print-receipt-img { max-height: none; }
    }
</style>
@endpush

@php
    $tax = (float) ($expense->tax_amount ?? 0);
    $grand = (float) $expense->amount + $tax;
    $posted = ($expense->status ?? '') === 'posted' || $expense->journal_entry_id;
    $categoryLabel = $expense->expenseCategory?->name_ar ?? ($expense->expenseAccount?->name_ar ?? '—');
    $firstImageAtt = ($expense->attachments ?? collect())->sortBy('id')->first(function ($a) {
        $m = strtolower((string) ($a->file_type ?? ''));

        return $m !== '' && str_starts_with($m, 'image/');
    });
    $receiptUrl = ($firstImageAtt && $firstImageAtt->file_path)
        ? asset('storage/'.ltrim($firstImageAtt->file_path, '/'))
        : null;
@endphp

@section('doc_type', 'سند مصروف')

@section('document_meta')
    <div class="print-meta-item">
        <span class="print-meta-label">رقم المصروف:</span>
        <span class="print-meta-value">{{ $expense->expense_number ?? ('EXP-'.str_pad((string) $expense->id, 5, '0', STR_PAD_LEFT)) }}</span>
    </div>
    <div class="print-meta-item">
        <span class="print-meta-label">التاريخ:</span>
        <span class="print-meta-value">{{ $expense->date?->format('Y-m-d') ?? '—' }}</span>
    </div>
    <div class="print-meta-item">
        <span class="print-meta-label">التصنيف:</span>
        <span class="print-meta-value">{{ $categoryLabel }}</span>
    </div>
    <div class="print-meta-item">
        <span class="print-meta-label">الحالة:</span>
        <span class="print-meta-value">{{ $posted ? 'معتمد' : 'مسودة' }}</span>
    </div>
    @if($expense->supplier)
        <div class="print-meta-item">
            <span class="print-meta-label">المورد:</span>
            <span class="print-meta-value">{{ $expense->supplier->localized_display_name ?? $expense->supplier->name ?? '—' }}</span>
        </div>
    @endif
    @if($expense->reference)
        <div class="print-meta-item">
            <span class="print-meta-label">المرجع:</span>
            <span class="print-meta-value">{{ $expense->reference }}</span>
        </div>
    @endif
@endsection

@section('document_table')
    <table class="print-table">
        <thead>
            <tr>
                <th>البند</th>
                <th class="num" style="width: 8rem">المبلغ (ر.س)</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>
                    <strong>المبلغ قبل الضريبة</strong>
                    @if($expense->notes)
                        <div class="print-expense-desc mt-2">{{ $expense->notes }}</div>
                    @endif
                </td>
                <td class="num">{{ number_format((float) $expense->amount, 2) }}</td>
            </tr>
            <tr>
                <td><strong>الضريبة</strong></td>
                <td class="num">{{ number_format($tax, 2) }}</td>
            </tr>
            <tr>
                <td><strong>الإجمالي</strong></td>
                <td class="num"><strong>{{ number_format($grand, 2) }}</strong></td>
            </tr>
        </tbody>
    </table>

    @if($receiptUrl)
        <div class="print-receipt-block">
            <div class="print-receipt-title">مرفق الإيصال</div>
            <img src="{{ $receiptUrl }}" alt="إيصال المصروف" class="print-receipt-img">
        </div>
    @endif
@endsection

@section('document_footer')
    <div class="print-totals">
        <div class="print-totals-row grand">
            <span>الإجمالي شامل الضريبة:</span>
            <span>{{ number_format($grand, 2) }} ر.س</span>
        </div>
    </div>
    <div class="print-signatures">
        <div>
            <p class="mb-0">أعدّه / سجّله</p>
            <div class="print-sig-line">التوقيع والاسم</div>
        </div>
        <div>
            <p class="mb-0">المعتمد</p>
            <div class="print-sig-line">التوقيع والاسم</div>
        </div>
    </div>
@endsection
