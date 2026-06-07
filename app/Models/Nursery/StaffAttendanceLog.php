<?php

declare(strict_types=1);

namespace App\Models\Nursery;

use App\Models\Concerns\ResolvesRouteBindingForTenant;
use App\Models\Employee;
use App\Models\Scopes\BelongsToTenantContextScope;
use App\Models\User;
use Illuminate\Database\Eloquent\Attributes\ScopedBy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[ScopedBy([BelongsToTenantContextScope::class])]
class StaffAttendanceLog extends Model
{
    use ResolvesRouteBindingForTenant;

    public const STATUS_PRESENT = 'present';

    public const STATUS_LEAVE = 'leave';

    public const STATUS_ABSENT = 'absent';

    protected $table = 'nursery_staff_attendance_logs';

    protected $fillable = [
        'user_id',
        'employee_id',
        'attendance_date',
        'checked_in_at',
        'checked_out_at',
        'status',
        'notes',
        'recorded_by',
    ];

    protected function casts(): array
    {
        return [
            'attendance_date' => 'date',
            'checked_in_at' => 'datetime',
            'checked_out_at' => 'datetime',
        ];
    }

    public function tenantUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }
}
