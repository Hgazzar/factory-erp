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
class AttendanceWeekdaySetting extends Model
{
    use ResolvesRouteBindingForTenant;

    public const SCOPE_CHILDREN = 'children';

    public const SCOPE_STAFF = 'staff';

    protected $table = 'nursery_attendance_weekday_settings';

    protected $fillable = [
        'user_id',
        'scope',
        'weekdays',
    ];

    protected function casts(): array
    {
        return [
            'weekdays' => 'array',
        ];
    }

    public function tenantUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * @return list<int>
     */
    public static function defaultWeekdays(): array
    {
        return [0, 1, 2, 3, 4];
    }
}
