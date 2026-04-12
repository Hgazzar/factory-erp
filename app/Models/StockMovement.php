<?php

namespace App\Models;

use App\Models\Concerns\ResolvesRouteBindingForTenant;
use App\Models\Scopes\BelongsToAuthenticatedUserScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class StockMovement extends Model
{
    use ResolvesRouteBindingForTenant;

    protected $fillable = [
        'user_id',
        'warehouse_id',
        'item_id',
        'quantity',
        'movement_type',
        'reference_type',
        'reference_id',
        'cost_center_id',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope(new BelongsToAuthenticatedUserScope);

        static::creating(function (StockMovement $model): void {
            if (! $model->user_id && $model->warehouse_id) {
                $model->user_id = (int) (Warehouse::withoutGlobalScopes()
                    ->where('id', $model->warehouse_id)
                    ->value('user_id') ?? auth()->id() ?? 1);
            }
        });
    }

    protected function casts(): array
    {
        return ['quantity' => 'decimal:4'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }

    public function costCenter(): BelongsTo
    {
        return $this->belongsTo(CostCenter::class, 'cost_center_id');
    }

    public function reference(): MorphTo
    {
        return $this->morphTo();
    }

    /** رقم المستند المرجعي (TRF-XXX, ADJ-XXX, AUD-XXX) */
    public function getReferenceNumberAttribute(): ?string
    {
        $ref = $this->reference;
        if (! $ref) {
            return null;
        }

        return $ref->transfer_number ?? $ref->adjustment_number ?? $ref->audit_number ?? null;
    }

    /** رابط فتح المستند الأصلي */
    public function getReferenceUrlAttribute(): ?string
    {
        $ref = $this->reference;
        if (! $ref) {
            return null;
        }
        if ($ref instanceof StockTransfer) {
            return route('inventory.transfers.show', $ref);
        }
        if ($ref instanceof StockAdjustment) {
            return route('inventory.adjustments.show', $ref);
        }
        if ($ref instanceof InventoryAudit) {
            return route('inventory.audits.show', $ref);
        }
        if ($ref instanceof ServicePart) {
            $order = $ref->serviceOrder;

            return $order ? route('services.orders.show', $order) : null;
        }

        return null;
    }
}
