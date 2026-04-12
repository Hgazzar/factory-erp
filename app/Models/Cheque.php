<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Cheque extends Model
{
    use HasFactory;

    protected $fillable = [
        'type',
        'cheque_number',
        'bank_name',
        'amount',
        'party_name',
        'beneficiary_name',
        'issue_date',
        'due_date',
        'status',
        'notes',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'issue_date' => 'date',
            'due_date' => 'date',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function getTypeLabelAttribute(): string
    {
        return $this->type === 'incoming' ? 'وارد' : 'صادر';
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'cleared' => 'تم التحصيل/الصرف',
            'bounced' => 'مرتجع',
            'cancelled' => 'ملغي',
            default => 'قيد المتابعة',
        };
    }
}
