<?php

namespace App\Models;

use App\Models\Scopes\BelongsToAuthenticatedUserScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AttendanceLog extends Model
{
    public const DIRECTION_IN = 'in';

    public const DIRECTION_OUT = 'out';

    public const SOURCE_EXCEL_IMPORT = 'excel_import';

    public const SOURCE_API_SYNC = 'api_sync';

    protected $fillable = [
        'user_id',
        'attendance_id',
        'employee_id',
        'employee_device_id',
        'logged_at',
        'direction',
        'source',
        'meta',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope(new BelongsToAuthenticatedUserScope);

        static::creating(function (AttendanceLog $log): void {
            if ($log->user_id === null && $log->attendance_id) {
                $log->user_id = Attendance::withoutGlobalScopes()
                    ->whereKey($log->attendance_id)
                    ->value('user_id');
            }

            if ($log->user_id === null && $log->employee_id) {
                $log->user_id = Employee::withoutGlobalScopes()
                    ->whereKey($log->employee_id)
                    ->value('user_id');
            }
        });
    }

    protected function casts(): array
    {
        return [
            'logged_at' => 'datetime',
            'meta' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function attendance(): BelongsTo
    {
        return $this->belongsTo(Attendance::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }
}
