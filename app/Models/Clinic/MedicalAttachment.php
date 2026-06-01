<?php

declare(strict_types=1);

namespace App\Models\Clinic;

use App\Models\Concerns\ResolvesRouteBindingForTenant;
use App\Models\Scopes\BelongsToTenantContextScope;
use App\Models\User;
use Illuminate\Database\Eloquent\Attributes\ScopedBy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[ScopedBy([BelongsToTenantContextScope::class])]
class MedicalAttachment extends Model
{
    use ResolvesRouteBindingForTenant;

    public const CATEGORY_XRAY = 'xray';

    public const CATEGORY_LAB = 'lab';

    public const CATEGORY_IMAGE = 'image';

    public const CATEGORY_MANUAL_PRESCRIPTION = 'manual_prescription';

    public const CATEGORY_OTHER = 'other';

    protected $table = 'clinic_medical_attachments';

    protected $fillable = [
        'user_id',
        'patient_id',
        'clinic_appointment_id',
        'category',
        'original_name',
        'storage_path',
        'mime_type',
        'size_bytes',
        'uploaded_by',
    ];

    protected function casts(): array
    {
        return [
            'size_bytes' => 'integer',
        ];
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class, 'patient_id');
    }

    public function appointment(): BelongsTo
    {
        return $this->belongsTo(Appointment::class, 'clinic_appointment_id');
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function isManualPrescription(): bool
    {
        return $this->category === self::CATEGORY_MANUAL_PRESCRIPTION;
    }

    public function isImage(): bool
    {
        return str_starts_with((string) $this->mime_type, 'image/');
    }

    /**
     * @return array<string, string>
     */
    public static function categoryLabels(): array
    {
        return [
            self::CATEGORY_XRAY => 'أشعة',
            self::CATEGORY_LAB => 'تحاليل',
            self::CATEGORY_IMAGE => 'صورة',
            self::CATEGORY_MANUAL_PRESCRIPTION => 'روشتة يدوية مصورة',
            self::CATEGORY_OTHER => 'أخرى',
        ];
    }
}
