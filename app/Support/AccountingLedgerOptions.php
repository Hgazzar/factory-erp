<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\Account;
use Illuminate\Support\Collection;

/**
 * قوائم حسابات الدليل لربط الإعدادات المحاسبية (x-searchable-select).
 */
final class AccountingLedgerOptions
{
    /**
     * @return list<array{value: int, label: string}>
     */
    public static function liabilityLeafAccountsForUser(int $userId): array
    {
        return Account::query()
            ->where('user_id', $userId)
            ->where('type', Account::TYPE_LIABILITY)
            ->whereNotNull('parent_id')
            ->where(function ($q) {
                $q->where('is_active', true)
                    ->orWhereNull('is_active');
            })
            ->orderBy('code')
            ->get(['id', 'code', 'name_ar', 'name_en'])
            ->map(fn (Account $a) => [
                'value' => $a->id,
                'label' => trim($a->code.' — '.($a->name_ar ?: $a->name_en)),
            ])
            ->values()
            ->all();
    }

    /**
     * أصول متداولة — نقدية وما في حكمها (صندوق، بنك، حسابات بنك فرعية).
     *
     * @return list<array{value: int, label: string}>
     */
    public static function cashEquivalentAssetAccountsForUser(int $userId): array
    {
        $bankMainId = Account::withoutGlobalScopes()
            ->where('user_id', $userId)
            ->where('code', DefaultLedgerAccounts::CODE_BANK)
            ->value('id');

        return Account::query()
            ->where('user_id', $userId)
            ->where('type', Account::TYPE_ASSET)
            ->where(function ($q) use ($bankMainId) {
                $q->whereIn('code', [DefaultLedgerAccounts::CODE_CASH, DefaultLedgerAccounts::CODE_BANK]);
                if ($bankMainId) {
                    $q->orWhere('parent_id', (int) $bankMainId);
                }
                $q->orWhere('is_bank', true);
            })
            ->where(function ($q) {
                $q->where('is_active', true)
                    ->orWhereNull('is_active');
            })
            ->orderBy('code')
            ->get(['id', 'code', 'name_ar', 'name_en'])
            ->unique('id')
            ->map(fn (Account $a) => [
                'value' => $a->id,
                'label' => trim($a->code.' — '.($a->name_ar ?: $a->name_en)),
            ])
            ->values()
            ->all();
    }

    /**
     * @return list<array{value: int, label: string}>
     */
    public static function inventoryAssetAccountsForUser(int $userId): array
    {
        return Account::query()
            ->where('user_id', $userId)
            ->where('type', Account::TYPE_ASSET)
            ->whereNotNull('parent_id')
            ->where(function ($q) {
                $q->where('code', 'like', '104%')
                    ->orWhere('code', '1200');
            })
            ->where(function ($q) {
                $q->where('is_active', true)
                    ->orWhereNull('is_active');
            })
            ->orderBy('code')
            ->get(['id', 'code', 'name_ar', 'name_en'])
            ->map(fn (Account $a) => [
                'value' => $a->id,
                'label' => trim($a->code.' — '.($a->name_ar ?: $a->name_en)),
            ])
            ->values()
            ->all();
    }

    /**
     * @return list<array{value: int, label: string}>
     */
    public static function revenueAccountsForUser(int $userId): array
    {
        return Account::query()
            ->where('user_id', $userId)
            ->where('type', Account::TYPE_REVENUE)
            ->where(function ($q) {
                $q->whereNotNull('parent_id')
                    ->orWhere('allow_direct_posting', true);
            })
            ->where(function ($q) {
                $q->where('is_active', true)
                    ->orWhereNull('is_active');
            })
            ->orderBy('code')
            ->get(['id', 'code', 'name_ar', 'name_en'])
            ->map(fn (Account $a) => [
                'value' => $a->id,
                'label' => trim($a->code.' — '.($a->name_ar ?: $a->name_en)),
            ])
            ->values()
            ->all();
    }

