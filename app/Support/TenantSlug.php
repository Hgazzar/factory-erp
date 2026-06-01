<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\TenantProfile;
use Illuminate\Validation\Rule;

final class TenantSlug
{
    public const PATTERN = '/^[a-z0-9]+(?:-[a-z0-9]+)*$/';

    public static function normalize(string $slug): string
    {
        $slug = strtolower(trim($slug));
        $slug = preg_replace('/[^a-z0-9\-]/', '-', $slug) ?? $slug;
        $slug = trim((string) preg_replace('/-+/', '-', $slug), '-');

        if ($slug === '') {
            throw new \InvalidArgumentException('Slug is required.');
        }

        return $slug;
    }

    /**
     * @return array<int, mixed>
     */
    public static function createRules(): array
    {
        return [
            'required',
            'string',
            'min:2',
            'max:64',
            'regex:'.self::PATTERN,
            Rule::unique('tenant_profiles', 'slug'),
            Rule::unique('tenant_profiles', 'domain'),
        ];
    }

    /**
     * @return array<int, mixed>
     */
    public static function updateRules(int $tenantUserId): array
    {
        return [
            'required',
            'string',
            'min:2',
            'max:64',
            'regex:'.self::PATTERN,
            Rule::unique('tenant_profiles', 'slug')->ignore($tenantUserId, 'tenant_user_id'),
            Rule::unique('tenant_profiles', 'domain')->ignore($tenantUserId, 'tenant_user_id'),
        ];
    }

    public static function isAvailable(string $slug, ?int $exceptTenantUserId = null): bool
    {
        $slug = self::normalize($slug);

        $query = TenantProfile::query()
            ->where(function ($q) use ($slug): void {
                $q->where('slug', $slug)->orWhere('domain', $slug);
            });

        if ($exceptTenantUserId !== null) {
            $query->where('tenant_user_id', '!=', $exceptTenantUserId);
        }

        return ! $query->exists();
    }
}
