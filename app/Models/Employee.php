<?php

namespace App\Models;

use App\Models\Concerns\ResolvesRouteBindingForTenant;
use App\Models\Scopes\BelongsToTenantContextScope;
use App\Traits\HasAttachments;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Employee extends Model
{
    use HasAttachments;
    use HasFactory;
    use ResolvesRouteBindingForTenant;

    public const SALARY_MONTHLY = 'monthly';

    public const SALARY_WEEKLY = 'weekly';

    public const SALARY_DAILY = 'daily';

    public const ATTENDANCE_POLICY_NONE = 'none';

    public const ATTENDANCE_POLICY_DAY_FOR_DAY = 'day_for_day';

    public const ATTENDANCE_POLICY_HOUR_FOR_HOUR = 'hour_for_hour';

    protected $fillable = [
        'user_id',
        'linked_user_id',
        'department_id',
        'shift_id',
        'nursery_shift_id',
        'cost_center_id',
        'ledger_account_id',
        'code',
        'attendance_device_id',
        'name',
        'first_name',
        'middle_name',
        'last_name',
        'email',
        'personal_email',
        'job_title',
        'position',
        'employment_type',
        'department',
        'gender',
        'birth_date',
        'marital_status',
        'nationality',
        'id_number',
        'passport_number',
        'mobile',
        'phone',
        'address',
        'city',
        'region',
        'postal_code',
        'country',
        'emergency_contact_name',
        'emergency_contact_phone',
        'emergency_contact_relation',
        'status',
        'clinic_role',
        'nursery_role',
        'nursery_job_role',
        'nursery_permissions',
        'nursery_education',
        'nursery_specialization',
        'clinic_specialty_id',
        'base_salary',
        'salary_type',
        'fixed_insurance_deduction',
        'fixed_tax_deduction',
        'attendance_policy',
        'annual_balance',
        'housing_allowance',
        'transport_allowance',
        'other_allowance',
        'bank_name',
        'bank_account_number',
        'iban',
        'social_insurance_number',
        'tax_number',
        'insurance_number',
        'notes',
        'hired_at',
        'hire_date',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope(new BelongsToTenantContextScope);
    }

    /**
     * موظفون يُقتَرَحون كـ«مدير قسم» حسب المسمى الوظيفي (مدير/مشرف…).
     */
    public function scopeDepartmentManagerCandidates(Builder $query): Builder
    {
        $tokens = ['مدير', 'مشرف', 'Manager', 'Supervisor', 'manager', 'supervisor', 'رئيس', 'Head'];

        return $query->where(function (Builder $q) use ($tokens) {
            foreach ($tokens as $t) {
                $q->orWhere('position', 'like', '%'.$t.'%')
                    ->orWhere('job_title', 'like', '%'.$t.'%');
            }
        });
    }

    /**
     * البحث عن موظف المستأجر بـ«كود الموظف» أو «رقم البصمة» (يُفترض أنهما نفس المعرّف عند الافتراض).
     */
    public static function findForAttendanceKey(int $tenantUserId, string $rawKey): ?self
    {
        $key = trim($rawKey);
        if ($key === '') {
            return null;
        }

        return self::withoutGlobalScopes()
            ->where('user_id', $tenantUserId)
            ->where(function (Builder $q) use ($key) {
                $q->where('code', $key)
                    ->orWhere('attendance_device_id', $key);
            })
            ->first();
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    public static function salaryTypeSelectOptions(): array
    {
        return [
            ['value' => self::SALARY_MONTHLY, 'label' => 'شهري'],
            ['value' => self::SALARY_WEEKLY, 'label' => 'أسبوعي'],
            ['value' => self::SALARY_DAILY, 'label' => 'يومي'],
        ];
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    public static function attendancePolicySelectOptions(): array
    {
        return [
            ['value' => self::ATTENDANCE_POLICY_NONE, 'label' => 'بدون سياسة خصم تلقائية'],
            ['value' => self::ATTENDANCE_POLICY_DAY_FOR_DAY, 'label' => 'خصم اليوم بيوم'],
            ['value' => self::ATTENDANCE_POLICY_HOUR_FOR_HOUR, 'label' => 'الساعة بساعة'],
        ];
    }

    protected function casts(): array
    {
        return [
            'base_salary' => 'decimal:2',
            'fixed_insurance_deduction' => 'decimal:2',
            'fixed_tax_deduction' => 'decimal:2',
            'annual_balance' => 'decimal:2',
            'housing_allowance' => 'decimal:2',
            'transport_allowance' => 'decimal:2',
            'other_allowance' => 'decimal:2',
            'hired_at' => 'date',
            'hire_date' => 'date',
            'birth_date' => 'date',
            'nursery_permissions' => 'array',
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

    public function shift(): BelongsTo
    {
        return $this->belongsTo(Shift::class);
    }

    public function nurseryShift(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Nursery\NurseryShift::class, 'nursery_shift_id');
    }

    /**
     * مركز التكلفة الافتراضي لتوزيع تكلفة العمالة والمصروفات المرتبطة بالموظف.
     */
    public function costCenter(): BelongsTo
    {
        return $this->belongsTo(CostCenter::class, 'cost_center_id');
    }

    /**
     * حساب دفتر الأستاذ (شجرة الحسابات) المرتبط بأجور / مستحقات الموظف.
     */
    public function ledgerAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'ledger_account_id');
    }

    /**
     * أوامر إنتاج يسند فيها هذا الموظف كمسؤول/عامل (عند التفعيل في الواجهات).
     */
    public function manufacturingRuns(): HasMany
    {
        return $this->hasMany(ManufacturingRun::class, 'employee_id');
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class);
    }

    public function leaves(): HasMany
    {
        return $this->hasMany(Leave::class);
    }
}
