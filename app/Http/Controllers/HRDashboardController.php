<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\Employee;
use App\Models\Leave;
use App\Models\OvertimeRequest;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class HRDashboardController extends Controller
{
    public function index(): View
    {
        $totalActiveEmployees = Employee::query()->where('status', 'active')->count();
        $departmentCount = Department::query()->count();

        $genderBuckets = Employee::query()
            ->where('status', 'active')
            ->pluck('gender')
            ->map(fn ($g) => $g ? strtolower((string) $g) : 'unspecified')
            ->countBy();

        $genderLabels = [];
        $genderCounts = [];
        $labelMap = [
            'male' => 'ذكر',
            'female' => 'أنثى',
            'other' => 'آخر',
            'unspecified' => 'غير محدد',
        ];
        foreach ($genderBuckets as $key => $count) {
            $genderLabels[] = $labelMap[$key] ?? $key;
            $genderCounts[] = (int) $count;
        }

        if ($genderLabels === []) {
            $genderLabels = ['غير محدد'];
            $genderCounts = [0];
        }

        $avgSalary = Employee::query()
            ->where('status', 'active')
            ->avg('base_salary');

        $metrics = [
            'present_today' => 0,
            'on_leave_today' => Leave::query()
                ->where('status', Leave::STATUS_APPROVED)
                ->whereDate('start_date', '<=', today()->toDateString())
                ->whereDate('end_date', '>=', today()->toDateString())
                ->count(),
            'new_hires_month' => Employee::query()
                ->where('status', 'active')
                ->where(function ($q) {
                    $start = now()->startOfMonth();
                    $end = now()->endOfMonth();
                    $q->where(function ($q2) use ($start, $end) {
                        $q2->whereNotNull('hire_date')
                            ->whereBetween('hire_date', [$start, $end]);
                    })->orWhere(function ($q2) use ($start, $end) {
                        $q2->whereNull('hire_date')
                            ->whereBetween('hired_at', [$start, $end]);
                    });
                })
                ->count(),
            'absent_today' => 0,
            'pending_overtime' => OvertimeRequest::query()
                ->where('status', OvertimeRequest::STATUS_NEW)
                ->count(),
        ];

        $attendanceTrendLabels = [];
        $attendanceTrendData = [];
        for ($i = 13; $i >= 0; $i--) {
            $d = Carbon::today()->subDays($i);
            $attendanceTrendLabels[] = $d->format('m-d');
            $attendanceTrendData[] = 0;
        }

        return view('hr.dashboard', [
            'header' => 'لوحة تحكم الموارد البشرية',
            'totalActiveEmployees' => $totalActiveEmployees,
            'departmentCount' => $departmentCount,
            'genderLabels' => $genderLabels,
            'genderCounts' => $genderCounts,
            'avgSalary' => $avgSalary !== null ? (float) $avgSalary : 0.0,
            'metrics' => $metrics,
            'attendanceTrendLabels' => $attendanceTrendLabels,
            'attendanceTrendData' => $attendanceTrendData,
        ]);
    }
}
