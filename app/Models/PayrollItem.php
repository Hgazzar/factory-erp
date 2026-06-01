<?php

namespace App\Models;

use App\Models\Scopes\BelongsToTenantContextScope;
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
        'user_id',
        'pay_slip_id',
        'item_code',
        'item_kind',
        'label',
        'amount',
        'sort_order',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope(new BelongsToTenantContextScope);

        static::creating(function (PayrollItem $item): void {
            if ($item->user_id === null && $item->pay_slip_id) {
                $item->user_id = PaySlip::withoutGlobalScopes()
                    ->whereKey($item->pay_slip_id)
                    ->value('user_id');
            }
        });
    }

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'sort_order' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function paySlip(): BelongsTo
    {
        return $this->belongsTo(PaySlip::class, 'pay_slip_id');
    }
}
