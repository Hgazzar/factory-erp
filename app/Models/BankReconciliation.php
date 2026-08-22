<?php

namespace App\Models;

use App\Models\Scopes\BelongsToTenantContextScope;
use App\Services\Tenant\TenantContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;

class BankReconciliation extends Model
{
    use HasFactory;

    protected $fillable = [
        'reconciliation_number',
        'account_id',
        'statement_date',
        'statement_balance',
        'book_balance',
        'difference',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'statement_date' => 'date',
            'statement_balance' => 'decimal:2',
            'book_balance' => 'decimal:2',
            'difference' => 'decimal:2',
        ];
    }

    protected static function booted(): void
    {
        /**
         * لا عمود user_id على التسويات — العزل عبر الحساب البنكي التابع للمستأجر.
         * لا نستخدم withoutGlobalScopes؛ نفلتر accounts.user_id صراحةً.
         */
        static::addGlobalScope('tenant_via_account', function (Builder $builder): void {
            if (! Auth::check()) {
                return;
            }

            $tenantContext = app(TenantContext::class);
            $tenantUserId = $tenantContext->resolveTenantUserId();

            if ($tenantUserId === null && $tenantContext->isPlatformOperator()) {
                $tenantUserId = (int) Auth::id();
            }

            if ($tenantUserId === null) {
                $builder->whereRaw('0 = 1');

                return;
            }

            $builder->whereIn('account_id', function ($query) use ($tenantUserId): void {
                $query->select('id')
                    ->from('accounts')
                    ->where('user_id', $tenantUserId);
            });
        });
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }
}
