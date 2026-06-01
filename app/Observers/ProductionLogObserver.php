<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\AuditTrail;
use App\Models\ProductionLog;

final class ProductionLogObserver
{
    public function created(ProductionLog $log): void
    {
        $this->log('create', $log, null, $this->auditAttributes($log));
    }

    public function updated(ProductionLog $log): void
    {
        $old = [];
        $new = [];
        foreach (['quantity', 'rejected_quantity', 'scrap_reason', 'notes', 'downtime_reason', 'downtime_lost_hours', 'logged_at'] as $key) {
            if ($log->isDirty($key)) {
                $old[$key] = $log->getOriginal($key);
                $new[$key] = $log->getAttribute($key);
            }
        }
        if ($old !== []) {
            $this->log('update', $log, $old, $new);
        }
    }

    public function deleted(ProductionLog $log): void
    {
        $this->log('delete', $log, $this->auditAttributes($log), null);
    }

    /**
     * @return array<string, mixed>
     */
    private function auditAttributes(ProductionLog $log): array
    {
        return [
            'quantity' => $log->quantity,
            'rejected_quantity' => $log->rejected_quantity,
            'scrap_reason' => $log->scrap_reason,
            'notes' => $log->notes,
            'downtime_reason' => $log->downtime_reason,
            'downtime_lost_hours' => $log->downtime_lost_hours,
            'logged_at' => $log->logged_at?->toIso8601String(),
            'item_id' => $log->item_id,
            'production_shift_id' => $log->production_shift_id,
            'warehouse_id' => $log->warehouse_id,
        ];
    }

    private function log(string $action, ProductionLog $log, ?array $oldValues, ?array $newValues): void
    {
        AuditTrail::log($action, 'production_logs', $log->id, $oldValues, $newValues);
    }
}
