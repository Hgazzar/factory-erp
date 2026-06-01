<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SystemModule extends Model
{
    protected $fillable = [
        'key',
        'name_ar',
        'name_en',
        'description_ar',
        'is_core',
        'niche_tags',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'is_core' => 'boolean',
            'niche_tags' => 'array',
            'sort_order' => 'integer',
        ];
    }

    public function tenantModules(): HasMany
    {
        return $this->hasMany(TenantModule::class);
    }
}
