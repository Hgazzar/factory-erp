<?php

declare(strict_types=1);

namespace App\Models\Nursery;

use App\Models\User;
use App\Services\Tenant\TenantBrandingService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NurserySetting extends Model
{
    protected $table = 'nursery_settings';

    protected $fillable = [
        'user_id',
        'nursery_name',
        'display_name',
        'logo_path',
        'theme_primary_color',
        'theme_secondary_color',
        'contact_phone',
        'contact_email',
        'address',
        'city',
        'region',
        'manager_name',
        'manager_mobile',
        'manager_email',
    ];

    public function tenantUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public static function forTenant(int $tenantUserId): self
    {
        if ($tenantUserId < 1) {
            throw new \InvalidArgumentException('معرّف المستأجر غير صالح.');
        }

        return static::query()->firstOrCreate(
            ['user_id' => $tenantUserId],
            ['nursery_name' => User::query()->whereKey($tenantUserId)->value('name') ?? 'حضانتي'],
        );
    }

    /** الاسم الظاهر في البوابة ولوحة الحضانة. */
    public function portalDisplayName(): string
    {
        return $this->branding()['display_name'];
    }

    public function logoUrl(): ?string
    {
        return $this->branding()['logo_url'];
    }

    /**
     * @return array{display_name: string, logo_url: string|null, nursery_name: string, theme_vars: array<string, string>, theme_primary: string, theme_secondary: string}
     */
    public function branding(): array
    {
        $payload = app(TenantBrandingService::class)->branding((int) $this->user_id, (string) $this->nursery_name);

        return [
            'display_name' => $payload['display_name'],
            'logo_url' => $payload['logo_url'],
            'nursery_name' => (string) $this->nursery_name,
            'theme_vars' => $payload['theme_vars'],
            'theme_primary' => $payload['theme_primary'],
            'theme_secondary' => $payload['theme_secondary'],
        ];
    }
}
