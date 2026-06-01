<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TenantStoreSetting extends Model
{
    protected $fillable = [
        'tenant_user_id',
        'is_store_enabled',
        'hero_title',
        'hero_subtitle',
        'hero_offer_text',
        'about_us',
        'contact_us',
        'faq',
        'shipping_policy',
        'privacy_policy',
        'social_facebook',
        'social_instagram',
        'social_twitter',
        'social_whatsapp',
        'default_pos_device_id',
    ];

    protected function casts(): array
    {
        return [
            'is_store_enabled' => 'boolean',
        ];
    }

    public function tenantUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'tenant_user_id');
    }

    public function defaultPosDevice(): BelongsTo
    {
        return $this->belongsTo(PosDevice::class, 'default_pos_device_id');
    }

    public static function forTenant(int $tenantUserId): self
    {
        if ($tenantUserId < 1) {
            throw new \InvalidArgumentException('معرّف المستأجر غير صالح.');
        }

        return static::query()->firstOrCreate(
            ['tenant_user_id' => $tenantUserId],
            ['is_store_enabled' => true],
        );
    }

    /**
     * @return array<string, string|null>
     */
    public function socialLinks(): array
    {
        return [
            'facebook' => $this->social_facebook,
            'instagram' => $this->social_instagram,
            'twitter' => $this->social_twitter,
            'whatsapp' => $this->social_whatsapp,
        ];
    }
}
