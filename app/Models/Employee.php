<?php

namespace App\Models;

use App\Models\Concerns\ResolvesRouteBindingForTenant;
use App\Models\Scopes\BelongsToAuthenticatedUserScope;
use App\Traits\HasAttachments;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Employee extends Model
{
    use HasAttachments;
    use HasFactory;
    use ResolvesRouteBindingForTenant;

    protected $fillable = [
        'user_id',
        'linked_user_id',
        'department_id',
        'code',
        'name',
        'email',
        'job_title',
        'position',
        'department',
        'gender',
        'status',
        'base_salary',
        'hired_at',
        'hire_date',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope(new BelongsToAuthenticatedUserScope);
    }

    protected function casts(): array
    {
        return [
            'base_salary' => 'decimal:2',
            'hired_at' => 'date',
            'hire_date' => 'date',
        ];
    }

    /**
     * مالك البيانات (المستأجر) — عزل متعدد.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * حساب تسجيل الدخول المرتبط بالموظف (اختياري).
     */
    public function linkedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'linked_user_id');
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }
}
