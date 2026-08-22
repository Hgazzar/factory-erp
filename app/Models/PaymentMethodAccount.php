<?php

namespace App\Models;

use App\Models\Concerns\ResolvesRouteBindingForTenant;
use App\Models\Scopes\BelongsToTenantContextScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentMethodAccount extends Model
{
    use HasFactory;
    use ResolvesRouteBindingForTenant;

    public const KEY_CASH = 'cash';

    public const KEY_TRANSFER = 'transfer';

    public const KEY_CARD = 'card';

    protected $fillable = [
        'user_id',
        'method_key',
        'ledger_account_id',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope(new BelongsToTenantContextScope);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function ledgerAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'ledger_account_id');
    }

    public static function ensureDefaultsForUser(int $userId): void
    {
        $cashId = Account::withoutGlobalScopes()->where('user_id', $userId)->where('code', '1010')->value('id');
        $bankId = Account::withoutGlobalScopes()->where('user_id', $userId)->where('code', '1020')->value('id');
        if (! $cashId || ! $bankId) {
            return;
        }
        foreach ([self::KEY_CASH => (int) $cashId, self::KEY_TRANSFER => (int) $bankId, self::KEY_CARD => (int) $bankId] as $key => $accId) {
            static::withoutGlobalScopes()->firstOrCreate(
                ['user_id' => $userId, 'method_key' => $key],
                ['ledger_account_id' => $accId]
            );
        }
    }

    public static function ledgerAccountIdForMethod(int $userId, string $methodKey): ?int
    {
        $row = static::withoutGlobalScopes()
            ->where('user_id', $userId)
            ->where('method_key', $methodKey)
            ->value('ledger_account_id');

        return $row ? (int) $row : null;
    }
}
