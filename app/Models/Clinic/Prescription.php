<?php

declare(strict_types=1);

namespace App\Models\Clinic;

use App\Models\Concerns\ResolvesRouteBindingForTenant;
use App\Models\Employee;
use App\Models\Scopes\BelongsToTenantContextScope;
use App\Models\User;
use Illuminate\Database\Eloquent\Attributes\ScopedBy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[ScopedBy([BelongsToTenantContextScope::class])]
class Prescription extends Model
{
    use ResolvesRouteBindingForTenant;

    protected $table = 'clinic_prescriptions';

    protected $fillable = [
        'user_id',
        'patient_id',
        'doctor_employee_id',
        'clinic_appointment_id',
        'diagnosis',
        'medications',
        'prescribed_at',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'medications' => 'array',
            'prescribed_at' => 'datetime',
        ];
    }

    public function tenantUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class, 'patient_id');
    }

    public function doctor(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'doctor_employee_id');
    }

    public function appointment(): BelongsTo
    {
        return $this->belongsTo(Appointment::class, 'clinic_appointment_id');
    }
}
