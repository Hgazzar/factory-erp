<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SalesTarget extends Model
{
    protected $fillable = [
        'name',
        'description',
        'target_type',
        'assigned_to_type',
        'assigned_to_id',
        'period',
        'start_date',
        'end_date',
        'target_amount',
        'threshold_amount',
        'stretch_amount',
        'achieved_amount',
        'status',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'target_amount' => 'decimal:4',
            'threshold_amount' => 'decimal:4',
            'stretch_amount' => 'decimal:4',
            'achieved_amount' => 'decimal:4',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function assignedWarehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'assigned_to_id');
    }

    public function assignedCustomer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'assigned_to_id');
    }

    public function getAssignedNameAttribute(): string
    {
        return match ($this->assigned_to_type) {
            'warehouse' => $this->assignedWarehouse?->name_ar ?? 'المخازن',
            'customer' => $this->assignedCustomer?->name ?? 'العملاء',
            default => 'المنشأة',
        };
    }

    public function getCompletionPercentAttribute(): float
    {
        if ($this->target_amount <= 0) {
            return 0;
        }
        return min(150, round(((float) $this->achieved_amount / (float) $this->target_amount) * 100, 2));
    }

    /**
     * إعادة حساب الإنجاز من فواتير المبيعات.
     */
    public function recalculateAchievement(): void
    {
        $query = SalesInvoice::whereBetween('date', [$this->start_date, $this->end_date]);

        if ($this->assigned_to_type === 'warehouse' && $this->assigned_to_id) {
            $query->where('warehouse_id', $this->assigned_to_id);
        } elseif ($this->assigned_to_type === 'customer' && $this->assigned_to_id) {
            $query->where('customer_id', $this->assigned_to_id);
        }

        $amount = (float) $query->sum('total');
        $this->achieved_amount = $amount;

        $today = now()->startOfDay();
        if ($today->gt($this->end_date)) {
            $this->status = 'expired';
        } elseif ($this->completion_percent >= 100) {
            $this->status = 'completed';
        } else {
            $this->status = 'active';
        }

        $this->save();
    }
}
