<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    @php use App\Support\PdfArabic; @endphp
    <meta charset="utf-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>{{ PdfArabic::glyphs('عرض سعر '.($quotation->quotation_number ?? ('QT-'.$quotation->id))) }}</title>
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
        /* dompdf: الخلية الأولى في الصف = يسار الصفحة؛ نضع عنوان المستند يساراً والهوية يميناً */
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
        .draft-badge {
            display: inline-block;
            margin-top: 6px;
            padding: 3px 10px;
            font-size: 10px;
            font-weight: bold;
            color: #92400e;
            background: #fef3c7;
            border: 1px solid #fcd34d;
            border-radius: 4px;
        }
        table.meta {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 14px;
            font-size: 10.5px;
            direction: rtl;
        }
        table.meta td {
            padding: 4px 0 4px 8px;
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
            margin-bottom: 12px;
            direction: rtl;
        }
        table.lines th, table.lines td {
            border: 1px solid #e0e0e0;
            padding: 7px 5px;
            text-align: center;
        }
        table.lines th {
            background: #f8f9fa;
            font-weight: bold;
            color: #333333;
        }
        .num { direction: ltr; unicode-bidi: embed; text-align: center; }
        .totals {
            margin-top: 4px;
            padding-top: 10px;
            border-top: 1px solid #e0e0e0;
            width: 100%;
            text-align: right;
            direction: rtl;
        }
        .totals-inner {
            font-size: 12px;
            font-weight: bold;
            color: #333333;
        }
        .terms {
            margin-top: 12px;
            font-size: 9.5px;
            color: #777777;
            line-height: 1.45;
            white-space: pre-wrap;
            text-align: right;
            direction: rtl;
        }
        .qr-wrap { margin-top: 16px; width: 100%; direction: rtl; }
        .qr-box { text-align: center; float: right; width: 110px; }
        .qr-box img { width: 90px; height: 90px; display: block; margin: 0 auto 4px; }
        .qr-cap { font-size: 8.5px; color: #777777; line-height: 1.3; margin: 0; }
        .clear { clear: both; }
    </style>
</head>
<body dir="rtl">
@php
    $qnum = $quotation->quotation_number ?? ('QT-'.str_pad((string) $quotation->id, 3, '0', STR_PAD_LEFT));
    $isDraft = $quotation->status === \App\Models\Quotation::STATUS_DRAFT;
@endphp

    <div class="hdr">
        <div class="hdr-row">
            <div class="hdr-cell hdr-doc">
                <p class="doc-type">{{ PdfArabic::glyphs('عرض سعر') }}</p>
            </div>
            <div class="hdr-cell hdr-brand">
                <div class="logo-title">
                    <div class="logo-title-row">
                        <div class="logo-title-cell">
                            <p class="company-name">{{ PdfArabic::glyphs($company?->name ?? 'MIRADA ERP') }}</p>
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
                @if($isDraft)
                    <span class="draft-badge">{{ PdfArabic::glyphs('مسودة') }}</span>
                @endif
            </div>
        </div>
    </div>

    {{-- ترتيب الخلايا: اليسار في PDF أولاً في DOM (العميل / صالح حتى) ثم اليمين (رقم العرض / التاريخ) --}}
    <table class="meta" dir="rtl">
        <tr>
            <td><span class="lbl">{{ PdfArabic::glyphs('العميل:') }}</span> <span class="val">{{ PdfArabic::glyphsIfArabic($quotation->customer?->display_name ?? '—') }}</span></td>
            <td><span class="lbl">{{ PdfArabic::glyphs('رقم العرض:') }}</span> <span class="val">{{ $qnum }}</span></td>
        </tr>
        <tr>
            <td><span class="lbl">{{ PdfArabic::glyphs('صالح حتى:') }}</span> <span class="val">{{ $quotation->valid_until?->format('Y-m-d') ?? '—' }}</span></td>
            <td><span class="lbl">{{ PdfArabic::glyphs('تاريخ العرض:') }}</span> <span class="val">{{ $quotation->date?->format('Y-m-d') ?? '—' }}</span></td>
        </tr>
    </table>

    {{-- أعمدة من اليسار إلى اليمين في PDF = إجمالي البند … # (عكس ترتيب المصدر المنطقي لـ dompdf) --}}
    <table class="lines" dir="rtl">
        <thead>
            <tr>
                <th style="width:12%">{{ PdfArabic::glyphs('إجمالي البند') }}</th>
                <th style="width:9%">{{ PdfArabic::glyphs('الضريبة %') }}</th>
                <th style="width:9%">{{ PdfArabic::glyphs('الخصم %') }}</th>
                <th style="width:11%">{{ PdfArabic::glyphs('سعر الوحدة') }}</th>
                <th style="width:9%">{{ PdfArabic::glyphs('الكمية') }}</th>
                <th>{{ PdfArabic::glyphs('المنتج') }}</th>
                <th style="width:3%">#</th>
            </tr>
        </thead>
        <tbody>
            @foreach($quotation->items as $idx => $line)
                <tr>
                    <td class="num">{{ erp_money((float) $line->line_total) }}</td>
                    <td class="num">{{ erp_qty((float) $line->tax_percent) }}</td>
                    <td class="num">{{ erp_qty((float) $line->discount_percent) }}</td>
                    <td class="num">{{ erp_money((float) $line->unit_price) }}</td>
                    <td class="num">{{ erp_qty((float) $line->quantity) }}</td>
                    <td>{{ PdfArabic::glyphsIfArabic($line->item?->name_ar ?? $line->item?->code ?? '—') }}</td>
                    <td>{{ $idx + 1 }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="totals">
        <div class="totals-inner">
            {{ PdfArabic::glyphs('الإجمالي:') }}
            <span dir="ltr" style="unicode-bidi: embed;">SAR {{ erp_money((float) $quotation->total_amount) }}</span>
        </div>
    </div>

    @if(!empty(trim($quotation->terms ?? '')))
        <div class="terms">
            <strong>{{ PdfArabic::glyphs('الشروط والأحكام:') }}</strong><br>
            {{ PdfArabic::glyphsIfArabic(trim($quotation->terms)) }}
        </div>
    @endif

    <div class="qr-wrap">
        @if(!empty($qrDataUri))
            <div class="qr-box">
                <img src="{{ $qrDataUri }}" alt="QR"/>
                <p class="qr-cap">{{ PdfArabic::glyphs('مسح الرمز للتحقق من صحة المستند') }}</p>
            </div>
        @endif
        <div class="clear"></div>
    </div>
</body>
</html>
