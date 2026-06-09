<?php

declare(strict_types=1);

namespace App\Models\Fleet;

use App\Models\Concerns\ResolvesRouteBindingForTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\HasApiTokens;

class FleetAgent extends Model
{
    use HasApiTokens;
    use ResolvesRouteBindingForTenant;

    public const STATUS_ACTIVE = 'active';

    public const STATUS_INACTIVE = 'inactive';

    protected $table = 'fleet_agents';

    protected $fillable = [
        'user_id',
        'name',
        'phone',
        'email',
        'employee_id',
        'pos_device_id',
        'status',
        'notes',
        'api_pin_hash',
        'api_last_login_at',
    ];

    protected $hidden = [
        'api_pin_hash',
    ];

    protected function casts(): array
    {
        return [
            'api_last_login_at' => 'datetime',
        ];
    }

    public function assignedCustomers(): HasMany
    {
        return $this->hasMany(FleetCustomer::class, 'assigned_agent_id');
    }

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    public function hasApiAccess(): bool
    {
        return $this->api_pin_hash !== null && trim((string) $this->api_pin_hash) !== '';
    }

    public function setApiPin(string $pin): void
    {
        $this->forceFill(['api_pin_hash' => Hash::make($pin)])->save();
    }

    public function verifyApiPin(string $pin): bool
    {
        $hash = $this->api_pin_hash;

        return is_string($hash) && $hash !== '' && Hash::check($pin, $hash);
    }

    public static function findActiveByPhone(int $tenantUserId, string $phone): ?self
    {
        $phone = trim($phone);
        $normalized = preg_replace('/\D+/', '', $phone) ?? '';

        if ($phone === '' && $normalized === '') {
            return null;
        }

        return static::query()
            ->where('user_id', $tenantUserId)
            ->where('status', self::STATUS_ACTIVE)
            ->where(function ($q) use ($phone, $normalized): void {
                $q->where('phone', $phone);
                if ($normalized !== '') {
                    $q->orWhereRaw(
                        "REPLACE(REPLACE(REPLACE(phone, ' ', ''), '-', ''), '+', '') = ?",
                        [$normalized],
                    );
                }
            })
            ->first();
    }
}
