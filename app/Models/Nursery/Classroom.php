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
use Illuminate\Database\Eloquent\Relations\HasMany;

#[ScopedBy([BelongsToTenantContextScope::class])]
class Classroom extends Model
{
    use ResolvesRouteBindingForTenant;

    protected $table = 'nursery_classrooms';

    protected $fillable = [
        'user_id',
        'name',
        'capacity',
        'age_groups',
        'teacher_employee_id',
        'accent_color',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'age_groups' => 'array',
            'is_active' => 'boolean',
        ];
    }

    public function tenantUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'teacher_employee_id');
    }

    public function enrollments(): HasMany
    {
        return $this->hasMany(Enrollment::class, 'classroom_id');
    }
}
