<?php

declare(strict_types=1);

namespace App\Models\Clinic;

use App\Models\Concerns\ResolvesRouteBindingForTenant;
use App\Models\Scopes\BelongsToTenantContextScope;
use App\Models\User;
use Illuminate\Database\Eloquent\Attributes\ScopedBy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[ScopedBy([BelongsToTenantContextScope::class])]
class InsurancePlan extends Model
{
    use ResolvesRouteBindingForTenant;

    protected $table = 'clinic_insurance_plans';

    protected $fillable = [
        'user_id',
        'clinic_insurance_company_id',
        'name',
        'copay_percent',
        'max_copay_amount',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'copay_percent' => 'float',
            'max_copay_amount' => 'float',
            'is_active' => 'boolean',
        ];
    }

    public function tenantUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(InsuranceCompany::class, 'clinic_insurance_company_id');
    }

    public function patients(): HasMany
    {
        return $this->hasMany(Patient::class, 'clinic_insurance_plan_id');
    }
}
