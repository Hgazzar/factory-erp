<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Models\Account;
use App\Models\JournalEntry;
use App\Models\JournalItem;
use App\Models\User;

abstract class InventoryAccountingTestCase extends InventoryTestCase
{
    /** @var array{rm: Account, fg: Account, cogs: Account} */
    protected array $ledger;

    protected function setUp(): void
    {
        parent::setUp();

        $this->ledger = $this->seedStandardInventoryAccounts();
    }

    /**
     * @return array{rm: Account, fg: Account, cogs: Account}
     */
    protected function seedStandardInventoryAccounts(?User $user = null): array
    {
        $user ??= $this->tenant;

        return [
            'rm' => Account::factory()->forTenant($user)->asset()->create([
                'code' => (string) config('accounting.raw_materials_inventory_code'),
                'name_ar' => 'مخزون خامات',
            ]),
            'fg' => Account::factory()->forTenant($user)->asset()->create([
                'code' => (string) config('accounting.finished_goods_inventory_code'),
                'name_ar' => 'مخزون منتج تام',
            ]),
            'cogs' => Account::factory()->forTenant($user)->expense()->create([
                'code' => (string) config('accounting.cogs_code'),
                'name_ar' => 'تكلفة البضاعة المباعة',
            ]),
        ];
    }

    protected function journalLineAmount(JournalEntry $entry, Account $account, string $side): float
    {
        $column = $side === 'debit' ? 'debit' : 'credit';

        return (float) JournalItem::withoutGlobalScopes()
            ->where('journal_entry_id', $entry->id)
            ->where('account_id', $account->id)
            ->value($column);
    }

    protected function assertJournalIsBalanced(JournalEntry $entry, float $expectedTotal): void
    {
        $items = JournalItem::withoutGlobalScopes()
            ->where('journal_entry_id', $entry->id)
            ->get();

        $debit = (float) $items->sum('debit');
        $credit = (float) $items->sum('credit');

        $this->assertEqualsWithDelta($expectedTotal, $debit, 0.0001, 'Total debit mismatch');
        $this->assertEqualsWithDelta($expectedTotal, $credit, 0.0001, 'Total credit mismatch');
        $this->assertEqualsWithDelta($expectedTotal, (float) $entry->total, 0.0001, 'Journal header total mismatch');
    }

    protected function assertJournalBelongsToTenant(JournalEntry $entry, User $user): void
    {
        $entryFresh = JournalEntry::withoutGlobalScopes()->findOrFail($entry->id);

        $this->assertSame((int) $user->id, (int) $entryFresh->user_id);

        $foreignAccountIds = Account::withoutGlobalScopes()
            ->where('user_id', '!=', $user->id)
            ->pluck('id');

        $leaked = JournalItem::withoutGlobalScopes()
            ->where('journal_entry_id', $entry->id)
            ->whereIn('account_id', $foreignAccountIds)
            ->exists();

        $this->assertFalse($leaked, 'Journal must not post to another tenant\'s accounts');
    }
}
