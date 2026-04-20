<?php

namespace App\Models;

use App\Models\Concerns\ResolvesRouteBindingForTenant;
use App\Models\Scopes\BelongsToAuthenticatedUserScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

class Account extends Model
{
    use HasFactory;
    use ResolvesRouteBindingForTenant;

    public const TYPE_ASSET = 'asset';

    public const TYPE_LIABILITY = 'liability';

    public const TYPE_EXPENSE = 'expense';

    public const TYPE_REVENUE = 'revenue';

    public const TYPE_EQUITY = 'equity';

    protected $fillable = [
        'user_id',
        'code',
        'name_ar',
        'name_en',
        'type',
        'parent_id',
        'opening_balance',
        'current_balance',
        'is_bank',
        'is_active',
        'allow_direct_posting',
    ];

    protected function casts(): array
    {
        return [
            'opening_balance' => 'decimal:4',
            'current_balance' => 'decimal:4',
            'is_bank' => 'boolean',
            'is_active' => 'boolean',
            'allow_direct_posting' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::addGlobalScope(new BelongsToAuthenticatedUserScope);
    }

    /**
     * تسلسل رقمي لكل مستخدم ضمن نفس الأب (مثال: 1، 11، 1101 حسب شجرة الآباء).
     */
    public static function generateNextNumericCodeForUser(int $userId, ?int $parentId): string
    {
        $q = static::withoutGlobalScopes()->where('user_id', $userId);
        if ($parentId === null) {
            $q->whereNull('parent_id');
        } else {
            $q->where('parent_id', $parentId);
        }

        $max = 0;
        foreach ($q->pluck('code') as $code) {
            if (preg_match('/^\d+$/', (string) $code)) {
                $max = max($max, (int) $code);
            }
        }

        return (string) ($max + 1);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(Account::class, 'parent_id')->orderBy('code');
    }

    public function childrenRecursive(): HasMany
    {
        return $this->children()->with('childrenRecursive');
    }

    /**
     * Arabic breadcrumb for selects (root › … › leaf), using an in-memory id map.
     */
    public function filterHierarchyLabel(Collection $accountsById): string
    {
        $parts = [];
        $node = $this;
        for ($i = 0; $i < 64 && $node !== null; $i++) {
            array_unshift($parts, $node->name_ar);
            $node = $node->parent_id ? $accountsById->get($node->parent_id) : null;
        }

        return implode(' › ', $parts);
    }
}
