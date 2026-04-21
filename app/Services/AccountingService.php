<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Account;
use App\Models\JournalItem;
use Illuminate\Support\Facades\DB;

/**
 * تحديث الرصيد التراكمي (current_balance) لحسابات الدليل بناءً على بنود القيد.
 */
final class AccountingService
{
    /**
     * يُستدعى بعد إنشاء بنود قيد يومية متوازنة لتحديث أرصدة الحسابات.
     */
    public function syncJournalEntryBalances(int $journalEntryId): void
    {
        $lines = JournalItem::withoutGlobalScopes()
            ->where('journal_entry_id', $journalEntryId)
            ->with('account')
            ->get();

        foreach ($lines as $line) {
            $account = $line->account;
            if (! $account) {
                continue;
            }
            $this->applyJournalLineToCurrentBalance(
                $account,
                (float) $line->debit,
                (float) $line->credit
            );
        }
    }

    public function applyJournalLineToCurrentBalance(Account $account, float $debit, float $credit): void
    {
        $delta = $this->postingDeltaByAccountType((string) $account->type, $debit, $credit);
        if (abs($delta) <= 0.0000001) {
            return;
        }

        Account::withoutGlobalScopes()
            ->where('user_id', (int) $account->user_id)
            ->whereKey($account->id)
            ->update([
                'current_balance' => DB::raw('COALESCE(current_balance, 0) + ('.(string) $delta.')'),
            ]);
    }

    private function postingDeltaByAccountType(string $accountType, float $debit, float $credit): float
    {
        $debitMinusCredit = $debit - $credit;

        return match ($accountType) {
            Account::TYPE_ASSET, Account::TYPE_EXPENSE => $debitMinusCredit,
            Account::TYPE_LIABILITY, Account::TYPE_REVENUE, Account::TYPE_EQUITY => -$debitMinusCredit,
            default => $debitMinusCredit,
        };
    }
}
