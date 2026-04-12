<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DebitNoteItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'debit_note_id',
        'description',
        'quantity',
        'unit_price',
        'tax_percent',
        'line_total',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:4',
            'unit_price' => 'decimal:4',
            'tax_percent' => 'decimal:2',
            'line_total' => 'decimal:4',
        ];
    }

    public function debitNote(): BelongsTo
    {
        return $this->belongsTo(DebitNote::class);
    }
}

