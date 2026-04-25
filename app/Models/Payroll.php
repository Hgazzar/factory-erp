<?php

namespace App\Models;

use App\Models\Scopes\BelongsToAuthenticatedUserScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Payroll extends Model
{
    public const STATUS_DRAFT = 'draft';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_PAID = 'paid';

    protected $table = 'payroll_cycles';

    protected $fillable = [
        'user_id',
        'name',
        'department_id',
        'period_start',
        'period_end',
        'month',
        'year',
        'payment_date',
        'employees_count',
        'status',
        'total_gross',
        'total_deductions',
        'total_amount',
        'notes',
        'accrual_journal_entry_id',
        'payment_journal_entry_id',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope(new BelongsToAuthenticatedUserScope);
    }

    protected function casts(): array
    {
        return [
            'period_start' => 'date',
            'period_end' => 'date',
            'month' => 'integer',
            'year' => 'integer',
            'payment_date' => 'date',
            'employees_count' => 'integer',
            'total_gross' => 'decimal:2',
            'total_deductions' => 'decimal:2',
            'total_amount' => 'decimal:2',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function paySlips(): HasMany
    {
        return $this->hasMany(PaySlip::class, 'payroll_cycle_id');
    }

    public function accrualJournalEntry(): BelongsTo
    {
        return $this->belongsTo(JournalEntry::class, 'accrual_journal_entry_id');
    }

    public function paymentJournalEntry(): BelongsTo
    {
        return $this->belongsTo(JournalEntry::class, 'payment_journal_entry_id');
    }

    public function periodLabelAr(): string
    {
        return sprintf('%02d / %d', $this->month, $this->year);
    }
}
