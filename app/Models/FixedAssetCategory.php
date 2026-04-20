<?php

namespace App\Models;

use App\Models\Concerns\ResolvesRouteBindingForTenant;
use App\Models\Scopes\BelongsToAuthenticatedUserScope;
use App\Support\DefaultLedgerAccounts;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FixedAssetCategory extends Model
{
    use HasFactory;
    use ResolvesRouteBindingForTenant;

    protected $fillable = [
        'user_id',
        'code',
        'name_ar',
        'name_en',
        'ledger_asset_account_id',
        'ledger_depreciation_cost_account_id',
        'ledger_accumulated_depreciation_account_id',
        'status',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope(new BelongsToAuthenticatedUserScope);
    }

    public static function generateNextCodeForUser(int $userId): string
    {
        $codes = static::withoutGlobalScopes()
            ->where('user_id', $userId)
            ->where('code', 'like', 'FACAT-%')
            ->pluck('code');

        $max = 0;
        foreach ($codes as $c) {
            if (preg_match('/^FACAT-(\d+)$/i', (string) $c, $m)) {
                $max = max($max, (int) $m[1]);
            }
        }

        $next = $max + 1;
        do {
            $code = 'FACAT-'.str_pad((string) $next, 4, '0', STR_PAD_LEFT);
            $exists = static::withoutGlobalScopes()
                ->where('user_id', $userId)
                ->where('code', $code)
                ->exists();
            $next++;
        } while ($exists);

        return $code;
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function ledgerAssetAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'ledger_asset_account_id');
    }

    public function ledgerDepreciationCostAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'ledger_depreciation_cost_account_id');
    }

    public function ledgerAccumulatedDepreciationAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'ledger_accumulated_depreciation_account_id');
    }

    public function fixedAssets(): HasMany
    {
        return $this->hasMany(FixedAsset::class, 'fixed_asset_category_id');
    }

    /**
     * فئة افتراضية مع ربط الدليل (للترحيل التلقائي من مصروفات رأس المال وما شابه).
     */
    public static function ensureDefaultForUser(int $userId): self
    {
        $existing = static::withoutGlobalScopes()
            ->where('user_id', $userId)
            ->where('code', 'FACAT-DEFAULT')
            ->first();

        if ($existing) {
            return $existing;
        }

        $assetAcc = DefaultLedgerAccounts::fixedAssetPostingAccount($userId);
        $depExp = DefaultLedgerAccounts::depreciationExpenseAccount($userId);
        $accDep = DefaultLedgerAccounts::accumulatedDepreciationAccount($userId);

        return static::withoutGlobalScopes()->create([
            'user_id' => $userId,
            'code' => 'FACAT-DEFAULT',
            'name_ar' => 'فئة افتراضية',
            'name_en' => 'Default category',
            'ledger_asset_account_id' => $assetAcc->id,
            'ledger_depreciation_cost_account_id' => $depExp->id,
            'ledger_accumulated_depreciation_account_id' => $accDep->id,
            'status' => 'active',
        ]);
    }
}
