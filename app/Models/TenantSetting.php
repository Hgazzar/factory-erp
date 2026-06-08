<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TenantSetting extends Model
{
    protected $fillable = [
        'tenant_user_id',
        'display_name',
        'logo_path',
        'theme_primary_color',
        'theme_secondary_color',
        'nursery_theme_primary_color',
        'nursery_theme_secondary_color',
        'clinic_theme_primary_color',
        'clinic_theme_secondary_color',
        'store_theme_primary_color',
        'store_theme_secondary_color',
    ];

    public function tenantUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'tenant_user_id');
    }

    public static function forTenant(int $tenantUserId): self
    {
        if ($tenantUserId < 1) {
            throw new \InvalidArgumentException('معرّف المستأجر غير صالح.');
        }

        return static::query()->firstOrCreate(['tenant_user_id' => $tenantUserId]);
    }
}
