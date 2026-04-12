<?php

namespace App\Models;

use App\Models\Concerns\ResolvesRouteBindingForTenant;
use App\Models\Scopes\BelongsToAuthenticatedUserScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

class CostCenter extends Model
{
    use HasFactory;
    use ResolvesRouteBindingForTenant;

    protected $fillable = [
        'user_id',
        'code',
        'name',
        'branch',
        'parent_id',
        'annual_budget',
        'monthly_budget',
        'status',
        'description',
    ];

    protected function casts(): array
    {
        return [
            'annual_budget' => 'decimal:2',
            'monthly_budget' => 'decimal:2',
        ];
    }

    protected static function booted(): void
    {
        static::addGlobalScope(new BelongsToAuthenticatedUserScope);
    }

    public function getSpentAmountAttribute(): float
    {
        $loadedAmount = $this->getAttribute('expenses_amount_total');
        $loadedTax = $this->getAttribute('expenses_tax_total');

        if ($loadedAmount !== null || $loadedTax !== null) {
            return (float) ($loadedAmount ?? 0) + (float) ($loadedTax ?? 0);
        }

        return (float) $this->expenses()
            ->where('type', 'expense')
            ->sum(DB::raw('amount + COALESCE(tax_amount, 0)'));
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function fixedAssets(): HasMany
    {
        return $this->hasMany(FixedAsset::class, 'cost_center_id');
    }

    public function expenses(): HasMany
    {
        return $this->hasMany(Payment::class, 'cost_center_id');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('code');
    }
}
