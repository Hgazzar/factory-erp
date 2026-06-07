<?php

declare(strict_types=1);

namespace App\Models\Nursery;

use App\Models\Concerns\ResolvesRouteBindingForTenant;
use App\Models\Scopes\BelongsToTenantContextScope;
use App\Models\User;
use Illuminate\Database\Eloquent\Attributes\ScopedBy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[ScopedBy([BelongsToTenantContextScope::class])]
class Guardian extends Model
{
    use ResolvesRouteBindingForTenant;

    protected $table = 'nursery_guardians';

    protected $fillable = [
        'user_id',
        'name',
        'phone',
        'phone_alt',
        'national_id',
        'relationship_default',
        'email',
        'address',
        'region',
        'city',
        'notes',
        'portal_access_token',
        'portal_invited_at',
        'portal_last_login_at',
    ];

    protected function casts(): array
    {
        return [
            'portal_invited_at' => 'datetime',
            'portal_last_login_at' => 'datetime',
        ];
    }

    public function tenantUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(Child::class, 'guardian_id');
    }

    public static function findByPortalToken(int $tenantUserId, string $token): ?self
    {
        $token = trim($token);

        if ($tenantUserId < 1 || $token === '') {
            return null;
        }

        return static::query()
            ->withoutGlobalScopes()
            ->where('user_id', $tenantUserId)
            ->where('portal_access_token', $token)
            ->first();
    }
}
