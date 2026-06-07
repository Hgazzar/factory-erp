<?php

declare(strict_types=1);

namespace App\Models\Nursery;

use App\Models\Concerns\ResolvesRouteBindingForTenant;
use App\Models\Scopes\BelongsToTenantContextScope;
use App\Models\User;
use Illuminate\Database\Eloquent\Attributes\ScopedBy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[ScopedBy([BelongsToTenantContextScope::class])]
class AttendanceLog extends Model
{
    use ResolvesRouteBindingForTenant;

    public const STATUS_PRESENT = 'present';

    public const STATUS_LATE = 'late';

    public const STATUS_ABSENT = 'absent';

    protected $table = 'nursery_attendance_logs';

    protected $fillable = [
        'user_id',
        'child_id',
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

    public function child(): BelongsTo
    {
        return $this->belongsTo(Child::class, 'child_id');
    }

    public function recorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    public function isCheckedIn(): bool
    {
        return $this->checked_in_at !== null && $this->checked_out_at === null;
    }
}
