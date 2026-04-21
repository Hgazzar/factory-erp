<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ManufacturingRunLine extends Model
{
    protected $fillable = [
        'manufacturing_run_id',
        'bom_list_line_id',
        'ingredient_item_id',
        'warehouse_id',
        'planned_quantity',
        'planned_scrap_percent',
        'actual_scrap_percent',
        'quantity_consumed',
        'unit_cost_at_post',
        'line_cost',
    ];

    protected function casts(): array
    {
        return [
            'planned_quantity' => 'decimal:4',
            'planned_scrap_percent' => 'decimal:4',
            'actual_scrap_percent' => 'decimal:4',
            'quantity_consumed' => 'decimal:4',
            'unit_cost_at_post' => 'decimal:4',
            'line_cost' => 'decimal:4',
        ];
    }

    public function manufacturingRun(): BelongsTo
    {
        return $this->belongsTo(ManufacturingRun::class);
    }

    public function bomListLine(): BelongsTo
    {
        return $this->belongsTo(BomListLine::class);
    }

    public function ingredientItem(): BelongsTo
    {
        return $this->belongsTo(Item::class, 'ingredient_item_id');
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }
}
