<?php

declare(strict_types=1);

namespace App\Services\Nursery\Portal;

use App\Models\Nursery\AttendanceLog;
use App\Models\Nursery\Child;
use App\Services\Nursery\NurseryChildDailyActivityService;
use Carbon\Carbon;

/**
 * ملف الطفل في بوابة ولي الأمر — قراءة فقط.
 */
final class NurseryPortalChildProfileService
{
    public function __construct(
        private readonly NurseryPortalAccessService $access,
    ) {}

    /**
     * @return array{
     *     child: Child,
     *     todayLog: AttendanceLog|null,
     *     todayStatus: string,
     *     todayStatusLabel: string,
     *     dailyActivities: \Illuminate\Support\Collection<int, \App\Models\Nursery\ChildDailyActivity>,
     *     dailySummary: list<array{type: string, label: string, lines: list<string>}>
     * }
     */
    public function profile(int $tenantUserId, int $guardianId, int $childId, ?Carbon $date = null): array
    {
        $child = $this->access->assertGuardianOwnsChild($tenantUserId, $guardianId, $childId);
        $child->load(['medications', 'activeEnrollment.classroom']);

        $date ??= now();
        $todayLog = AttendanceLog::query()
            ->withoutGlobalScopes()
            ->where('user_id', $tenantUserId)
            ->where('child_id', $child->id)
            ->whereDate('attendance_date', $date->toDateString())
            ->first();

        $status = $this->resolveTodayStatus($todayLog);
        $labels = self::statusLabels();

        $dailyActivities = app(NurseryChildDailyActivityService::class)->forChildOnDate(
            $tenantUserId,
            (int) $child->id,
            $date->toDateString(),
            true,
        );

        return [
            'child' => $child,
            'todayLog' => $todayLog,
            'todayStatus' => $status,
            'todayStatusLabel' => $labels[$status] ?? $status,
            'dailyActivities' => $dailyActivities,
            'dailySummary' => app(NurseryChildDailyActivityService::class)->summary($dailyActivities),
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function statusLabels(): array
    {
        return [
            'present' => 'حاضر',
            'late' => 'متأخر',
            'absent' => 'غائب',
            'checked_out' => 'انصرف',
            'no_record' => 'لا يوجد سجل اليوم',
        ];
    }

    private function resolveTodayStatus(?AttendanceLog $log): string
    {
        if ($log === null) {
            return 'no_record';
        }

        if ($log->checked_out_at !== null) {
            return 'checked_out';
        }

        return match ($log->status) {
            AttendanceLog::STATUS_LATE => 'late',
            AttendanceLog::STATUS_ABSENT => 'absent',
            default => 'present',
        };
    }
}
