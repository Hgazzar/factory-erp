<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>إيصال {{ $sale->invoice_number ?? $sale->receipt_number }}</title>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        @page { margin: 4mm; size: 80mm auto; }
        * { box-sizing: border-box; }
        body { font-family: 'Cairo', sans-serif; margin: 0; padding: 8px; width: 80mm; max-width: 80mm; color: #111; font-size: 12px; line-height: 1.45; }
        .center { text-align: center; }
        .bold { font-weight: 700; }
        .muted { color: #555; font-size: 11px; }
        .divider { border-top: 1px dashed #999; margin: 8px 0; }
        table { width: 100%; border-collapse: collapse; }
        td { padding: 3px 0; vertical-align: top; }
        .qty { width: 18%; text-align: center; }
        .price { width: 28%; text-align: left; white-space: nowrap; }
        .name { width: 54%; }
        .totals td { padding-top: 4px; }
        .totals .label { text-align: right; }
        .totals .value { text-align: left; font-weight: 700; }
        .qr { display: block; margin: 8px auto 4px; width: 120px; height: 120px; }
        .no-print { margin-top: 12px; text-align: center; }
        @media print {
            .no-print { display: none !important; }
            body { padding: 0; }
        }
    </style>
</head>
<body onload="window.print()">
    <div class="center">
        <div class="bold" style="font-size:14px;">{{ $company?->company_name_ar ?? $company?->company_name_en ?? config('app.name') }}</div>
        @if($company?->tax_number)
            <div class="muted">الرقم الضريبي: {{ $company->tax_number }}</div>
        @endif
        <div class="muted">إيصال نقاط البيع</div>
    </div>

    <div class="divider"></div>

    <div>
        <div><span class="muted">رقم الفاتورة:</span> <span class="bold">{{ $sale->invoice_number ?? $sale->receipt_number }}</span></div>
        <div><span class="muted">رقم الإيصال:</span> {{ $sale->receipt_number }}</div>
        <div><span class="muted">التاريخ:</span> {{ $sale->created_at?->format('Y-m-d H:i') }}</div>
        <div><span class="muted">الجهاز:</span> {{ $sale->posDevice?->name ?? '—' }}</div>
        <div><span class="muted">طريقة الدفع:</span> {{ $sale->payment_method }}</div>
    </div>

    <div class="divider"></div>

    <table>
        <thead>
            <tr class="bold">
                <td class="name">الصنف</td>
                <td class="qty">كم</td>
                <td class="price">الإجمالي</td>
            </tr>
        </thead>
        <tbody>
            @foreach($sale->items as $item)
                <tr>
                    <td class="name">
                        {{ $item->product?->name ?? '—' }}
                        <div class="muted">{{ number_format((float) $item->unit_price, 2) }} × {{ rtrim(rtrim(number_format((float) $item->quantity, 4, '.', ''), '0'), '.') ?: '0' }}</div>
                    </td>
                    <td class="qty">{{ rtrim(rtrim(number_format((float) $item->quantity, 4, '.', ''), '0'), '.') ?: '0' }}</td>
                    <td class="price">{{ $currencyCode }} {{ number_format((float) $item->line_total, 2) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="divider"></div>

    <table class="totals">
        <tr>
            <td class="label">المجموع الفرعي</td>
            <td class="value">{{ $currencyCode }} {{ number_format((float) ($sale->subtotal_amount ?? $sale->total_price), 2) }}</td>
        </tr>
        <tr>
            <td class="label">ضريبة القيمة المضافة</td>
            <td class="value">{{ $currencyCode }} {{ number_format((float) ($sale->vat_amount ?? 0), 2) }}</td>
        </tr>
        <tr>
            <td class="label bold" style="font-size:13px;">الإجمالي</td>
            <td class="value bold" style="font-size:13px;">{{ $currencyCode }} {{ number_format((float) ($sale->total_amount ?? $sale->total_price), 2) }}</td>
        </tr>
    </table>

    <img src="{{ $qrDataUri }}" alt="QR" class="qr">

    <div class="center muted">شكراً لتسوقكم</div>

    <div class="no-print">
        <button type="button" onclick="window.print()" style="padding:8px 16px;font-family:Cairo,sans-serif;cursor:pointer;">طباعة</button>
        <button type="button" onclick="window.close()" style="padding:8px 16px;font-family:Cairo,sans-serif;cursor:pointer;">إغلاق</button>
    </div>
</body>
</html>
