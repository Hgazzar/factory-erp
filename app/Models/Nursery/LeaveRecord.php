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
class LeaveRecord extends Model
{
    use ResolvesRouteBindingForTenant;

    public const SCOPE_CHILDREN = 'children';

    public const SCOPE_STAFF = 'staff';

    protected $table = 'nursery_leave_records';

    protected $fillable = [
        'user_id',
        'scope',
        'child_id',
        'employee_id',
        'name',
        'starts_on',
        'ends_on',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'starts_on' => 'date',
            'ends_on' => 'date',
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

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function coversDate(string $date): bool
    {
        return $this->starts_on->toDateString() <= $date
            && $this->ends_on->toDateString() >= $date;
    }
}
