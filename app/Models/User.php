<?php

namespace App\Models;

use App\Support\ErpRoles;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'role',
        'is_technician',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_technician' => 'boolean',
        ];
    }

    public function employee(): HasOne
    {
        return $this->hasOne(Employee::class, 'linked_user_id');
    }

    public function employees(): HasMany
    {
        return $this->hasMany(Employee::class, 'user_id');
    }

    public function companySetting(): HasOne
    {
        return $this->hasOne(CompanySetting::class);
    }

    /**
     * مشغّل المنصة (super_admin) — للتحكم المركزي.
     */
    protected function isSuperAdmin(): Attribute
    {
        return Attribute::get(fn (): bool => ErpRoles::isSuperAdmin($this));
    }

    public function tenantModules(): HasMany
    {
        return $this->hasMany(TenantModule::class, 'tenant_user_id');
    }

    public function tenantProfile(): HasOne
    {
        return $this->hasOne(TenantProfile::class, 'tenant_user_id');
    }

    public function tenantFeatures(): HasMany
    {
        return $this->hasMany(TenantFeature::class, 'tenant_id');
    }

    public function hasModule(string $moduleKey): bool
    {
        return app(\App\Services\Tenant\TenantModuleRegistry::class)
            ->isEnabled($moduleKey, (int) $this->id);
    }

    public function hasFeature(string $key): bool
    {
        return app(\App\Services\Tenant\TenantFeatureRegistry::class)
            ->isEnabled($key, (int) $this->id);
    }

    /**
     * نفس امتيازات مسارات وميزات «أدمن الويب» (يتطابق مع middleware role:admin).
     */
    public function isAdminOrSuperAdmin(): bool
    {
        return ErpRoles::hasFinanceAdminPanelAccess($this);
    }
}