    /**
     * @return list<array{value: int, label: string}>
     */
    public static function cogsExpenseAccountsForUser(int $userId): array
    {
        $cogsRootId = Account::withoutGlobalScopes()
            ->where('user_id', $userId)
            ->where('code', DefaultLedgerAccounts::CODE_COGS)
            ->value('id');

        $q = Account::query()
            ->where('user_id', $userId)
            ->where('type', Account::TYPE_EXPENSE)
            ->where(function ($inner) {
                $inner->whereNotNull('parent_id')
                    ->orWhere('allow_direct_posting', true);
            })
            ->where(function ($inner) {
                $inner->where('is_active', true)
                    ->orWhereNull('is_active');
            });

        if ($cogsRootId) {
            $q->where(function ($inner) use ($cogsRootId) {
                $inner->where('id', (int) $cogsRootId)
                    ->orWhere('parent_id', (int) $cogsRootId);
            });
        }

        return $q->orderBy('code')
            ->get(['id', 'code', 'name_ar', 'name_en'])
            ->map(fn (Account $a) => [
                'value' => $a->id,
                'label' => trim($a->code.' — '.($a->name_ar ?: $a->name_en)),
            ])
            ->values()
            ->all();
    }

    /**
     * حسابات أصول (ذمم مدينة) للعملاء الافتراضيين.
     *
     * @return list<array{value: int, label: string}>
     */
    public static function receivableAssetAccountsForUser(int $userId): array
    {
        return self::assetAccountsByCodesOrParent($userId, ['1030'], Account::TYPE_ASSET);
    }

    /**
     * حسابات خصوم (ذمم دائنة) للموردين الافتراضيين.
     *
     * @return list<array{value: int, label: string}>
     */
    public static function payableLiabilityAccountsForUser(int $userId): array
    {
        return self::assetAccountsByCodesOrParent($userId, ['2010'], Account::TYPE_LIABILITY);
    }

    /**
     * @param  list<string>  $preferredCodes
     * @return list<array{value: int, label: string}>
     */
    private static function assetAccountsByCodesOrParent(int $userId, array $preferredCodes, string $type): array
    {
        $base = Account::query()
            ->where('user_id', $userId)
            ->where('type', $type)
            ->where(function ($q) {
                $q->whereNotNull('parent_id')
                    ->orWhere('allow_direct_posting', true);
            })
            ->where(function ($q) {
                $q->where('is_active', true)
                    ->orWhereNull('is_active');
            });

        $preferred = (clone $base)->whereIn('code', $preferredCodes)->orderBy('code')->get();
        if ($preferred->isNotEmpty()) {
            return $preferred->map(fn (Account $a) => [
                'value' => $a->id,
                'label' => trim($a->code.' — '.($a->name_ar ?: $a->name_en)),
            ])->values()->all();
        }

        return (clone $base)->orderBy('code')
            ->get(['id', 'code', 'name_ar', 'name_en'])
            ->map(fn (Account $a) => [
                'value' => $a->id,
                'label' => trim($a->code.' — '.($a->name_ar ?: $a->name_en)),
            ])
            ->values()
            ->all();
    }

    /**
     * مصروفات/إيرادات لخصومات المشتريات والمبيعات.
     *
     * @return list<array{value: int, label: string}>
     */
    public static function expenseAccountsForUser(int $userId): array
    {
        return self::typedLeafAccounts($userId, Account::TYPE_EXPENSE);
    }

    /**
     * @return list<array{value: int, label: string}>
     */
    public static function revenueLeafAccountsForUser(int $userId): array
    {
        return self::typedLeafAccounts($userId, Account::TYPE_REVENUE);
    }

    /**
     * @return list<array{value: int, label: string}>
     */
    private static function typedLeafAccounts(int $userId, string $type): array
    {
        return Account::query()
            ->where('user_id', $userId)
            ->where('type', $type)
            ->where(function ($q) {
                $q->whereNotNull('parent_id')
                    ->orWhere('allow_direct_posting', true);
            })
            ->where(function ($q) {
                $q->where('is_active', true)
                    ->orWhereNull('is_active');
            })
            ->orderBy('code')
            ->get(['id', 'code', 'name_ar', 'name_en'])
            ->map(fn (Account $a) => [
                'value' => $a->id,
                'label' => trim($a->code.' — '.($a->name_ar ?: $a->name_en)),
            ])
            ->values()
            ->all();
    }

    /**
     * @param  Collection<int, Account>  $accounts
     * @return list<array{value: int, label: string}>
     */
    public static function toSelectOptions(Collection $accounts): array
    {
        return $accounts->map(fn (Account $a) => [
            'value' => $a->id,
            'label' => trim($a->code.' — '.($a->name_ar ?: $a->name_en)),
        ])->values()->all();
    }
}
