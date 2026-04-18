<?php

namespace App\Models;

use App\Models\Scopes\CustomerTenantScope;
use App\Traits\HasAttachments;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
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
        'code',
        'name',
        'name_ar',
        'contact_name',
        'phone',
        'mobile',
        'email',
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
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'credit_limit' => 'decimal:2',
            'payment_terms_days' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::addGlobalScope(new CustomerTenantScope);

        static::saving(function (Customer $customer) {
            if ($customer->isDirty('status')) {
                $customer->is_active = $customer->status === 'active';
            } elseif ($customer->isDirty('is_active')) {
                $customer->status = $customer->is_active ? 'active' : 'inactive';
            }

            if ($customer->isDirty('vat_number')) {
                $customer->tax_number = $customer->vat_number;
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

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
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

    public function getDisplayNameAttribute(): string
    {
        return $this->name_ar ?: $this->name;
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
}
