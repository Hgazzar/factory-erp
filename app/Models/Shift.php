<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Shift extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name_ar',
        'name_en',
        'start_time',
        'end_time',
        'is_night',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'start_time' => 'datetime:H:i',
            'end_time' => 'datetime:H:i',
            'is_night' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    /**
     * الورديات الفعلية المرتبطة بهذا القالب.
     */
    public function productionShifts(): HasMany
    {
        return $this->hasMany(ProductionShift::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}

