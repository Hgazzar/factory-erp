<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Machine extends Model
{
    use HasFactory;

    public const STATUS_ACTIVE = 'active';
    public const STATUS_MAINTENANCE = 'maintenance';
    public const STATUS_INACTIVE = 'inactive';

    protected $fillable = [
        'code',
        'name_ar',
        'name_en',
        'production_line_id',
        'description',
        'status',
        'is_active',
        'depreciation_rate_per_unit',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'depreciation_rate_per_unit' => 'decimal:4',
        ];
    }

    /**
     * خط الإنتاج التابع له الماكينة.
     */
    public function productionLine(): BelongsTo
    {
        return $this->belongsTo(ProductionLine::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
