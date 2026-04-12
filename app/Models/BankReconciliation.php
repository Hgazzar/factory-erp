<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BankReconciliation extends Model
{
    use HasFactory;

    protected $fillable = [
        'reconciliation_number',
        'account_id',
        'statement_date',
        'statement_balance',
        'book_balance',
        'difference',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'statement_date' => 'date',
            'statement_balance' => 'decimal:2',
            'book_balance' => 'decimal:2',
            'difference' => 'decimal:2',
        ];
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }
}
