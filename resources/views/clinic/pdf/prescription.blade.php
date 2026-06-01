<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    @php use App\Support\PdfArabic; @endphp
    <meta charset="utf-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>{{ PdfArabic::glyphs('روشتة — '.($prescription->patient?->name ?? '')) }}</title>
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
            padding: 20px 28px;
        }
        .rx-header {
            border: 2px solid #0d9488;
            border-radius: 8px;
            padding: 14px 16px;
            margin-bottom: 18px;
            background: #f0fdfa;
        }
        .rx-header table { width: 100%; border-collapse: collapse; }
        .logo { height: 44px; max-width: 110px; }
        .rx-symbol { font-size: 28px; font-weight: bold; color: #0d9488; }
        .company { font-size: 16px; font-weight: bold; color: #134e4a; margin: 0; }
        .patient-name { font-size: 15px; font-weight: bold; margin: 12px 0 4px; color: #111; }
        .meta { font-size: 10px; color: #64748b; margin-bottom: 16px; }
        .section-title { font-weight: bold; color: #115e59; margin: 14px 0 8px; font-size: 12px; }
        .diagnosis {
            background: #f0fdfa;
            border: 1px solid #99f6e4;
            border-radius: 6px;
            padding: 10px 12px;
            margin-bottom: 14px;
            white-space: pre-line;
        }
        .med {
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            padding: 10px 12px;
            margin-bottom: 8px;
        }
        .med-name { font-weight: bold; color: #134e4a; font-size: 12px; }
        .med-detail { font-size: 10px; color: #475569; margin-top: 4px; }
        .signature {
            margin-top: 40px;
            border-top: 1px dashed #cbd5e1;
            padding-top: 12px;
            font-size: 10px;
            color: #64748b;
        }
    </style>
</head>
<body>
    <div class="rx-header">
        <table>
            <tr>
                <td style="width:15%; vertical-align:middle;">
                    <span class="rx-symbol">℞</span>
                </td>
                <td style="width:55%; vertical-align:middle;">
                    @if($logoDataUri ?? null)
                        <img src="{{ $logoDataUri }}" class="logo" alt=""><br>
                    @endif
                    <p class="company">{{ PdfArabic::glyphs($company?->name ?? config('app.name')) }}</p>
                </td>
                <td style="width:30%; vertical-align:top; text-align:left; font-size:10px; color:#64748b;">
                    {{ $prescription->prescribed_at?->format('Y-m-d') ?? now()->format('Y-m-d') }}
                </td>
            </tr>
        </table>
    </div>

    <p class="patient-name">{{ PdfArabic::glyphsIfArabic($prescription->patient?->name ?? '—') }}</p>
    <p class="meta">
        {{ PdfArabic::glyphs('كود المريض: '.($prescription->patient?->code ?? '—')) }}
        @if($prescription->doctor)
            · {{ PdfArabic::glyphs('الطبيب: '.$prescription->doctor->name) }}
        @endif
    </p>

    @if($prescription->diagnosis)
        <p class="section-title">{{ PdfArabic::glyphs('التشخيص') }}</p>
        <div class="diagnosis">{{ PdfArabic::glyphsIfArabic($prescription->diagnosis) }}</div>
    @endif

    <p class="section-title">{{ PdfArabic::glyphs('الأدوية') }}</p>
    @foreach($prescription->medications ?? [] as $i => $med)
        <div class="med">
            <p class="med-name">{{ ($i + 1).'. '.PdfArabic::glyphsIfArabic($med['name'] ?? '') }}</p>
            <p class="med-detail">
                @if(!empty($med['dosage'])){{ PdfArabic::glyphsIfArabic($med['dosage']) }}@endif
                @if(!empty($med['frequency'])) · {{ PdfArabic::glyphsIfArabic($med['frequency']) }}@endif
                @if(!empty($med['duration'])) · {{ PdfArabic::glyphsIfArabic($med['duration']) }}@endif
            </p>
        </div>
    @endforeach

    <div class="signature">
        {{ PdfArabic::glyphs('توقيع الطبيب: _________________________') }}
    </div>
</body>
</html>
