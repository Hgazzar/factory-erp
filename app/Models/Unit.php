<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Unit extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name_ar',
        'name_en',
        'symbol',
        'base_unit_id',
        'conversion_factor',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'conversion_factor' => 'decimal:6',
            'is_active' => 'boolean',
        ];
    }

    /**
     * الوحدة الأساسية (للتحويل بين الوحدات).
     */
    public function baseUnit(): BelongsTo
    {
        return $this->belongsTo(Unit::class, 'base_unit_id');
    }

    /**
     * الوحدات الفرعية التي تحوّل لهذه الوحدة.
     */
    public function subUnits(): HasMany
    {
        return $this->hasMany(Unit::class, 'base_unit_id');
    }

    /**
     * الأصناف التي تستخدم هذه الوحدة.
     */
    public function items(): HasMany
    {
        return $this->hasMany(Item::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
