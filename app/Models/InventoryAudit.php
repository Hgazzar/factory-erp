<?php

namespace App\Models;

use App\Models\Concerns\ResolvesRouteBindingForTenant;
use App\Models\Scopes\BelongsToAuthenticatedUserScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InventoryAudit extends Model
{
    use ResolvesRouteBindingForTenant;

    protected $fillable = [
        'user_id',
        'audit_number',
        'audit_date',
        'warehouse_id',
        'type',
        'category',
        'status',
        'notes',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope(new BelongsToAuthenticatedUserScope);

        static::creating(function (InventoryAudit $model): void {
            if (! $model->user_id && $model->warehouse_id) {
                $model->user_id = (int) (Warehouse::withoutGlobalScopes()
                    ->where('id', $model->warehouse_id)
                    ->value('user_id') ?? auth()->id() ?? 1);
            }
        });
    }

    protected function casts(): array
    {
        return [
            'audit_date' => 'date',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function lines(): HasMany
    {
        return $this->hasMany(InventoryAuditLine::class, 'inventory_audit_id');
    }

    public function isDraft(): bool
    {
        return $this->status === 'draft';
    }

    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }
}
