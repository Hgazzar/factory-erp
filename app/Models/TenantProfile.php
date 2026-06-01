<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TenantProfile extends Model
{
    public const STATUS_ACTIVE = 'active';

    public const STATUS_SUSPENDED = 'suspended';

    protected $fillable = [
        'tenant_user_id',
        'niche_key',
        'domain',
        'slug',
        'status',
        'lexicon_overrides',
    ];

    protected function casts(): array
    {
        return [
            'lexicon_overrides' => 'array',
        ];
    }

    public function tenantUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'tenant_user_id');
    }

    public static function forTenantUser(int $tenantUserId): ?self
    {
        if ($tenantUserId < 1) {
            return null;
        }

        return static::query()->where('tenant_user_id', $tenantUserId)->first();
    }

    public static function resolveBySlug(string $slug): ?self
    {
        $slug = strtolower(trim($slug));

        if ($slug === '') {
            return null;
        }

        return static::query()
            ->where('status', self::STATUS_ACTIVE)
            ->where(function ($q) use ($slug): void {
                $q->where('slug', $slug)->orWhere('domain', $slug);
            })
            ->first();
    }
}
