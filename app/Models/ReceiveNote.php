<?php

namespace App\Models;

use App\Models\Concerns\ResolvesRouteBindingForTenant;
use App\Models\Scopes\BelongsToAuthenticatedUserScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ReceiveNote extends Model
{
    use HasFactory;
    use ResolvesRouteBindingForTenant;

    public const STATUS_COMPLETED = 'completed';
    public const STATUS_PENDING = 'pending';
    public const STATUS_DRAFT = 'draft';

    protected $fillable = [
        'user_id',
        'code',
        'supplier_id',
        'purchase_order_id',
        'warehouse_id',
        'receive_date',
        'reference',
        'supplier_delivery_notice',
        'status',
        'requires_inspection',
        'notes',
        'internal_notes',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope(new BelongsToAuthenticatedUserScope);
    }

    protected function casts(): array
    {
        return [
            'receive_date' => 'date',
            'requires_inspection' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class, 'purchase_order_id');
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(ReceiveNoteItem::class, 'receive_note_id');
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_COMPLETED => 'مكتمل',
            self::STATUS_PENDING => 'معلق',
            self::STATUS_DRAFT => 'مسودة',
            default => $this->status,
        };
    }
}
