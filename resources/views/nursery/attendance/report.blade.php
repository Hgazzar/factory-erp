<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <title>تقرير حضور — {{ config('app.name') }}</title>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: Cairo, sans-serif; padding: 2rem; color: #431407; }
        h1 { font-size: 1.5rem; margin-bottom: 0.25rem; }
        .meta { color: #9a3412; margin-bottom: 1.5rem; font-size: 0.9rem; }
        table { width: 100%; border-collapse: collapse; font-size: 0.85rem; }
        th, td { border: 1px solid #fed7aa; padding: 0.5rem; text-align: right; }
        th { background: #fff7ed; }
        .present { color: #047857; }
        .leave { color: #b45309; }
        .absent { color: #b91c1c; }
        @media print { body { padding: 0; } .no-print { display: none; } }
    </style>
</head>
<body>
    <button class="no-print" onclick="window.print()" style="margin-bottom:1rem;padding:0.5rem 1rem;background:#f97316;color:#fff;border:0;border-radius:0.5rem;cursor:pointer;">طباعة</button>
    <h1>تقرير حضور {{ $report['scope'] === 'children' ? 'الأطفال' : 'طاقم العمل' }}</h1>
    <p class="meta">من {{ $report['from'] }} إلى {{ $report['to'] }}</p>

    @forelse($report['rows'] as $row)
        <h2 style="font-size:1.1rem;margin:1.25rem 0 0.5rem;">{{ $row['name'] }}</h2>
        <table>
            <thead>
                <tr><th>التاريخ</th><th>الحالة</th>@if($report['include_absence_reason'])<th>السبب</th>@endif</tr>
            </thead>
            <tbody>
                @foreach($row['days'] as $day)
                    <tr>
                        <td>{{ $day['date'] }}</td>
                        <td class="{{ $day['status'] }}">
                            @if($day['status'] === 'present') حاضر
                            @elseif($day['status'] === 'leave') إجازة
                            @else غائب @endif
                        </td>
                        @if($report['include_absence_reason'])<td>{{ $day['reason'] ?? '—' }}</td>@endif
                    </tr>
                @endforeach
            </tbody>
        </table>
    @empty
        <p>لا توجد بيانات للفترة المحددة.</p>
    @endforelse
</body>
</html>
