<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CommissionRule extends Model
{
    protected $fillable = [
        'name',
        'description',
        'type',
        'basis',
        'rate_percent',
        'min_amount',
        'max_amount',
        'valid_from',
        'valid_until',
        'priority',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'rate_percent' => 'decimal:2',
            'min_amount' => 'decimal:4',
            'max_amount' => 'decimal:4',
            'valid_from' => 'date',
            'valid_until' => 'date',
        ];
    }

    public function commissions(): HasMany
    {
        return $this->hasMany(Commission::class, 'commission_rule_id');
    }

    /**
     * القاعدة النشطة ذات الأولوية الأعلى (أقل رقم أولوية) السارية في تاريخ معين.
     */
    public static function activeForDate(?string $date = null): ?self
    {
        $date = $date ?? now()->toDateString();

        return self::where('status', 'active')
            ->where(function ($q) use ($date) {
                $q->whereNull('valid_from')->orWhere('valid_from', '<=', $date);
            })
            ->where(function ($q) use ($date) {
                $q->whereNull('valid_until')->orWhere('valid_until', '>=', $date);
            })
            ->orderBy('priority')
            ->first();
    }

    /**
     * حساب مبلغ العمولة من المبلغ الأساسي حسب القاعدة (الحد الأدنى/الأقصى).
     */
    public function computeAmount(float $baseAmount): float
    {
        $amount = round($baseAmount * (float) $this->rate_percent / 100, 4);
        if ($this->min_amount !== null && $amount < (float) $this->min_amount) {
            $amount = (float) $this->min_amount;
        }
        if ($this->max_amount !== null && $amount > (float) $this->max_amount) {
            $amount = (float) $this->max_amount;
        }
        return $amount;
    }
}
