<?php

namespace App\Models;

use App\Models\Concerns\ResolvesRouteBindingForTenant;
use App\Models\Scopes\BelongsToAuthenticatedUserScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OvertimeRequest extends Model
{
    use ResolvesRouteBindingForTenant;

    public const STATUS_NEW = 'new';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_REJECTED = 'rejected';

    public const KIND_REGULAR = 'regular';

    public const KIND_HOLIDAY = 'holiday';

    public const KIND_FULL_DAY = 'full_day';

    protected $fillable = [
        'user_id',
        'employee_id',
        'work_date',
        'kind',
        'time_start',
        'time_end',
        'hours',
        'reason',
        'status',
        'rate_multiplier',
        'approved_by',
        'approved_at',
        'rejected_by',
        'rejected_at',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope(new BelongsToAuthenticatedUserScope);
    }

    protected function casts(): array
    {
        return [
            'work_date' => 'date',
            'hours' => 'decimal:2',
            'rate_multiplier' => 'decimal:2',
            'approved_at' => 'datetime',
            'rejected_at' => 'datetime',
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function rejector(): BelongsTo
    {
        return $this->belongsTo(User::class, 'rejected_by');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function statusLabelAr(): string
    {
        return match ($this->status) {
            self::STATUS_APPROVED => 'معتمد',
            self::STATUS_REJECTED => 'مرفوض',
            default => 'جديد',
        };
    }

    public function kindLabelAr(): string
    {
        return match ($this->kind ?? '') {
            self::KIND_HOLIDAY => 'عطلة',
            self::KIND_FULL_DAY => 'يوم كامل',
            self::KIND_REGULAR, '' => 'عادي',
            default => 'عادي',
        };
    }

    public static function rateMultiplierForKind(string $kind): float
    {
        return match ($kind) {
            self::KIND_HOLIDAY => 2.0,
            self::KIND_FULL_DAY => (float) config('hr.payroll.overtime_hourly_rate_multiplier', 1.5),
            default => 1.5,
        };
    }
}
