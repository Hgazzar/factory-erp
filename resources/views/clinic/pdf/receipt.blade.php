<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    @php use App\Support\PdfArabic; @endphp
    <meta charset="utf-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>{{ PdfArabic::glyphs('إيصال '.$appointment->appointment_number) }}</title>
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
        .hdr { border-bottom: 2px solid #0d9488; padding-bottom: 12px; margin-bottom: 16px; }
        .hdr table { width: 100%; border-collapse: collapse; }
        .logo { height: 48px; max-width: 120px; }
        .company { font-size: 18px; font-weight: bold; color: #134e4a; margin: 0; }
        .doc-title { font-size: 14px; font-weight: bold; color: #115e59; margin-top: 8px; }
        .meta { width: 100%; margin-bottom: 16px; font-size: 10.5px; }
        .meta td { padding: 4px 0; vertical-align: top; }
        .lbl { color: #64748b; }
        .items { width: 100%; border-collapse: collapse; margin: 12px 0; }
        .items th, .items td { border: 1px solid #e2e8f0; padding: 8px 10px; text-align: right; }
        .items th { background: #f0fdfa; color: #134e4a; font-weight: bold; }
        .totals { width: 100%; margin-top: 12px; }
        .totals td { padding: 5px 0; }
        .totals .grand { font-size: 13px; font-weight: bold; color: #134e4a; border-top: 2px solid #0d9488; padding-top: 8px; }
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
                    <p class="doc-title">{{ PdfArabic::glyphs('إيصال تحصيل') }}</p>
                    <p style="font-family:monospace;font-size:11px;">{{ $appointment->appointment_number }}</p>
                    <p style="font-size:10px;color:#64748b;">{{ $appointment->paid_at?->format('Y-m-d H:i') ?? now()->format('Y-m-d H:i') }}</p>
                </td>
            </tr>
        </table>
    </div>

    <table class="meta">
        <tr>
            <td><span class="lbl">{{ PdfArabic::glyphs('المريض:') }}</span> {{ PdfArabic::glyphsIfArabic($appointment->patient?->name ?? '—') }}</td>
            <td><span class="lbl">{{ PdfArabic::glyphs('الكود:') }}</span> {{ $appointment->patient?->code ?? '—' }}</td>
        </tr>
        <tr>
            <td><span class="lbl">{{ PdfArabic::glyphs('الهاتف:') }}</span> {{ $appointment->patient?->phone ?? '—' }}</td>
            <td><span class="lbl">{{ PdfArabic::glyphs('الطبيب:') }}</span> {{ PdfArabic::glyphsIfArabic($appointment->doctor?->name ?? '—') }}</td>
        </tr>
        <tr>
            <td colspan="2"><span class="lbl">{{ PdfArabic::glyphs('طريقة الدفع:') }}</span>
                {{ PdfArabic::glyphs(match($appointment->payment_method) {
                    'bank', 'card' => 'بنك / شبكة',
                    default => 'نقدي',
                }) }}
            </td>
        </tr>
    </table>

    <table class="items">
        <thead>
            <tr>
                <th>{{ PdfArabic::glyphs('الخدمة') }}</th>
                <th style="width:12%">{{ PdfArabic::glyphs('الكمية') }}</th>
                <th style="width:18%">{{ PdfArabic::glyphs('المبلغ') }}</th>
            </tr>
        </thead>
        <tbody>
            @forelse($appointment->serviceLines as $line)
                <tr>
                    <td>{{ PdfArabic::glyphsIfArabic($line->service?->name ?? '—') }}</td>
                    <td>{{ $line->quantity }}</td>
                    <td>{{ number_format((float) $line->line_total, 2) }}</td>
                </tr>
            @empty
                <tr>
                    <td>{{ PdfArabic::glyphs('كشف / خدمة') }}</td>
                    <td>1</td>
                    <td>{{ number_format((float) $appointment->fee_amount, 2) }}</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <table class="totals">
        @if($appointment->subtotal_amount !== null)
        <tr>
            <td style="width:75%"></td>
            <td><span class="lbl">{{ PdfArabic::glyphs('الصافي:') }}</span></td>
            <td style="width:15%; text-align:left;">{{ number_format((float) $appointment->subtotal_amount, 2) }}</td>
        </tr>
        <tr>
            <td></td>
            <td><span class="lbl">{{ PdfArabic::glyphs('ض.ق.م:') }}</span></td>
            <td style="text-align:left;">{{ number_format((float) ($appointment->vat_amount ?? 0), 2) }}</td>
        </tr>
        @endif
        <tr class="grand">
            <td></td>
            <td>{{ PdfArabic::glyphs('الإجمالي:') }}</td>
            <td style="text-align:left;">{{ number_format((float) $appointment->fee_amount, 2) }}</td>
        </tr>
    </table>

    <p class="footer">{{ PdfArabic::glyphs('شكراً لزيارتكم — '.($company?->name ?? config('app.name'))) }}</p>
</body>
</html>
