<?php

namespace App\Models;

use App\Models\Scopes\BelongsToAuthenticatedUserScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InventoryTransaction extends Model
{
    protected $fillable = [
        'user_id',
        'item_id',
        'warehouse_id',
        'quantity',
        'type',
        'reference_id',
        'reference_type',
        'notes',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope(new BelongsToAuthenticatedUserScope);

        static::creating(function (InventoryTransaction $model): void {
            if (! $model->user_id && $model->item_id) {
                $model->user_id = (int) (Item::withoutGlobalScopes()
                    ->where('id', $model->item_id)
                    ->value('user_id') ?? auth()->id() ?? 1);
            }
        });
    }

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:4',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
