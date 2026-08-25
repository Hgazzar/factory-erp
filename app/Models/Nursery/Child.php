<?php

declare(strict_types=1);

namespace App\Models\Nursery;

use App\Models\Concerns\ResolvesRouteBindingForTenant;
use App\Models\Scopes\BelongsToTenantContextScope;
use App\Models\User;
use App\Traits\HasAttachments;
use Illuminate\Database\Eloquent\Attributes\ScopedBy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[ScopedBy([BelongsToTenantContextScope::class])]
class Child extends Model
{
    use HasAttachments;
    use ResolvesRouteBindingForTenant;

    public const STATUS_ACTIVE = 'active';

    public const STATUS_INACTIVE = 'inactive';

    protected $table = 'nursery_children';

    protected $fillable = [
        'user_id',
        'code',
        'name',
        'date_of_birth',
        'gender',
        'guardian_id',
        'guardian_relationship',
        'allergies',
        'diseases',
        'health_notes',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'date_of_birth' => 'date',
        ];
    }

    public function tenantUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function guardian(): BelongsTo
    {
        return $this->belongsTo(Guardian::class, 'guardian_id');
    }

    public function enrollments(): HasMany
    {
        return $this->hasMany(Enrollment::class, 'child_id');
    }

    public function activeEnrollment(): HasOne
    {
        return $this->hasOne(Enrollment::class, 'child_id')
            ->ofMany(
                ['starts_on' => 'max', 'id' => 'max'],
                function ($query): void {
                    $query->where($query->getModel()->getTable().'.is_active', true);
                }
            );
    }

    public function attendanceLogs(): HasMany
    {
        return $this->hasMany(AttendanceLog::class, 'child_id');
    }

    public function medications(): HasMany
    {
        return $this->hasMany(ChildMedication::class, 'child_id')->orderBy('sort_order');
    }

    public function dailyActivities(): HasMany
    {
        return $this->hasMany(ChildDailyActivity::class, 'child_id');
    }

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }
}
