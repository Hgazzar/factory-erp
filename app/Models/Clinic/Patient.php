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
class Patient extends Model
{
    use ResolvesRouteBindingForTenant;

    public const BLOOD_TYPES = [
        'A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-', 'unknown',
    ];

    protected $fillable = [
        'user_id',
        'code',
        'name',
        'phone',
        'national_id',
        'blood_type',
        'medical_history_summary',
        'clinic_insurance_company_id',
        'clinic_insurance_plan_id',
        'insurance_card_number',
        'insurance_expires_at',
        'allergies',
        'chronic_conditions',
    ];

    protected function casts(): array
    {
        return [
            'insurance_expires_at' => 'date',
        ];
    }

    public function tenantUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class, 'patient_id');
    }

    public function prescriptions(): HasMany
    {
        return $this->hasMany(Prescription::class, 'patient_id');
    }

    public function clinicalNotes(): HasMany
    {
        return $this->hasMany(ClinicalNote::class, 'patient_id');
    }

    public function medicalAttachments(): HasMany
    {
        return $this->hasMany(MedicalAttachment::class, 'patient_id');
    }

    public function insuranceCompany(): BelongsTo
    {
        return $this->belongsTo(InsuranceCompany::class, 'clinic_insurance_company_id');
    }

    public function insurancePlan(): BelongsTo
    {
        return $this->belongsTo(InsurancePlan::class, 'clinic_insurance_plan_id');
    }
}
