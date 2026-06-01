@extends('layouts.app')

@section('title', 'لوحة الموارد البشرية - '.config('app.name'))

@section('breadcrumb')
    <a href="{{ route('dashboard') }}" class="text-gray-500 hover:text-indigo-600">الرئيسية</a>
    <span>›</span>
    <span class="text-gray-500">الموارد البشرية</span>
    <span>›</span>
    <span class="text-indigo-900 font-semibold truncate">لوحة التحكم</span>
@endsection

@php
    $attendanceRate = $totalActiveEmployees > 0
        ? round(($metrics['present_today'] / max($totalActiveEmployees, 1)) * 100, 1)
        : 0.0;
    $kpiCards = [
        [
            'label' => 'إجمالي الموظفين',
            'value' => number_format($totalActiveEmployees),
            'sub' => number_format($totalActiveEmployees).' نشط',
            'hint' => 'hr.total_employees',
            'iconBg' => 'bg-blue-100',
            'iconColor' => 'text-blue-600',
            'svg' => '<path d="M7 14s-1 0-1-1 1-4 5-4 5 3 5 4-1 1-1 1H7zm4-6a3 3 0 1 0 0-6 3 3 0 0 0 0 6z"/><path fill-rule="evenodd" d="M5.216 14A2.238 2.238 0 0 1 5 13c0-1.355.68-2.75 1.936-3.72A6.325 6.325 0 0 0 5 9c-4 0-5 3-5 4s1 1 1 1h4.216z"/>',
        ],
        [
            'label' => 'الحاضرون اليوم',
            'value' => number_format($metrics['present_today']),
            'sub' => $attendanceRate.'% معدل الحضور',
            'hint' => 'hr.present_today',
            'iconBg' => 'bg-emerald-100',
            'iconColor' => 'text-emerald-600',
            'svg' => '<path fill-rule="evenodd" d="M16 8A8 8 0 1 1 0 8a8 8 0 0 1 16 0zm-3.97-3.03a.75.75 0 0 0-1.08.022L7.477 9.417 5.384 7.323a.75.75 0 0 0-1.06 1.06L6.97 11.03a.75.75 0 0 0 1.079-.02l3.992-4.99a.75.75 0 0 0-.01-1.05z"/>',
        ],
        [
            'label' => 'في إجازة اليوم',
            'value' => number_format($metrics['on_leave_today']),
            'sub' => 'طلبات معلّقة: —',
            'hint' => 'hr.on_leave_today',
            'iconBg' => 'bg-amber-100',
            'iconColor' => 'text-amber-600',
            'svg' => '<path d="M3.5 0a.5.5 0 0 1 .5.5V1h8V.5a.5.5 0 0 1 1 0V1h1a2 2 0 0 1 2 2v11a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2V3a2 2 0 0 1 2-2h1V.5a.5.5 0 0 1 .5-.5zM1 4v10a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1V4H1z"/><path d="M11 7.5a.5.5 0 0 1 .5.5v1a.5.5 0 0 1-.5.5H9a.5.5 0 0 1 0-1h2zm-6 0H3a.5.5 0 0 0 0 1h2a.5.5 0 0 0 0-1z"/>',
        ],
        [
            'label' => 'التعيينات الجديدة (الشهر)',
            'value' => number_format($metrics['new_hires_month']),
            'sub' => 'إنهاء خدمة الشهر: —',
            'hint' => 'hr.new_hires_month',
            'iconBg' => 'bg-violet-100',
            'iconColor' => 'text-violet-600',
            'svg' => '<path d="M2.866 14.85c-.078.444.36.791.746.593L8 13.187l4.389 2.256c.386.198.824-.149.746-.592l-.83-4.73 3.523-3.356c.329-.314.158-.888-.283-.95l-4.898-.696L8.465.792A.513.513 0 0 0 8 .5c-.197 0-.39.12-.465.3L4.568 5.087l-4.898.696c-.441.062-.612.636-.283.95l3.523 3.356-.83 4.73z"/>',
        ],
        [
            'label' => 'الأقسام',
            'value' => number_format($departmentCount),
            'sub' => null,
            'hint' => 'hr.department_count',
            'iconBg' => 'bg-sky-100',
            'iconColor' => 'text-sky-600',
            'svg' => '<path d="M14 1a1 1 0 0 1 1 1v12h-2v1h-1v-1H4v1H3v-1H1V2a1 1 0 0 1 1-1h12ZM2 2v11h12V2H2ZM4 4h2v2H4V4Zm3 0h2v2H7V4Zm3 0h2v2h-2V4ZM4 7h2v2H4V7Zm3 0h2v2H7V7Zm3 0h2v2h-2V7Z"/>',
        ],
        [
            'label' => 'الغائبون اليوم',
            'value' => number_format($metrics['absent_today']),
            'sub' => null,
            'hint' => 'hr.absent_today',
            'iconBg' => 'bg-red-100',
            'iconColor' => 'text-red-600',
            'svg' => '<path d="M4.646 4.646a.5.5 0 0 1 .708 0L8 7.293l2.646-2.647a.5.5 0 0 1 .708.708L8.707 8l2.647 2.646a.5.5 0 0 1-.708.708L8 8.707l-2.646 2.647a.5.5 0 0 1-.708-.708L7.293 8 4.646 5.354a.5.5 0 0 1 0-.708z"/>',
        ],
        [
            'label' => 'العمل الإضافي المعلق',
            'value' => number_format($metrics['pending_overtime']),
            'sub' => null,
            'hint' => 'hr.pending_overtime',
            'iconBg' => 'bg-orange-100',
            'iconColor' => 'text-orange-600',
            'svg' => '<path d="M8 3.5a.5.5 0 0 0-1 0V9a.5.5 0 0 0 .252.434l3.5 2a.5.5 0 0 0 .496-.868L8 8.71V3.5z"/><path d="M8 16A8 8 0 1 0 8 0a8 8 0 0 0 0 16zm7-8A7 7 0 1 1 1 8a7 7 0 0 1 14 0z"/>',
        ],
        [
            'label' => 'متوسط الراتب',
            'value' => 'ر.س '.number_format($avgSalary, 2),
            'sub' => 'للموظفين النشطين',
            'hint' => 'hr.avg_salary',
            'iconBg' => 'bg-teal-100',
            'iconColor' => 'text-teal-600',
            'svg' => '<path d="M0 3.5A1.5 1.5 0 0 1 1.5 2h13A1.5 1.5 0 0 1 16 3.5v2.382l-8 4-8-4V3.5z"/><path d="M16 7.384v5.116a1.5 1.5 0 0 1-1.5 1.5h-13A1.5 1.5 0 0 1 0 12.5V7.384l8 4 8-4z"/>',
        ],
    ];
