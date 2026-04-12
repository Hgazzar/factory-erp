<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductionLine extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name_ar',
        'name_en',
        'description',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    /**
     * الماكينات التابعة لخط الإنتاج.
     */
    public function machines(): HasMany
    {
        return $this->hasMany(Machine::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
