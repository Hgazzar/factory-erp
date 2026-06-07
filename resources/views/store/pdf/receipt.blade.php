<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    @php use App\Support\PdfArabic; @endphp
    <meta charset="utf-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>{{ PdfArabic::glyphs('فاتورة '.$sale->invoice_number) }}</title>
    @include('pdf.partials.cairo-font-face')
    <style>
        * { box-sizing: border-box; }
        body {
            font-family: Cairo, "DejaVu Sans", sans-serif;
            font-size: 11px;
            color: #333;
            direction: rtl;
            text-align: right;
            margin: 0;
            padding: 20px 24px;
        }
        .hdr { border-bottom: 2px solid #dc2626; padding-bottom: 12px; margin-bottom: 16px; }
        .hdr table { width: 100%; border-collapse: collapse; }
        .logo { height: 48px; max-width: 120px; }
        .company { font-size: 18px; font-weight: bold; color: #991b1b; margin: 0; }
        .doc-title { font-size: 14px; font-weight: bold; color: #b91c1c; margin-top: 8px; }
        .meta { width: 100%; margin-bottom: 16px; font-size: 10.5px; }
        .meta td { padding: 4px 0; vertical-align: top; }
        .lbl { color: #64748b; }
        .items { width: 100%; border-collapse: collapse; margin: 12px 0; }
        .items th, .items td { border: 1px solid #e2e8f0; padding: 8px 10px; text-align: right; }
        .items th { background: #fef2f2; color: #991b1b; font-weight: bold; }
        .totals { width: 100%; margin-top: 12px; }
        .totals td { padding: 5px 0; }
        .totals .grand { font-size: 13px; font-weight: bold; color: #991b1b; border-top: 2px solid #dc2626; padding-top: 8px; }
        .footer { margin-top: 24px; font-size: 9px; color: #94a3b8; text-align: center; }
    </style>
</head>
<body>
    <div class="hdr">
        <table>
            <tr>
                <td style="width:70%; vertical-align:middle;">
                    @if($logoDataUri ?? null)
                        <img src="{{ $logoDataUri }}" class="logo" alt="">
                    @endif
                    <p class="company">{{ PdfArabic::glyphs($company?->name ?? config('app.name')) }}</p>
                    @if($company?->tax_number)
                        <p style="font-size:10px;color:#64748b;margin:4px 0 0;">{{ PdfArabic::glyphs('الرقم الضريبي: '.$company->tax_number) }}</p>
                    @endif
                </td>
                <td style="width:30%; vertical-align:top; text-align:left;">
                    <p class="doc-title">{{ PdfArabic::glyphs('فاتورة متجر إلكتروني') }}</p>
                    <p style="font-family:monospace;font-size:11px;">{{ $sale->invoice_number }}</p>
                    <p style="font-size:10px;color:#64748b;">{{ $sale->created_at?->format('Y-m-d H:i') ?? now()->format('Y-m-d H:i') }}</p>
                </td>
            </tr>
        </table>
    </div>

    <table class="meta">
        <tr>
            <td><span class="lbl">{{ PdfArabic::glyphs('العميل:') }}</span> {{ PdfArabic::glyphsIfArabic($sale->customer_name ?? '—') }}</td>
            <td><span class="lbl">{{ PdfArabic::glyphs('الهاتف:') }}</span> {{ $sale->customer_phone ?? '—' }}</td>
        </tr>
        @if($sale->customer_address)
        <tr>
            <td colspan="2"><span class="lbl">{{ PdfArabic::glyphs('العنوان:') }}</span> {{ PdfArabic::glyphsIfArabic($sale->customer_address) }}</td>
        </tr>
        @endif
        <tr>
            <td><span class="lbl">{{ PdfArabic::glyphs('طريقة الدفع:') }}</span>
                {{ PdfArabic::glyphs(match($sale->payment_method) {
                    'cod' => 'دفع عند الاستلام',
                    'card' => 'بطاقة / دفع إلكتروني',
                    'bank' => 'تحويل بنكي',
                    default => 'نقدي',
                }) }}
            </td>
            <td><span class="lbl">{{ PdfArabic::glyphs('الحالة:') }}</span>
                {{ PdfArabic::glyphs(\App\Models\PosSale::onlineOrderStatusLabels()[$sale->status] ?? $sale->status) }}
            </td>
        </tr>
    </table>

    <table class="items">
        <thead>
            <tr>
                <th>{{ PdfArabic::glyphs('المنتج') }}</th>
                <th style="width:12%">{{ PdfArabic::glyphs('الكمية') }}</th>
                <th style="width:16%">{{ PdfArabic::glyphs('السعر') }}</th>
                <th style="width:16%">{{ PdfArabic::glyphs('الإجمالي') }}</th>
            </tr>
        </thead>
        <tbody>
            @foreach($sale->items as $item)
                <tr>
                    <td>{{ PdfArabic::glyphsIfArabic($item->product?->name ?? '—') }}</td>
                    <td>{{ number_format((float) $item->quantity, 2) }}</td>
                    <td>{{ number_format((float) $item->unit_price, 2) }}</td>
                    <td>{{ number_format((float) $item->line_total, 2) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <table class="totals">
        <tr>
            <td style="width:75%"></td>
            <td><span class="lbl">{{ PdfArabic::glyphs('المجموع الفرعي:') }}</span></td>
            <td style="width:15%; text-align:left;">{{ number_format((float) $sale->subtotal_amount, 2) }} {{ $currency }}</td>
        </tr>
        @if((float) ($sale->discount_amount ?? 0) > 0)
        <tr>
            <td></td>
            <td><span class="lbl">{{ PdfArabic::glyphs('الخصم:') }}</span></td>
            <td style="text-align:left;">−{{ number_format((float) $sale->discount_amount, 2) }}</td>
        </tr>
        @endif
        <tr>
            <td></td>
            <td><span class="lbl">{{ PdfArabic::glyphs('ض.ق.م:') }}</span></td>
            <td style="text-align:left;">{{ number_format((float) ($sale->vat_amount ?? 0), 2) }}</td>
        </tr>
        <tr class="grand">
            <td></td>
            <td>{{ PdfArabic::glyphs('الإجمالي:') }}</td>
            <td style="text-align:left;">{{ number_format((float) $sale->total_amount, 2) }} {{ $currency }}</td>
        </tr>
    </table>

    <p class="footer">{{ PdfArabic::glyphs('شكراً لتسوقكم — '.($company?->name ?? config('app.name'))) }}</p>
</body>
</html>
