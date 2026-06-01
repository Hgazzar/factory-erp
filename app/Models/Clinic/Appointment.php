<?php

declare(strict_types=1);

namespace App\Models\Clinic;

use App\Models\Concerns\ResolvesRouteBindingForTenant;
use App\Models\Employee;
use App\Models\JournalEntry;
use App\Models\Scopes\BelongsToTenantContextScope;
use App\Models\User;
use Illuminate\Database\Eloquent\Attributes\ScopedBy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[ScopedBy([BelongsToTenantContextScope::class])]
class Appointment extends Model
{
    use ResolvesRouteBindingForTenant;

    public const STATUS_PENDING = 'pending';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_CANCELLED = 'cancelled';

    public const SOURCE_STAFF = 'staff';

    public const SOURCE_PORTAL = 'portal';

    protected $table = 'clinic_appointments';

    protected $fillable = [
        'user_id',
        'appointment_number',
        'patient_id',
        'doctor_employee_id',
        'appointment_date',
        'start_time',
        'end_time',
        'status',
        'booking_source',
        'portal_manage_token',
        'fee_amount',
        'subtotal_amount',
        'vat_amount',
        'payment_method',
        'paid_at',
        'reminder_sent_at',
        'journal_entry_id',
        'notes',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'appointment_date' => 'date',
            'fee_amount' => 'float',
            'subtotal_amount' => 'float',
            'vat_amount' => 'float',
            'paid_at' => 'datetime',
            'reminder_sent_at' => 'datetime',
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

    public function journalEntry(): BelongsTo
    {
        return $this->belongsTo(JournalEntry::class, 'journal_entry_id');
    }

    public function prescription(): HasOne
    {
        return $this->hasOne(Prescription::class, 'clinic_appointment_id');
    }

    public function prescriptions(): HasMany
    {
        return $this->hasMany(Prescription::class, 'clinic_appointment_id');
    }

    public function serviceLines(): HasMany
    {
        return $this->hasMany(AppointmentServiceLine::class, 'clinic_appointment_id');
    }

    public function clinicalNotes(): HasMany
    {
        return $this->hasMany(ClinicalNote::class, 'clinic_appointment_id');
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    public function isCancelled(): bool
    {
        return $this->status === self::STATUS_CANCELLED;
    }

    public function isPaid(): bool
    {
        return $this->paid_at !== null;
    }

    /**
     * @return array<string, string>
     */
    public static function statusLabels(): array
    {
        return [
            self::STATUS_PENDING => 'قيد الانتظار',
            self::STATUS_COMPLETED => 'اكتمل',
            self::STATUS_CANCELLED => 'إلغاء',
        ];
    }
}
