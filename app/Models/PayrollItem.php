<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PayrollItem extends Model
{
    public const CODE_BASIC_SALARY = 'basic_salary';

    public const CODE_HOUSING_ALLOWANCE = 'housing_allowance';

    public const CODE_TRANSPORT_ALLOWANCE = 'transport_allowance';

    public const CODE_OTHER_ALLOWANCE = 'other_allowance';

    public const CODE_OVERTIME = 'overtime';

    public const CODE_ATTENDANCE_DEDUCTION = 'attendance_deduction';

    public const CODE_INSURANCE = 'insurance';

    public const CODE_TAX = 'tax';

    protected $fillable = [
        'pay_slip_id',
        'item_code',
        'item_kind',
        'label',
        'amount',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'sort_order' => 'integer',
        ];
    }

    public function paySlip(): BelongsTo
    {
        return $this->belongsTo(PaySlip::class, 'pay_slip_id');
    }
}
