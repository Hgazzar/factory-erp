<?php

namespace App\Observers;

use App\Models\AuditTrail;
use App\Models\JournalEntry;

class JournalEntryObserver
{
    public function created(JournalEntry $entry): void
    {
        AuditTrail::log('create', 'journal_entries', $entry->id, null, [
            'reference' => $entry->reference,
            'date' => $entry->date?->toDateString(),
            'description' => $entry->description,
            'total' => $entry->total,
        ]);
    }

    public function updated(JournalEntry $entry): void
    {
        $old = [];
        $new = [];
        foreach (['reference', 'date', 'description', 'total'] as $key) {
            if ($entry->isDirty($key)) {
                $old[$key] = $entry->getOriginal($key);
                $new[$key] = $entry->getAttribute($key);
            }
        }
        if ($old !== []) {
            AuditTrail::log('update', 'journal_entries', $entry->id, $old, $new);
        }
    }

    public function deleted(JournalEntry $entry): void
    {
        AuditTrail::log('delete', 'journal_entries', $entry->id, [
            'reference' => $entry->reference,
            'date' => $entry->date?->toDateString(),
            'description' => $entry->description,
            'total' => $entry->total,
        ], null);
    }
}
