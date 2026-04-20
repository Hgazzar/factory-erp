<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <title>تقرير الموازنة - {{ $budget->name }}</title>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Cairo', sans-serif; color: #0f172a; margin: 24px; }
        .header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 16px; }
        .title { font-size: 26px; font-weight: 700; margin: 0; }
        .meta { font-size: 13px; color: #475569; margin-top: 6px; }
        table { width: 100%; border-collapse: collapse; margin-top: 16px; }
        th, td { border: 1px solid #e2e8f0; padding: 10px; font-size: 13px; text-align: right; font-variant-numeric: tabular-nums; }
        th { background: #f8fafc; }
        .danger { color: #dc2626; font-weight: 700; }
        .safe { color: #059669; font-weight: 700; }
        .totals { margin-top: 14px; font-size: 14px; }
        @media print {
            @page { size: landscape; }
            .no-print { display: none; }
            body { margin: 0; }
        }
    </style>
</head>
<body>
    <div class="header">
        <div>
            <h1 class="title">تقرير الموازنة</h1>
            <div class="meta">الاسم: {{ $budget->name }}</div>
            <div class="meta">السنة المالية: {{ $budget->fiscal_year }}</div>
            <div class="meta">الفترة: {{ $budget->start_date->format('Y-m-d') }} - {{ $budget->end_date->format('Y-m-d') }}</div>
        </div>
        <button class="no-print" onclick="window.print()">تصدير PDF</button>
    </div>

    <table>
        <thead>
            <tr>
                <th>الحساب</th>
                <th>مركز التكلفة</th>
                <th>المخطط</th>
                <th>الفعلي</th>
                <th>الانحراف</th>
                <th>نسبة الانحراف</th>
            </tr>
        </thead>
        <tbody>
            @foreach(($analysis['lines'] ?? []) as $line)
                @php $isOver = (float) $line['actual'] > (float) $line['planned']; @endphp
                <tr>
                    <td>{{ $line['account_code'] }} - {{ $line['account_name'] }}</td>
                    <td>{{ $line['cost_center'] ?? '—' }}</td>
                    <td>SAR {{ erp_money((float) $line['planned']) }}</td>
                    <td>SAR {{ erp_money((float) $line['actual']) }}</td>
                    <td class="{{ $isOver ? 'danger' : 'safe' }}">SAR {{ erp_money((float) $line['variance']) }}</td>
                    <td class="{{ $isOver ? 'danger' : 'safe' }}">{{ erp_qty((float) $line['variance_percent']) }}%</td>
                </tr>
                @if(!empty($line['monthly']))
                    <tr>
                        <td colspan="6" style="background:#fcfdff;">
                            <table style="width:100%; border-collapse:collapse; margin:0;">
                                <thead>
                                    <tr>
                                        <th style="font-size:12px;">الشهر</th>
                                        <th style="font-size:12px;">المخطط</th>
                                        <th style="font-size:12px;">الفعلي</th>
                                        <th style="font-size:12px;">الانحراف</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($line['monthly'] as $month)
                                        <tr>
                                            <td style="font-size:12px;">{{ $month['label'] }}</td>
                                            <td style="font-size:12px;">SAR {{ erp_money((float) $month['planned']) }}</td>
                                            <td style="font-size:12px;">SAR {{ erp_money((float) $month['actual']) }}</td>
                                            <td style="font-size:12px;" class="{{ (float) $month['variance'] > 0 ? 'danger' : 'safe' }}">
                                                SAR {{ erp_money((float) $month['variance']) }}
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </td>
                    </tr>
                @endif
            @endforeach
        </tbody>
    </table>

    <div class="totals">
        <div>إجمالي المخطط: SAR {{ erp_money((float) ($analysis['totals']['planned'] ?? 0)) }}</div>
        <div>إجمالي الفعلي: SAR {{ erp_money((float) ($analysis['totals']['actual'] ?? 0)) }}</div>
        <div>إجمالي الانحراف: SAR {{ erp_money((float) ($analysis['totals']['variance'] ?? 0)) }}</div>
    </div>
</body>
</html>

