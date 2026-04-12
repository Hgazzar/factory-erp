<?php

namespace App\Models;

use App\Models\Concerns\ResolvesRouteBindingForTenant;
use App\Models\Scopes\BelongsToAuthenticatedUserScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Warehouse extends Model
{
    use HasFactory;
    use ResolvesRouteBindingForTenant;

    protected $fillable = [
        'user_id',
        'code',
        'name_ar',
        'name_en',
        'address',
        'city',
        'manager',
        'phone',
        'description',
        'map_location',
        'is_active',
        'is_default',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope(new BelongsToAuthenticatedUserScope);
    }

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'is_default' => 'boolean',
        ];
    }

    public function scopeDefault($query)
    {
        return $query->where('is_default', true);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * الأصناف في هذا المخزن مع الكميات.
     */
    public function items(): BelongsToMany
    {
        return $this->belongsToMany(Item::class, 'item_warehouse')
            ->withPivot(['quantity', 'reserved_quantity'])
            ->withTimestamps();
    }

    /**
     * سجلات الكميات (pivot model عند الحاجة لاستعلامات معقدة).
     */
    public function itemWarehouses(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(ItemWarehouse::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