@endphp

@section('content')
<div class="max-w-full bg-gray-50 min-h-[calc(100vh-8rem)] -mx-4 sm:-mx-6 px-4 sm:px-6 py-6" dir="rtl">
    <div class="mb-6 flex flex-wrap items-center justify-end gap-2 rounded-lg border border-gray-200 bg-white p-3 shadow-sm">
        <a href="{{ route('hr.employees.create') }}" class="inline-flex items-center gap-2 rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-blue-700">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" viewBox="0 0 16 16" aria-hidden="true"><path d="M8 4a.5.5 0 0 1 .5.5v3h3a.5.5 0 0 1 0 1h-3v3a.5.5 0 0 1-1 0v-3h-3a.5.5 0 0 1 0-1h3v-3A.5.5 0 0 1 8 4z"/></svg>
            موظف جديد
        </a>
        <a href="{{ route('hr.attendance') }}" class="inline-flex items-center gap-2 rounded-lg border border-gray-200 bg-white px-4 py-2 text-sm font-semibold text-gray-800 shadow-sm hover:bg-gray-50">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" viewBox="0 0 16 16" aria-hidden="true"><path d="M8 3.5a.5.5 0 0 0-1 0V9a.5.5 0 0 0 .252.434l3.5 2a.5.5 0 0 0 .496-.868L8 8.71V3.5z"/><path d="M8 16A8 8 0 1 0 8 0a8 8 0 0 0 0 16zm7-8A7 7 0 1 1 1 8a7 7 0 0 1 14 0z"/></svg>
            الحضور
        </a>
        @can('manage_payroll')
        <a href="{{ route('hr.payrolls.create') }}" class="inline-flex items-center gap-2 rounded-lg border border-indigo-200 bg-indigo-50 px-4 py-2 text-sm font-semibold text-indigo-900 shadow-sm hover:bg-indigo-100">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" viewBox="0 0 16 16" aria-hidden="true"><path d="M0 2.5A1.5 1.5 0 0 1 1.5 1h11A1.5 1.5 0 0 1 14 2.5v1.382c.307.345.5.729.5 1.118 0 .391-.193.774-.5 1.118V12.5A1.5 1.5 0 0 1 12.5 14h-11A1.5 1.5 0 0 1 0 12.5V9.382a1.497 1.497 0 0 1-.5-1.118c0-.39.193-.774.5-1.118V2.5z"/></svg>
            دورة رواتب جديدة
        </a>
        <a href="{{ route('hr.payrolls.index') }}" class="inline-flex items-center gap-2 rounded-lg border border-gray-200 bg-white px-4 py-2 text-sm font-semibold text-gray-800 shadow-sm hover:bg-gray-50">الرواتب</a>
        @endcan
    </div>

    <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:flex-wrap sm:items-center sm:justify-between">
        <div>
            <h1 class="inline-flex flex-wrap items-center gap-2 text-2xl font-bold text-gray-900">
                لوحة تحكم الموارد البشرية
                <x-info field="hr.dashboard_intro" />
            </h1>
            <p class="mt-1 text-sm text-gray-500">نظرة عامة على مقاييس وأنشطة الموارد البشرية</p>
        </div>
    </div>

    <div class="mb-8 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
        @foreach($kpiCards as $card)
            <div class="flex min-h-[100px] items-start justify-between gap-3 rounded-lg border border-gray-200 bg-white p-4 shadow-sm">
                <div class="min-w-0 flex-1">
                    <div class="mb-1 inline-flex items-center gap-1 text-xs font-semibold text-gray-500">
                        {{ $card['label'] }}
                        <x-info field="{{ $card['hint'] }}" />
                    </div>
                    <div class="text-2xl font-bold tracking-tight text-gray-900 tabular-nums">{{ $card['value'] }}</div>
                    @if(! empty($card['sub']))
                        <div class="mt-1 text-xs text-gray-400">{{ $card['sub'] }}</div>
                    @endif
                </div>
                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full {{ $card['iconBg'] }} {{ $card['iconColor'] }}">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" viewBox="0 0 16 16" aria-hidden="true">{!! $card['svg'] !!}</svg>
                </div>
            </div>
        @endforeach
    </div>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        <div class="flex min-h-[300px] flex-col rounded-lg border border-gray-200 bg-white p-4 shadow-sm sm:p-6 lg:col-span-2">
            <h2 class="mb-4 inline-flex items-center gap-1 text-lg font-bold text-gray-900">
                اتجاه الحضور (آخر 14 يوماً)
                <x-info field="hr.attendance_trend" />
            </h2>
            <p class="mb-4 text-sm text-gray-500">عدد تسجيلات الحضور اليومية عند توفر البيانات</p>
            <div class="relative min-h-[220px] flex-1" dir="ltr">
                <canvas id="hrAttendanceChart" class="max-h-72"></canvas>
            </div>
        </div>
        <div class="flex min-h-[300px] flex-col rounded-lg border border-gray-200 bg-white p-4 shadow-sm sm:p-6">
            <h2 class="mb-4 inline-flex items-center gap-1 text-lg font-bold text-gray-900">
                توزيع الجنس
                <x-info field="hr.gender_chart" />
            </h2>
            <div class="relative flex flex-1 items-center justify-center" dir="ltr">
                <canvas id="hrGenderChart" class="max-h-56 w-full max-w-[240px]"></canvas>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const genderLabels = @json($genderLabels);
    const genderCounts = @json($genderCounts);
    const attendanceLabels = @json($attendanceTrendLabels);
    const attendanceData = @json($attendanceTrendData);

    const gCtx = document.getElementById('hrGenderChart');
    if (gCtx && typeof Chart !== 'undefined') {
        new Chart(gCtx, {
            type: 'doughnut',
            data: {
                labels: genderLabels,
                datasets: [{
                    data: genderCounts,
                    backgroundColor: ['#6366f1', '#a855f7', '#14b8a6', '#94a3b8'],
                    borderWidth: 0,
                }],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'bottom', labels: { font: { family: 'Cairo, sans-serif' } } },
                },
            },
        });
    }

    const aCtx = document.getElementById('hrAttendanceChart');
    if (aCtx && typeof Chart !== 'undefined') {
        new Chart(aCtx, {
            type: 'line',
            data: {
                labels: attendanceLabels,
                datasets: [{
                    label: 'الحضور',
                    data: attendanceData,
                    borderColor: '#2563eb',
                    backgroundColor: 'rgba(37, 99, 235, 0.1)',
                    fill: true,
                    tension: 0.3,
                }],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    y: { beginAtZero: true, ticks: { stepSize: 1 } },
                },
            },
        });
    }
});
</script>
@endpush
@endsection
