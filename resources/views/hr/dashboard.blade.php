@extends('layouts.app')

@section('title', 'لوحة الموارد البشرية - MIRADA ERP')

@section('breadcrumb')
    <a href="{{ route('dashboard') }}" class="text-gray-500 hover:text-indigo-600">الرئيسية</a>
    <span>›</span>
    <span class="text-indigo-900 font-semibold truncate">الموارد البشرية</span>
    <span>›</span>
    <span class="text-gray-600 truncate">لوحة التحكم</span>
@endsection

@section('content')
<div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-xl font-bold text-gray-900">لوحة تحكم الموارد البشرية</h1>
            <p class="text-sm text-gray-500 mt-1">نظرة عامة على مقاييس وأنشطة الموارد البشرية</p>
        </div>
        <a href="{{ route('hr.employees.create') }}" class="inline-flex items-center justify-center gap-2 rounded-lg bg-indigo-600 text-white text-sm font-medium px-4 py-2.5 hover:bg-indigo-700 shrink-0">
            <span>موظف جديد</span>
            <span class="text-lg leading-none">+</span>
        </a>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
        <div class="erp-card rounded-lg p-5">
            <p class="text-xs font-medium text-gray-500 flex items-center gap-1">إجمالي الموظفين <x-info field="hr.total_employees" /></p>
            <p class="text-2xl font-bold text-gray-900 mt-2 tabular-nums">{{ number_format($totalActiveEmployees) }}</p>
        </div>
        <div class="erp-card rounded-lg p-5">
            <p class="text-xs font-medium text-gray-500 flex items-center gap-1">الحاضرون اليوم <x-info field="hr.present_today" /></p>
            <p class="text-2xl font-bold text-gray-900 mt-2 tabular-nums">{{ number_format($metrics['present_today']) }}</p>
        </div>
        <div class="erp-card rounded-lg p-5">
            <p class="text-xs font-medium text-gray-500 flex items-center gap-1">في إجازة اليوم <x-info field="hr.on_leave_today" /></p>
            <p class="text-2xl font-bold text-gray-900 mt-2 tabular-nums">{{ number_format($metrics['on_leave_today']) }}</p>
        </div>
        <div class="erp-card rounded-lg p-5">
            <p class="text-xs font-medium text-gray-500 flex items-center gap-1">التعيينات الجديدة (الشهر) <x-info field="hr.new_hires_month" /></p>
            <p class="text-2xl font-bold text-gray-900 mt-2 tabular-nums">{{ number_format($metrics['new_hires_month']) }}</p>
        </div>
        <div class="erp-card rounded-lg p-5">
            <p class="text-xs font-medium text-gray-500 flex items-center gap-1">الأقسام <x-info field="hr.department_count" /></p>
            <p class="text-2xl font-bold text-gray-900 mt-2 tabular-nums">{{ number_format($departmentCount) }}</p>
        </div>
        <div class="erp-card rounded-lg p-5">
            <p class="text-xs font-medium text-gray-500 flex items-center gap-1">الغائبون اليوم <x-info field="hr.absent_today" /></p>
            <p class="text-2xl font-bold text-gray-900 mt-2 tabular-nums">{{ number_format($metrics['absent_today']) }}</p>
        </div>
        <div class="erp-card rounded-lg p-5">
            <p class="text-xs font-medium text-gray-500 flex items-center gap-1">العمل الإضافي المعلق <x-info field="hr.pending_overtime" /></p>
            <p class="text-2xl font-bold text-gray-900 mt-2 tabular-nums">{{ number_format($metrics['pending_overtime']) }}</p>
        </div>
        <div class="erp-card rounded-lg p-5">
            <p class="text-xs font-medium text-gray-500 flex items-center gap-1">متوسط الراتب <x-info field="hr.avg_salary" /></p>
            <p class="text-2xl font-bold text-indigo-700 mt-2 tabular-nums">ر.س {{ number_format($avgSalary, 2) }}</p>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div class="erp-card rounded-lg p-5 min-h-[280px]">
            <h2 class="text-sm font-semibold text-gray-800 mb-4 flex items-center gap-1">توزيع الجنس <x-info field="hr.gender_chart" /></h2>
            <div class="h-56 flex items-center justify-center">
                <canvas id="hrGenderChart" class="max-h-56"></canvas>
            </div>
        </div>
        <div class="erp-card rounded-lg p-5 min-h-[280px]">
            <h2 class="text-sm font-semibold text-gray-800 mb-4 flex items-center gap-1">اتجاه الحضور (آخر 14 يوماً) <x-info field="hr.attendance_trend" /></h2>
            <div class="h-56">
                <canvas id="hrAttendanceChart"></canvas>
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
                    legend: { position: 'bottom', labels: { font: { family: 'Cairo' } } },
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
