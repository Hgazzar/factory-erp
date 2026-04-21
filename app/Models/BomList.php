<?php

namespace App\Models;

use App\Models\Concerns\ResolvesRouteBindingForTenant;
use App\Models\Scopes\BelongsToAuthenticatedUserScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BomList extends Model
{
    use ResolvesRouteBindingForTenant;

    public const STATUS_DRAFT = 'draft';

    public const STATUS_ACTIVE = 'active';

    public const STATUS_OBSOLETE = 'obsolete';

    protected $fillable = [
        'user_id',
        'item_id',
        'name',
        'version',
        'status',
        'labor_cost',
        'overhead_cost',
        'header_notes',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope(new BelongsToAuthenticatedUserScope);
    }

    protected function casts(): array
    {
        return [
            'labor_cost' => 'decimal:4',
            'overhead_cost' => 'decimal:4',
        ];
    }

    public function finishedItem(): BelongsTo
    {
        return $this->belongsTo(Item::class, 'item_id');
    }

    public function lines(): HasMany
    {
        return $this->hasMany(BomListLine::class)->orderBy('sort_order')->orderBy('id');
    }

    public static function statusLabels(): array
    {
        return [
            self::STATUS_DRAFT => 'مسودة',
            self::STATUS_ACTIVE => 'نشط',
            self::STATUS_OBSOLETE => 'قديم',
        ];
    }
}
