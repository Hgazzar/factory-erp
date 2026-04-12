<?php

namespace App\Observers;

use App\Models\AuditTrail;
use App\Models\ProductionRecord;

class ProductionRecordObserver
{
    public function created(ProductionRecord $record): void
    {
        $this->log('create', $record, null, $this->auditAttributes($record));
    }

    public function updated(ProductionRecord $record): void
    {
        $old = [];
        $new = [];
        foreach (['quantity', 'scrap_quantity', 'scrap_reason', 'notes', 'downtime_reason', 'downtime_lost_hours'] as $key) {
            if ($record->isDirty($key)) {
                $old[$key] = $record->getOriginal($key);
                $new[$key] = $record->getAttribute($key);
            }
        }
        if ($old !== []) {
            $this->log('update', $record, $old, $new);
        }
    }

    public function deleted(ProductionRecord $record): void
    {
        $this->log('delete', $record, $this->auditAttributes($record), null);
    }

    private function auditAttributes(ProductionRecord $record): array
    {
        return [
            'quantity' => $record->quantity,
            'scrap_quantity' => $record->scrap_quantity,
            'scrap_reason' => $record->scrap_reason,
            'notes' => $record->notes,
            'downtime_reason' => $record->downtime_reason,
            'downtime_lost_hours' => $record->downtime_lost_hours,
            'recorded_at' => $record->recorded_at?->toIso8601String(),
            'item_id' => $record->item_id,
            'journal_entry_id' => $record->journal_entry_id,
        ];
    }

    private function log(string $action, ProductionRecord $record, ?array $oldValues, ?array $newValues): void
    {
        AuditTrail::log($action, 'production_records', $record->id, $oldValues, $newValues);
    }
}
