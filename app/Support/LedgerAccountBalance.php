<?php

namespace App\Support;

use App\Models\Account;
use Illuminate\Support\Facades\DB;

final class LedgerAccountBalance
{
    /**
     * رصيد الحساب = الرصيد الافتتاحي + مجموع (مدين − دائن) من القيود.
     */
    public static function forAccountId(?int $accountId): float
    {
        if ($accountId === null) {
            return 0.0;
        }

        $opening = (float) Account::query()->whereKey($accountId)->value('opening_balance');

        $movement = (float) DB::table('journal_items')
            ->where('journal_items.account_id', $accountId)
            ->sum(DB::raw('journal_items.debit - journal_items.credit'));

        return $opening + $movement;
    }

    public static function forAccountCode(?string $code): float
    {
        if ($code === null || $code === '') {
            return 0.0;
        }

        $id = Account::query()->where('code', $code)->value('id');

        return self::forAccountId($id ? (int) $id : null);
    }

    /**
     * مجموع أرصدة كل الحسابات التي تحمل نفس الرمز عبر جميع المستأجرين (لملخص المشرف).
     */
    public static function sumForAccountCodeAcrossUsers(?string $code): float
    {
        if ($code === null || $code === '') {
            return 0.0;
        }

        $ids = Account::withoutGlobalScopes()->where('code', $code)->pluck('id');
        $sum = 0.0;
        foreach ($ids as $id) {
            $sum += self::forAccountId((int) $id);
        }

        return $sum;
    }
}
