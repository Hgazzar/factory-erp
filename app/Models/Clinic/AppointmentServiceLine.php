<?php

declare(strict_types=1);

namespace App\Models\Clinic;

use App\Models\Concerns\ResolvesRouteBindingForTenant;
use App\Models\Scopes\BelongsToTenantContextScope;
use Illuminate\Database\Eloquent\Attributes\ScopedBy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[ScopedBy([BelongsToTenantContextScope::class])]
class AppointmentServiceLine extends Model
{
    use ResolvesRouteBindingForTenant;

    protected $table = 'clinic_appointment_service_lines';

    protected $fillable = [
        'user_id',
        'clinic_appointment_id',
        'clinic_service_id',
        'quantity',
        'unit_price',
        'vat_amount',
        'line_total',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'unit_price' => 'float',
            'vat_amount' => 'float',
            'line_total' => 'float',
        ];
    }

    public function appointment(): BelongsTo
    {
        return $this->belongsTo(Appointment::class, 'clinic_appointment_id');
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(ClinicService::class, 'clinic_service_id');
    }
}
