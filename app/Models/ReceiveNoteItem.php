<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReceiveNoteItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'receive_note_id',
        'item_id',
        'description',
        'quantity',
        'unit',
        'quantity_required',
        'quantity_accepted',
        'quantity_rejected',
        'unit_cost',
        'line_cost',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:4',
            'quantity_required' => 'decimal:4',
            'quantity_accepted' => 'decimal:4',
            'quantity_rejected' => 'decimal:4',
            'unit_cost' => 'decimal:4',
            'line_cost' => 'decimal:4',
        ];
    }

    public function receiveNote(): BelongsTo
    {
        return $this->belongsTo(ReceiveNote::class);
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }

    public function getDisplayNameAttribute(): string
    {
        if ($this->item_id && $this->item) {
            return $this->item->name_ar ?: $this->item->name_en ?: $this->item->code;
        }
        return $this->description ?: '—';
    }
}
