<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Budget extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'fiscal_year',
        'start_date',
        'end_date',
        'status',
        'final_snapshot',
        'closed_at',
        'archived_at',
    ];

    protected function casts(): array
    {
        return [
            'fiscal_year' => 'integer',
            'start_date' => 'date',
            'end_date' => 'date',
            'final_snapshot' => 'array',
            'closed_at' => 'datetime',
            'archived_at' => 'datetime',
        ];
    }

    public function items(): HasMany
    {
        return $this->hasMany(BudgetItem::class);
    }
}

