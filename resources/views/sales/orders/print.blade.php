<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>طباعة أمر بيع SO-{{ $salesOrder->id }}</title>
    <style>
        body { font-family: Tahoma, Arial, sans-serif; margin: 24px; color: #111; }
        .head { display: flex; justify-content: space-between; align-items: start; margin-bottom: 18px; }
        .title { font-size: 22px; font-weight: 700; margin: 0; }
        .sub { color: #555; font-size: 13px; margin-top: 4px; }
        .meta { border: 1px solid #ddd; border-radius: 8px; padding: 12px; margin-bottom: 16px; }
        .meta-row { display: flex; justify-content: space-between; gap: 12px; font-size: 13px; margin-bottom: 6px; }
        .meta-row:last-child { margin-bottom: 0; }
        table { width: 100%; border-collapse: collapse; font-size: 13px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: right; }
        th { background: #f6f7f9; }
        .total { margin-top: 12px; display: flex; justify-content: flex-start; font-weight: 700; }
        .status { display: inline-block; padding: 2px 8px; border-radius: 999px; background: #eef2ff; }
        .actions { margin-bottom: 14px; }
        .btn { display: inline-block; padding: 8px 12px; border: 1px solid #ccc; border-radius: 6px; text-decoration: none; color: #111; margin-left: 6px; }
        @media print {
            .actions { display: none; }
            body { margin: 10mm; }
        }
    </style>
</head>
<body>
    <div class="actions">
        <a href="#" onclick="window.print(); return false;" class="btn">طباعة / حفظ PDF</a>
        <a href="{{ route('sales.orders.show', $salesOrder) }}" class="btn">رجوع للأمر</a>
    </div>

    <div class="head">
        <div>
            <h1 class="title">أمر بيع SO-{{ $salesOrder->id }}</h1>
            <div class="sub">{{ config('app.name') }}</div>
        </div>
        <div class="sub">{{ now()->format('Y-m-d H:i') }}</div>
    </div>

    <div class="meta">
        <div class="meta-row"><span>العميل</span><strong>{{ $salesOrder->customer?->name ?? '—' }}</strong></div>
        <div class="meta-row"><span>تاريخ الأمر</span><strong>{{ $salesOrder->order_date?->format('Y-m-d') ?? '—' }}</strong></div>
        <div class="meta-row"><span>التسليم المتوقع</span><strong>{{ $salesOrder->expected_delivery?->format('Y-m-d') ?? '—' }}</strong></div>
        <div class="meta-row"><span>الحالة</span><strong class="status">{{ $salesOrder->status }}</strong></div>
    </div>

    <table>
        <thead>
            <tr>
                <th>الصنف</th>
                <th>النوع</th>
                <th>الكمية</th>
                <th>سعر الوحدة</th>
                <th>الإجمالي</th>
            </tr>
        </thead>
        <tbody>
            @php $types = ['raw_material' => 'مادة خام', 'finished_good' => 'منتج تام', 'service' => 'خدمة']; @endphp
            @foreach($salesOrder->items as $line)
                <tr>
                    <td>{{ $line->item?->code }} — {{ $line->item?->name_ar }}</td>
                    <td>{{ $types[$line->item?->type] ?? ($line->item?->type ?? '—') }}</td>
                    <td>{{ erp_qty((float) $line->quantity) }}</td>
                    <td>{{ erp_money((float) $line->unit_price) }}</td>
                    <td>{{ erp_money((float) $line->line_total) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="total">
        الإجمالي: SAR {{ erp_money((float) $salesOrder->total) }}
    </div>

    @if($salesOrder->notes)
        <div style="margin-top: 14px; font-size: 13px;">
            <strong>ملاحظات:</strong> {{ $salesOrder->notes }}
        </div>
    @endif
</body>
</html>
