<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\AuditTrail;
use App\Models\StockMovement;

final class StockMovementObserver
{
    public function created(StockMovement $movement): void
    {
        $this->log('create', $movement, null, $this->auditAttributes($movement));
    }

    public function updated(StockMovement $movement): void
    {
        $old = [];
        $new = [];
        foreach (['quantity', 'movement_type', 'warehouse_id', 'item_id'] as $key) {
            if ($movement->isDirty($key)) {
                $old[$key] = $movement->getOriginal($key);
                $new[$key] = $movement->getAttribute($key);
            }
        }
        if ($old !== []) {
            $this->log('update', $movement, $old, $new);
        }
    }

    public function deleted(StockMovement $movement): void
    {
        $this->log('delete', $movement, $this->auditAttributes($movement), null);
    }

    /**
     * @return array<string, mixed>
     */
    private function auditAttributes(StockMovement $movement): array
    {
        return [
            'quantity' => $movement->quantity,
            'movement_type' => $movement->movement_type,
            'warehouse_id' => $movement->warehouse_id,
            'item_id' => $movement->item_id,
            'reference_type' => $movement->reference_type,
            'reference_id' => $movement->reference_id,
        ];
    }

    private function log(string $action, StockMovement $movement, ?array $oldValues, ?array $newValues): void
    {
        AuditTrail::log($action, 'stock_movements', $movement->id, $oldValues, $newValues);
    }
}
