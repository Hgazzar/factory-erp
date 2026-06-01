<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <title>تقرير تقييم المخزون</title>
    @include('pdf.partials.cairo-font-face')
    <style>
        body { font-family: 'Cairo', sans-serif; font-size: 11px; color: #111; }
        h1 { font-size: 16px; margin: 0 0 4px; }
        .meta { color: #555; margin-bottom: 16px; font-size: 10px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #ddd; padding: 6px 8px; text-align: right; }
        th { background: #f3f4f6; font-weight: 700; }
        td.num { text-align: left; direction: ltr; }
        tfoot td { font-weight: 700; background: #eef2ff; }
    </style>
</head>
<body>
    <h1>تقرير تقييم المخزون</h1>
    <p class="meta">{{ $companyName }} — {{ $generatedAt }}</p>

    <table>
        <thead>
            <tr>
                <th>الرمز</th>
                <th>اسم الصنف</th>
                <th>الكمية</th>
                <th>تكلفة الوحدة</th>
                <th>إجمالي القيمة</th>
            </tr>
        </thead>
        <tbody>
            @foreach($rows as $row)
            <tr>
                <td>{{ $row->code }}</td>
                <td>{{ $row->name }}</td>
                <td class="num">{{ number_format($row->quantity, 4) }}</td>
                <td class="num">{{ number_format($row->unit_cost, 2) }}</td>
                <td class="num">{{ number_format($row->total_value, 2) }}</td>
            </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <td colspan="4">الإجمالي</td>
                <td class="num">{{ number_format($grandTotal, 2) }}</td>
            </tr>
        </tfoot>
    </table>
</body>
</html>
