<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <title>تقرير حضور — {{ config('app.name') }}</title>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: Cairo, sans-serif; padding: 2rem; color: #0f172a; background: #f7f6f3; }
        h1 { font-size: 1.5rem; margin-bottom: 0.25rem; font-weight: 800; }
        h2 { font-size: 1.05rem; margin: 1.35rem 0 0.65rem; font-weight: 800; color: #0f172a; }
        .meta { color: #64748b; margin-bottom: 1.5rem; font-size: 0.9rem; }
        .card {
            background: #fff;
            border: 1px solid rgba(15, 23, 42, 0.06);
            border-radius: 1rem;
            box-shadow: 0 8px 24px rgba(15, 23, 42, 0.04);
            overflow: hidden;
            margin-bottom: 1rem;
        }
        table { width: 100%; border-collapse: separate; border-spacing: 0; font-size: 0.85rem; }
        th, td { padding: 0.75rem 1rem; text-align: right; border-bottom: 1px solid rgba(15, 23, 42, 0.06); }
        th { background: #f8fafc; color: #64748b; font-weight: 700; font-size: 0.8rem; }
        td { color: #475569; }
        tr:last-child td { border-bottom: none; }
        .present { color: #15803d; font-weight: 700; }
        .leave { color: #115E59; font-weight: 700; }
        .absent { color: #b91c1c; font-weight: 700; }
        .pill {
            display: inline-flex; align-items: center; justify-content: center;
            border-radius: 0.5rem; padding: 0.25rem 0.7rem; font-size: 0.75rem; font-weight: 700;
            border: 1px solid transparent;
        }
        .pill-present { background: #dcfce7; color: #15803d; border-color: #86efac; }
        .pill-leave { background: #F0FDFA; color: #115E59; border-color: #5EEAD4; }
        .pill-absent { background: #fee2e2; color: #b91c1c; border-color: #fca5a5; }
        @media print { body { padding: 0; background: #fff; } .no-print { display: none; } .card { box-shadow: none; } }
    </style>
</head>
<body>
    <button class="no-print" onclick="window.print()" style="margin-bottom:1rem;padding:0.55rem 1.1rem;background:#069494;color:#fff;border:0;border-radius:0.75rem;cursor:pointer;font-weight:700;">طباعة</button>
    <h1>تقرير حضور {{ $report['scope'] === 'children' ? 'الأطفال' : 'طاقم العمل' }}</h1>
    <p class="meta">من {{ $report['from'] }} إلى {{ $report['to'] }}</p>

    @forelse($report['rows'] as $row)
        <h2>{{ $row['name'] }}</h2>
        <div class="card">
            <table>
                <thead>
                    <tr>
                        <th>التاريخ</th>
                        <th>الحالة</th>
                        @if($report['include_absence_reason'])<th>السبب</th>@endif
                    </tr>
                </thead>
                <tbody>
                    @foreach($row['days'] as $day)
                        <tr>
                            <td>{{ $day['date'] }}</td>
                            <td>
                                @if($day['status'] === 'present')
                                    <span class="pill pill-present">حاضر</span>
                                @elseif($day['status'] === 'leave')
                                    <span class="pill pill-leave">إجازة</span>
                                @else
                                    <span class="pill pill-absent">غائب</span>
                                @endif
                            </td>
                            @if($report['include_absence_reason'])<td>{{ $day['reason'] ?? '—' }}</td>@endif
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @empty
        <p>لا توجد بيانات للفترة المحددة.</p>
    @endforelse
</body>
</html>
