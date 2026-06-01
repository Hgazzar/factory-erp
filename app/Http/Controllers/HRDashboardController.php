<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Leave;
use App\Models\OvertimeRequest;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class HRDashboardController extends Controller
{
    public function index(): View
    {
        $totalActiveEmployees = Employee::query()->where('status', 'active')->count();
        $departmentCount = Schema::hasTable('departments')
            ? (int) Department::query()->count()
            : 0;

        $genderBuckets = collect();
        if (Schema::hasColumn('employees', 'gender')) {
            $genderBuckets = Employee::query()
                ->where('status', 'active')
                ->pluck('gender')
                ->map(fn ($g) => $g ? strtolower((string) $g) : 'unspecified')
                ->countBy();
        }

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

        $today = today()->toDateString();

        $presentToday = (int) Attendance::query()
            ->whereDate('work_date', $today)
            ->whereIn('status', [Attendance::STATUS_PRESENT, Attendance::STATUS_LATE])
            ->count();

        $absentToday = (int) Attendance::query()
            ->whereDate('work_date', $today)
            ->where('status', Attendance::STATUS_ABSENT)
            ->count();

        $metrics = [
            'present_today' => $presentToday,
            'on_leave_today' => Schema::hasTable('leaves')
                ? (int) Leave::query()
                    ->where('status', Leave::STATUS_APPROVED)
                    ->whereDate('start_date', '<=', $today)
                    ->whereDate('end_date', '>=', $today)
                    ->count()
                : 0,
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
            'absent_today' => $absentToday,
            'pending_overtime' => Schema::hasTable('overtime_requests')
                ? (int) OvertimeRequest::query()
                    ->where('status', OvertimeRequest::STATUS_NEW)
                    ->count()
                : 0,
        ];

        $attendanceTrendLabels = [];
        $attendanceTrendData = [];
        $trendStart = Carbon::today()->subDays(13)->toDateString();
        $trendEnd = Carbon::today()->toDateString();

        $dailyPresent = Attendance::query()
            ->whereBetween('work_date', [$trendStart, $trendEnd])
            ->whereIn('status', [Attendance::STATUS_PRESENT, Attendance::STATUS_LATE])
            ->select('work_date', DB::raw('COUNT(*) as cnt'))
            ->groupBy('work_date')
            ->pluck('cnt', 'work_date');

        for ($i = 13; $i >= 0; $i--) {
            $d = Carbon::today()->subDays($i);
            $key = $d->toDateString();
            $attendanceTrendLabels[] = $d->format('m-d');
            $attendanceTrendData[] = (int) ($dailyPresent[$key] ?? 0);
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
