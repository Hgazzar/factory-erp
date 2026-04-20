<?php

namespace App\Models;

use App\Models\Concerns\ResolvesRouteBindingForTenant;
use App\Models\Scopes\BelongsToAuthenticatedUserScope;
use App\Traits\HasAttachments;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Payment extends Model
{
    use HasAttachments;
    use HasFactory;
    use ResolvesRouteBindingForTenant;

    protected $fillable = [
        'user_id',
        'supplier_id',
        'expense_account_id',
        'category_id',
        'expense_category_id',
        'cost_center_id',
        'expense_number',
        'date',
        'reference',
        'amount',
        'tax_amount',
        'total_amount',
        'status',
        'notes',
        'type',
        'payment_method',
        'bank_account_id',
        'fixed_asset_id',
        'journal_entry_id',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'amount' => 'decimal:4',
            'tax_amount' => 'decimal:4',
            'total_amount' => 'decimal:4',
        ];
    }

    protected static function booted(): void
    {
        static::addGlobalScope(new BelongsToAuthenticatedUserScope);
    }

    /**
     * Expense payments that count toward accounting totals (posted, or legacy rows with a journal link).
     */
    public function scopeAccountingPostedExpenses(Builder $query): Builder
    {
        return $query->where('type', 'expense')
            ->where(function (Builder $q): void {
                $q->where('status', 'posted')
                    ->orWhereNotNull('journal_entry_id');
            });
    }

    public static function generateNextExpenseNumberForUser(int $userId): string
    {
        $nums = static::withoutGlobalScopes()
            ->where('user_id', $userId)
            ->where('type', 'expense')
            ->whereNotNull('expense_number')
            ->where('expense_number', 'like', 'EXP-%')
            ->pluck('expense_number');

        $max = 0;
        foreach ($nums as $n) {
            if (preg_match('/^EXP-(\d+)$/i', (string) $n, $m)) {
                $max = max($max, (int) $m[1]);
            }
        }

        $next = $max + 1;
        do {
            $code = 'EXP-'.str_pad((string) $next, 4, '0', STR_PAD_LEFT);
            $exists = static::withoutGlobalScopes()
                ->where('user_id', $userId)
                ->where('expense_number', $code)
                ->exists();
            $next++;
        } while ($exists);

        return $code;
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function bankAccount(): BelongsTo
    {
        return $this->belongsTo(BankAccount::class, 'bank_account_id');
    }

    public function fixedAsset(): BelongsTo
    {
        return $this->belongsTo(FixedAsset::class, 'fixed_asset_id');
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function expenseAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'expense_account_id');
    }

    public function expenseCategory(): BelongsTo
    {
        return $this->belongsTo(ExpenseCategory::class, 'expense_category_id');
    }

    public function costCenter(): BelongsTo
    {
        return $this->belongsTo(CostCenter::class, 'cost_center_id');
    }

    public function journalEntry(): BelongsTo
    {
        return $this->belongsTo(JournalEntry::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * فواتير المشتريات المرتبطة بهذا السند (مع مبلغ التخصيص).
     */
    public function purchaseInvoices(): BelongsToMany
    {
        return $this->belongsToMany(PurchaseInvoice::class, 'purchase_payment_invoices')
            ->withPivot('amount')
            ->withTimestamps();
    }
}
