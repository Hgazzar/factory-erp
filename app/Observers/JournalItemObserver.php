<?php

namespace App\Observers;

use App\Models\AuditTrail;
use App\Models\JournalItem;

class JournalItemObserver
{
    public function created(JournalItem $item): void
    {
        AuditTrail::log('create', 'journal_items', $item->id, null, [
            'journal_entry_id' => $item->journal_entry_id,
            'account_id' => $item->account_id,
            'description' => $item->description,
            'debit' => $item->debit,
            'credit' => $item->credit,
        ]);
    }

    public function updated(JournalItem $item): void
    {
        $old = [];
        $new = [];
        foreach (['debit', 'credit', 'description', 'account_id'] as $key) {
            if ($item->isDirty($key)) {
                $old[$key] = $item->getOriginal($key);
                $new[$key] = $item->getAttribute($key);
            }
        }
        if ($old !== []) {
            AuditTrail::log('update', 'journal_items', $item->id, $old, $new);
        }
    }

    public function deleted(JournalItem $item): void
    {
        AuditTrail::log('delete', 'journal_items', $item->id, [
            'journal_entry_id' => $item->journal_entry_id,
            'account_id' => $item->account_id,
            'debit' => $item->debit,
            'credit' => $item->credit,
        ], null);
    }
}
