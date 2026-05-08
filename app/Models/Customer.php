<?php

namespace App\Models;

use App\Models\Scopes\CustomerTenantScope;
use App\Traits\HasAttachments;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Customer extends Model
{
    use HasAttachments;
    use HasFactory;
    use SoftDeletes;

    protected $appends = [
        'display_name',
    ];

    protected $fillable = [
        'user_id',
        'assigned_user_id',
        'code',
        'name',
        'first_name',
        'last_name',
        'name_ar',
        'contact_name',
        'company_name',
        'job_title',
        'phone',
        'mobile',
        'email',
        'website',
        'tax_number',
        'vat_number',
        'credit_limit',
        'payment_terms_days',
        'address',
        'country',
        'city',
        'region',
        'postal_code',
        'is_active',
        'status',
        'crm_status',
        'source',
        'source_details',
        'lead_number',
        'lead_priority',
        'lead_rating',
        'lead_sector',
        'lead_company_size',
        'lead_budget',
        'lead_description',
        'lead_requirements',
        'converted_at',
        'crm_last_activity_at',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'credit_limit' => 'decimal:2',
            'payment_terms_days' => 'integer',
            'converted_at' => 'datetime',
            'crm_last_activity_at' => 'datetime',
            'lead_rating' => 'integer',
            'lead_budget' => 'decimal:2',
        ];
    }

    protected static function booted(): void
    {
        static::addGlobalScope(new CustomerTenantScope);

        static::creating(function (Customer $customer) {
            if ($customer->crm_status === 'potential' && empty($customer->lead_number)) {
                $customer->lead_number = static::generateNextLeadNumberForUser((int) $customer->user_id);
            }
        });

        static::saving(function (Customer $customer) {
            if ($customer->crm_status === 'potential' && empty($customer->lead_number)) {
                $customer->lead_number = static::generateNextLeadNumberForUser((int) $customer->user_id);
            }

            if ($customer->isDirty('crm_status')) {
                $prev = $customer->getOriginal('crm_status');
                if ($customer->crm_status === 'active' && in_array($prev, ['potential', 'interested'], true)) {
                    $customer->converted_at = now();
                }
            }

            if ($customer->isDirty('status')) {
                $customer->is_active = $customer->status === 'active';
            } elseif ($customer->isDirty('is_active')) {
                $customer->status = $customer->is_active ? 'active' : 'inactive';
            }

            if ($customer->isDirty('vat_number')) {
                $customer->tax_number = $customer->vat_number;
            }

            $fn = trim((string) ($customer->first_name ?? ''));
            $ln = trim((string) ($customer->last_name ?? ''));
            if ($fn !== '' || $ln !== '') {
                $customer->name = trim($fn.' '.$ln);
            }
        });
    }

    /**
     * @param  mixed  $value
     */
    public function resolveRouteBinding($value, $field = null): ?Model
    {
        $field = $field ?: $this->getRouteKeyName();

        $customer = static::withoutGlobalScopes()
            ->where($field, $value)
            ->first();

        if (! $customer) {
            abort(404);
        }

        if (! auth()->check()) {
            abort(403);
        }

        if ((int) $customer->user_id !== (int) auth()->id()) {
            abort(403);
        }

        return $customer;
    }

    /**
     * استعلام معزول صراحةً بالمستأجر (مفيد للوحدات ومهام الخلفية دون الاعتماد على الجلسة).
     *
     * @return \Illuminate\Database\Eloquent\Builder<static>
     */
    public static function forTenant(int $userId): \Illuminate\Database\Eloquent\Builder
    {
        return static::withoutGlobalScopes()->where('customers.user_id', $userId);
    }

    /**
     * تمهيد لدور sales_agent لاحقاً: يقيّد بالعملاء المسندين للمستخدم عند تفعيل الدور.
     *
     * @return \Illuminate\Database\Eloquent\Builder<static>
     */
    public static function queryForCrmUser(User $user): \Illuminate\Database\Eloquent\Builder
    {
        $q = static::forTenant((int) $user->id);

        if ($user->role === 'sales_agent') {
            $q->where('customers.assigned_user_id', $user->id);
        }

        return $q;
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function assignedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_user_id');
    }

    public function crmAppointments(): HasMany
    {
        return $this->hasMany(CrmAppointment::class, 'customer_id');
    }

    public function crmActivities(): HasMany
    {
        return $this->hasMany(CrmActivity::class, 'customer_id')->latest();
    }

    /**
     * أعلى رقم تسلسلي بعد بادئة CUST- لهذا المستخدم، ثم زيادة واحدة (CUST-0001).
     */
    public static function generateNextCodeForUser(int $userId): string
    {
        $codes = static::withoutGlobalScopes()
            ->where('user_id', $userId)
            ->where('code', 'like', 'CUST-%')
            ->pluck('code');

        $max = 0;
        foreach ($codes as $c) {
            if (preg_match('/^CUST-(\d+)$/i', (string) $c, $m)) {
                $max = max($max, (int) $m[1]);
            }
        }

        $next = $max + 1;
        if ($next < 1) {
            $next = 1;
        }

        do {
            $code = 'CUST-'.str_pad((string) $next, 4, '0', STR_PAD_LEFT);
            $exists = static::withoutGlobalScopes()
                ->where('user_id', $userId)
                ->where('code', $code)
                ->exists();
            $next++;
        } while ($exists);

        return $code;
    }

    /** رقم عميل محتمل فريد لكل مستأجر (LEAD-0001). */
    public static function generateNextLeadNumberForUser(int $userId): string
    {
        $nums = static::withoutGlobalScopes()
            ->where('user_id', $userId)
            ->whereNotNull('lead_number')
            ->where('lead_number', 'like', 'LEAD-%')
            ->pluck('lead_number');

        $max = 0;
        foreach ($nums as $ln) {
            if (preg_match('/^LEAD-(\d+)$/i', (string) $ln, $m)) {
                $max = max($max, (int) $m[1]);
            }
        }

        $next = $max + 1;
        if ($next < 1) {
            $next = 1;
        }

        do {
            $candidate = 'LEAD-'.str_pad((string) $next, 4, '0', STR_PAD_LEFT);
            $exists = static::withoutGlobalScopes()
                ->where('user_id', $userId)
                ->where('lead_number', $candidate)
                ->exists();
            $next++;
        } while ($exists);

        return $candidate;
    }

    public function getDisplayNameAttribute(): string
    {
        if (! empty($this->name_ar)) {
            return (string) $this->name_ar;
        }

        $combined = trim(((string) ($this->first_name ?? '')).' '.((string) ($this->last_name ?? '')));
        if ($combined !== '') {
            return $combined;
        }

        return (string) ($this->name ?? '');
    }

    /** تسمية عرض لمصدر العميل المحتمل (مفتاح مخزّن أو نص حر قديم). */
    public static function labelForLeadSource(?string $value): string
    {
        if ($value === null || $value === '') {
            return '—';
        }
        foreach (config('crm_lead_form.sources', []) as $row) {
            if (($row['value'] ?? '') === $value) {
                return (string) ($row['label'] ?? $value);
            }
        }

        return $value;
    }

    public static function labelForLeadSector(?string $value): string
    {
        if ($value === null || $value === '') {
            return '—';
        }
        foreach (config('crm_lead_form.sectors', []) as $row) {
            if (($row['value'] ?? '') === $value) {
                return (string) ($row['label'] ?? $value);
            }
        }

        return $value;
    }

    public static function labelForLeadCompanySize(?string $value): string
    {
        if ($value === null || $value === '') {
            return '—';
        }
        foreach (config('crm_lead_form.company_sizes', []) as $row) {
            if (($row['value'] ?? '') === $value) {
                return (string) ($row['label'] ?? $value);
            }
        }

        return $value;
    }

    /** تسمية عرض لحالة مسار CRM للعميل. */
    public static function labelForCrmStatus(?string $value): string
    {
        if ($value === null || $value === '') {
            return '—';
        }

        return match ($value) {
            'potential' => 'محتمل',
            'interested' => 'مهتم',
            'active' => 'نشط',
            'not_interested' => 'غير مهتم',
            default => $value,
        };
    }

    /** نوع سجل جهة الاتصال في القوائم: شركة أم شخص وفق اسم الشركة. */
    public static function contactRecordTypeLabel(?string $companyName): string
    {
        return filled(trim((string) $companyName)) ? 'شركة' : 'شخص';
    }

    /** عنوان مختصر (مدينة، منطقة، دولة) لعرض الموقع في جداول CRM. */
    public function crmShortLocation(): string
    {
        $parts = collect([$this->city, $this->region, $this->country])
            ->filter(static fn ($s) => $s !== null && trim((string) $s) !== '')
            ->unique()
            ->values();

        return $parts->isNotEmpty() ? $parts->implode('، ') : '—';
    }

    public function salesInvoices(): HasMany
    {
        return $this->hasMany(SalesInvoice::class);
    }

    public function salesReturns(): HasMany
    {
        return $this->hasMany(SalesReturn::class);
    }

    public function contracts(): HasMany
    {
        return $this->hasMany(Contract::class);
    }

    public function crmSegments(): BelongsToMany
    {
        return $this->belongsToMany(CrmSegment::class, 'crm_segment_customer', 'customer_id', 'segment_id')
            ->withTimestamps();
    }

    public function crmLoyaltyAccounts(): HasMany
    {
        return $this->hasMany(CrmLoyaltyAccount::class, 'customer_id');
    }

    public function crmCustomerMemberships(): HasMany
    {
        return $this->hasMany(CrmCustomerMembership::class, 'customer_id');
    }

    public function currentMembership(): HasOne
    {
        return $this->hasOne(CrmCustomerMembership::class, 'customer_id')->latestOfMany();
    }
}
