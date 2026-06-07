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
class Unit extends Model
{
    use ResolvesRouteBindingForTenant;

    protected $table = 'nursery_units';

    protected $fillable = [
        'user_id',
        'name',
        'age_groups',
        'goals',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'age_groups' => 'array',
            'goals' => 'array',
            'is_active' => 'boolean',
        ];
    }

    public function tenantUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function lessons(): HasMany
    {
        return $this->hasMany(UnitLesson::class, 'unit_id');
    }

    public function activeLessons(): HasMany
    {
        return $this->lessons()->where('is_active', true)->orderBy('name');
    }

    /**
     * @return list<string>
     */
    public function goalLines(): array
    {
        $goals = $this->goals ?? [];

        return array_values(array_filter(
            array_map(fn ($g) => trim((string) $g), is_array($goals) ? $goals : []),
            fn (string $g): bool => $g !== ''
        ));
    }
}
