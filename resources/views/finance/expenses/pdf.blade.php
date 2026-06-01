<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    @php use App\Support\PdfArabic; @endphp
    <meta charset="utf-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>{{ PdfArabic::glyphs('مصروف '.($expense->expense_number ?? (string) $expense->id)) }}</title>
    @include('pdf.partials.cairo-font-face')
    <style>
        * { box-sizing: border-box; }
        html {
            direction: rtl;
        }
        body {
            font-family: Cairo, "DejaVu Sans", sans-serif;
            font-size: 11px;
            color: #333333;
            direction: rtl;
            text-align: right;
            unicode-bidi: embed;
            margin: 0;
            padding: 18px 22px;
        }
        .hdr {
            display: table;
            width: 100%;
            direction: rtl;
            border-bottom: 2px solid #1a2b4c;
            padding-bottom: 12px;
            margin-bottom: 14px;
        }
        .hdr-row { display: table-row; }
        .hdr-cell { display: table-cell; vertical-align: middle; }
        .hdr-doc {
            width: 32%;
            text-align: left;
            vertical-align: top;
        }
        .hdr-brand {
            width: 68%;
            text-align: right;
        }
        .logo-title { display: table; width: 100%; direction: rtl; }
        .logo-title-row { display: table-row; }
        .logo-title-cell { display: table-cell; vertical-align: middle; text-align: right; padding-left: 10px; }
        .logo-title-cell.img { padding-left: 0; width: 1%; vertical-align: middle; }
        .logo-img { height: 44px; width: auto; max-width: 120px; display: block; margin-right: 0; margin-left: auto; }
        .company-name {
            font-size: 18px;
            font-weight: bold;
            color: #1a2b4c;
            margin: 0 0 4px 0;
            line-height: 1.2;
        }
        .tax-line { font-size: 10px; color: #777777; margin: 0; }
        .doc-type {
            font-size: 15px;
            font-weight: bold;
            color: #333333;
            margin: 0;
            line-height: 1.3;
        }
        table.meta {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 14px;
            font-size: 10.5px;
            direction: rtl;
        }
        table.meta td {
            padding: 5px 0 5px 8px;
            vertical-align: top;
            width: 50%;
            text-align: right;
        }
        .meta .lbl { color: #777777; white-space: nowrap; padding-left: 6px; }
        .meta .val { font-weight: bold; color: #333333; }
        table.lines {
            width: 100%;
            border-collapse: collapse;
            font-size: 10px;
            margin-bottom: 4px;
            direction: rtl;
        }
        table.lines th, table.lines td {
            border: 1px solid #e0e0e0;
            padding: 8px 10px;
            text-align: right;
        }
        table.lines th {
            background: #f8f9fa;
            font-weight: bold;
            color: #333333;
        }
        table.lines tbody tr { background: #fff; }
        .num { text-align: left; direction: ltr; unicode-bidi: embed; font-family: Cairo, "DejaVu Sans", sans-serif; }
        .lines .grand { font-weight: bold; color: #333333; }
        .notes { white-space: pre-wrap; margin-top: 6px; color: #777777; line-height: 1.45; font-size: 9.5px; text-align: right; }
        .doc-total-footer {
            margin-top: 16px;
            padding: 14px 12px 12px;
            border-top: 2px solid #1a2b4c;
            background: #fafbfc;
            text-align: right;
            direction: rtl;
            font-size: 13px;
            font-weight: bold;
            color: #1a2b4c;
        }
        .receipt-title {
            margin-top: 20px;
            font-size: 11px;
            font-weight: bold;
            color: #1a2b4c;
            padding-bottom: 6px;
            border-bottom: 1px solid #e0e0e0;
            text-align: right;
        }
        .receipt-img { max-width: 100%; max-height: 280px; margin-top: 10px; border: 1px solid #e0e0e0; }
    </style>
</head>
<body dir="rtl">
@php
    $tax = (float) ($expense->tax_amount ?? 0);
    $grand = (float) $expense->amount + $tax;
    $categoryLabel = $expense->expenseCategory?->name_ar ?? ($expense->expenseAccount?->name_ar ?? '—');
    $supplierLabel = $expense->supplier
        ? ($expense->supplier->localized_display_name ?? $expense->supplier->name ?? '—')
        : null;
@endphp

    <div class="hdr">
        <div class="hdr-row">
            <div class="hdr-cell hdr-doc">
                <p class="doc-type">{{ PdfArabic::glyphs('سند مصروف') }}</p>
            </div>
            <div class="hdr-cell hdr-brand">
                <div class="logo-title">
                    <div class="logo-title-row">
                        <div class="logo-title-cell">
                            <p class="company-name">{{ PdfArabic::glyphs($company?->name ?? config('app.name')) }}</p>
                            <p class="tax-line">
                                {{ PdfArabic::glyphs('الرقم الضريبي:') }}
                                {{ $company && $company->tax_number ? $company->tax_number : '—' }}
                            </p>
                        </div>
                        @if(!empty($logoDataUri))
                            <div class="logo-title-cell img">
                                <img src="{{ $logoDataUri }}" alt="" class="logo-img"/>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    <table class="meta" dir="rtl">
        <tr>
            <td><span class="lbl">{{ PdfArabic::glyphs('التاريخ:') }}</span> <span class="val">{{ $expense->date?->format('Y-m-d') ?? '—' }}</span></td>
            <td><span class="lbl">{{ PdfArabic::glyphs('رقم المصروف:') }}</span> <span class="val">{{ $expense->expense_number ?? ('EXP-'.str_pad((string) $expense->id, 5, '0', STR_PAD_LEFT)) }}</span></td>
        </tr>
        <tr>
            <td><span class="lbl">{{ PdfArabic::glyphs('الحالة:') }}</span> <span class="val">{{ PdfArabic::glyphs('معتمد') }}</span></td>
            <td><span class="lbl">{{ PdfArabic::glyphs('التصنيف:') }}</span> <span class="val">{{ PdfArabic::glyphsIfArabic($categoryLabel) }}</span></td>
        </tr>
        @if($expense->supplier)
            <tr>
                <td>
                    @if($expense->reference)
                        <span class="lbl">{{ PdfArabic::glyphs('المرجع:') }}</span> <span class="val">{{ PdfArabic::glyphsIfArabic($expense->reference) }}</span>
                    @endif
                </td>
                <td><span class="lbl">{{ PdfArabic::glyphs('المورد:') }}</span> <span class="val">{{ PdfArabic::glyphsIfArabic($supplierLabel) }}</span></td>
            </tr>
        @elseif($expense->reference)
            <tr>
                <td colspan="2" style="text-align:right">
                    <span class="lbl">{{ PdfArabic::glyphs('المرجع:') }}</span>
                    <span class="val">{{ PdfArabic::glyphsIfArabic($expense->reference) }}</span>
                </td>
            </tr>
        @endif
    </table>

    {{-- عمود المبلغ يسار الجدول في PDF، عمود البند يميناً --}}
    <table class="lines" dir="rtl">
        <thead>
            <tr>
                <th style="width: 22%;">{{ PdfArabic::glyphs('المبلغ (ر.س)') }}</th>
                <th>{{ PdfArabic::glyphs('البند') }}</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td class="num">{{ erp_money((float) $expense->amount) }}</td>
                <td>
                    <strong>{{ PdfArabic::glyphs('المبلغ قبل الضريبة') }}</strong>
                    @if($expense->notes)
                        <div class="notes">{{ PdfArabic::glyphsIfArabic($expense->notes) }}</div>
                    @endif
                </td>
            </tr>
            <tr>
                <td class="num">{{ erp_money($tax) }}</td>
                <td><strong>{{ PdfArabic::glyphs('الضريبة') }}</strong></td>
            </tr>
            <tr>
                <td class="num grand">{{ erp_money($grand) }}</td>
                <td class="grand">{{ PdfArabic::glyphs('الإجمالي') }}</td>
            </tr>
        </tbody>
    </table>

    <div class="doc-total-footer">
        {{ PdfArabic::glyphs('الإجمالي شامل الضريبة: '.erp_money($grand).' ر.س') }}
    </div>

    @if(!empty($receiptDataUri))
        <div class="receipt-title">{{ PdfArabic::glyphs('مرفق الإيصال') }}</div>
        <img src="{{ $receiptDataUri }}" alt="" class="receipt-img"/>
    @endif
</body>
</html>
