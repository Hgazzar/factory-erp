<?php

namespace App\Models;

use App\Models\Scopes\BelongsToAuthenticatedUserScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Attendance extends Model
{
    public const STATUS_PRESENT = 'present';

    public const STATUS_LATE = 'late';

    public const STATUS_ABSENT = 'absent';

    public const STATUS_LEAVE = 'leave';

    protected $fillable = [
        'user_id',
        'employee_id',
        'work_date',
        'check_in_at',
        'check_out_at',
        'status',
        'minutes_late',
        'work_hours',
        'deduction_amount',
        'notes',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope(new BelongsToAuthenticatedUserScope);

        static::creating(function (Attendance $attendance): void {
            if ($attendance->user_id === null && $attendance->employee_id) {
                $attendance->user_id = Employee::query()
                    ->whereKey($attendance->employee_id)
                    ->value('user_id');
            }
        });
    }

    protected function casts(): array
    {
        return [
            'work_date' => 'date',
            'check_in_at' => 'datetime',
            'check_out_at' => 'datetime',
            'work_hours' => 'decimal:2',
            'deduction_amount' => 'decimal:2',
            'minutes_late' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function logs(): HasMany
    {
        return $this->hasMany(AttendanceLog::class);
    }

    /**
     * @return array<string, mixed>
     */
    public static function buildViewRow(Employee $employee, ?self $attendance): array
    {
        if (($employee->status ?? 'active') !== 'active') {
            return [
                'employee' => $employee,
                'status_key' => self::STATUS_ABSENT,
                'status_label' => 'غائب',
                'minutes_late' => 0,
                'minutes_late_display' => '—',
                'deduction_display' => '—',
                'check_in' => '—',
                'check_out' => '—',
                'work_hours' => '—',
            ];
        }

        if ($attendance === null) {
            return [
                'employee' => $employee,
                'status_key' => self::STATUS_ABSENT,
                'status_label' => 'غائب',
                'minutes_late' => 0,
                'minutes_late_display' => '—',
                'deduction_display' => '—',
                'check_in' => '—',
                'check_out' => '—',
                'work_hours' => '—',
            ];
        }

        $statusKey = in_array($attendance->status, [self::STATUS_PRESENT, self::STATUS_LATE, self::STATUS_ABSENT, self::STATUS_LEAVE], true)
            ? $attendance->status
            : self::STATUS_ABSENT;

        $minutesLate = (int) $attendance->minutes_late;
        $workHours = $attendance->work_hours;

        $minutesLateDisplay = match ($statusKey) {
            self::STATUS_LATE => (string) $minutesLate,
            self::STATUS_PRESENT => '0',
            self::STATUS_LEAVE => '0',
            default => '—',
        };

        $deductionDisplay = '—';
        if ($attendance->deduction_amount !== null) {
            $deductionDisplay = number_format((float) $attendance->deduction_amount, 2);
        } elseif ($statusKey === self::STATUS_LATE) {
            $deductionDisplay = '—';
        }

        return [
            'employee' => $employee,
            'status_key' => $statusKey,
            'status_label' => $statusKey === self::STATUS_PRESENT
                ? 'حاضر'
                : ($statusKey === self::STATUS_LATE ? 'متأخر' : ($statusKey === self::STATUS_LEAVE ? 'إجازة مدفوعة' : 'غائب')),
            'minutes_late' => $minutesLate,
            'minutes_late_display' => $minutesLateDisplay,
            'deduction_display' => $deductionDisplay,
            'check_in' => $attendance->check_in_at ? $attendance->check_in_at->format('H:i') : '—',
            'check_out' => $attendance->check_out_at ? $attendance->check_out_at->format('H:i') : '—',
            'work_hours' => $workHours !== null ? number_format((float) $workHours, 2) : '—',
        ];
    }

    /**
     * صف تاريخ واحد لسجل الحضور في شاشة الموظف (نفس مفاتيح الواجهة).
     *
     * @return array<string, mixed>
     */
    public static function buildHistoryRowForDate(string $date, Employee $employee, ?self $attendance): array
    {
        $row = self::buildViewRow($employee, $attendance);

        return [
            'date' => $date,
            'status_key' => $row['status_key'],
            'status_label' => $row['status_label'],
            'minutes_late' => $row['minutes_late'],
            'minutes_late_display' => $row['minutes_late_display'],
            'deduction_display' => $row['deduction_display'],
            'check_in' => $row['check_in'],
            'check_out' => $row['check_out'],
            'work_hours' => $row['work_hours'],
        ];
    }
}
