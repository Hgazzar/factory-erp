<?php

declare(strict_types=1);

namespace App\Models\Clinic;

use App\Models\Concerns\ResolvesRouteBindingForTenant;
use App\Models\Scopes\BelongsToTenantContextScope;
use Illuminate\Database\Eloquent\Attributes\ScopedBy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[ScopedBy([BelongsToTenantContextScope::class])]
class ClinicService extends Model
{
    use ResolvesRouteBindingForTenant;

    protected $fillable = [
        'user_id',
        'code',
        'name',
        'price',
        'vat_inclusive',
        'is_active',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'float',
            'vat_inclusive' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function appointmentLines(): HasMany
    {
        return $this->hasMany(AppointmentServiceLine::class, 'clinic_service_id');
    }
}
