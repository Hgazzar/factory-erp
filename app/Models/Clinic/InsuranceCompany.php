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
class InsuranceCompany extends Model
{
    use ResolvesRouteBindingForTenant;

    protected $table = 'clinic_insurance_companies';

    protected $fillable = [
        'user_id',
        'code',
        'name',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function tenantUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function plans(): HasMany
    {
        return $this->hasMany(InsurancePlan::class, 'clinic_insurance_company_id');
    }
}
