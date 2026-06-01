<?php

namespace App\Models;

use App\Models\Scopes\BelongsToTenantContextScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Leave extends Model
{
    public const TYPE_ANNUAL = 'annual';

    public const TYPE_CASUAL = 'casual';

    public const TYPE_SICK = 'sick';

    public const TYPE_EXCEPTIONAL = 'exceptional';

    /** جديد (قيد الاعتماد) */
    public const STATUS_NEW = 'new';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_REJECTED = 'rejected';

    protected $fillable = [
        'user_id',
        'employee_id',
        'leave_type',
        'start_date',
        'end_date',
        'days_count',
        'reason',
        'status',
        'attachments',
        'approved_by',
        'approved_at',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope(new BelongsToTenantContextScope);
    }

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'days_count' => 'integer',
            'attachments' => 'array',
            'approved_at' => 'datetime',
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

    public static function statusLabelAr(string $status): string
    {
        return match ($status) {
            self::STATUS_NEW => 'جديد',
            self::STATUS_APPROVED => 'معتمد',
            self::STATUS_REJECTED => 'مرفوض',
            default => $status,
        };
    }
}
