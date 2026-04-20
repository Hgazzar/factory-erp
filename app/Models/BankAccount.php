<?php

namespace App\Models;

use App\Models\Concerns\ResolvesRouteBindingForTenant;
use App\Models\Scopes\BelongsToAuthenticatedUserScope;
use App\Support\LedgerAccountBalance;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BankAccount extends Model
{
    use HasFactory;
    use ResolvesRouteBindingForTenant;

    protected $fillable = [
        'user_id',
        'bank_name',
        'branch_name',
        'account_number',
        'iban',
        'currency',
        'ledger_account_id',
        'opening_balance',
        'status',
        'created_by',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope(new BelongsToAuthenticatedUserScope);
    }

    protected function casts(): array
    {
        return [
            'opening_balance' => 'decimal:2',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function ledgerAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'ledger_account_id');
    }

    /**
     * الرصيد المحاسبي: رصيد افتتاحي الحساب في الدليل + حركة القيود (لا يُستخدم عمود opening_balance في bank_accounts).
     */
    public function getCurrentBalanceAttribute(): float
    {
        if ($this->ledger_account_id === null) {
            return 0.0;
        }

        return LedgerAccountBalance::forAccountId((int) $this->ledger_account_id);
    }
}
