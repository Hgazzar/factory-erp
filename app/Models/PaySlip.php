<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PaySlip extends Model
{
    public const ITEM_KIND_EARNING = 'earning';

    public const ITEM_KIND_DEDUCTION = 'deduction';

    protected $table = 'pay_slips';

    protected $fillable = [
        'payroll_cycle_id',
        'employee_id',
        'basic_salary',
        'total_allowances',
        'attendance_deductions',
        'statutory_deductions',
        'total_deductions',
        'net_salary',
        'overtime_hours',
        'overtime_amount',
        'absence_hours',
        'late_hours',
    ];

    protected function casts(): array
    {
        return [
            'basic_salary' => 'decimal:2',
            'total_allowances' => 'decimal:2',
            'attendance_deductions' => 'decimal:2',
            'statutory_deductions' => 'decimal:2',
            'total_deductions' => 'decimal:2',
            'net_salary' => 'decimal:2',
            'overtime_hours' => 'decimal:2',
            'overtime_amount' => 'decimal:2',
            'absence_hours' => 'decimal:2',
            'late_hours' => 'decimal:2',
        ];
    }

    public function payrollCycle(): BelongsTo
    {
        return $this->belongsTo(Payroll::class, 'payroll_cycle_id');
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(PayrollItem::class, 'pay_slip_id')->orderBy('sort_order');
    }
}
