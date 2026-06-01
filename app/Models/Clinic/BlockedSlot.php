<?php

declare(strict_types=1);

namespace App\Models\Clinic;

use App\Models\Concerns\ResolvesRouteBindingForTenant;
use App\Models\Employee;
use App\Models\Scopes\BelongsToTenantContextScope;
use Illuminate\Database\Eloquent\Attributes\ScopedBy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[ScopedBy([BelongsToTenantContextScope::class])]
class BlockedSlot extends Model
{
    use ResolvesRouteBindingForTenant;

    protected $table = 'clinic_blocked_slots';

    protected $fillable = [
        'user_id',
        'doctor_employee_id',
        'blocked_date',
        'start_time',
        'end_time',
        'is_full_day',
        'reason',
    ];

    protected function casts(): array
    {
        return [
            'blocked_date' => 'date',
            'is_full_day' => 'boolean',
        ];
    }

    public function tenantUser(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'user_id');
    }

    public function doctor(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'doctor_employee_id');
    }
}
