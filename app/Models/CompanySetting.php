<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CompanySetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'tax_number',
        'default_vat_percent',
        'default_receivable_account_id',
        'default_payable_account_id',
        'purchase_discount_ledger_account_id',
        'sales_allowed_discount_ledger_account_id',
        'payroll_wage_expense_account_id',
        'payroll_wages_payable_account_id',
        'payroll_default_payment_account_id',
        'commercial_register',
        'address',
        'logo_url',
        'currency_code',
    ];

    protected $casts = [
        'default_vat_percent' => 'float',
    ];

    /**
     * نسبة الضريبة الافتراضية للواجهات والنماذج: إعداد المنشأة ثم config ثم 15٪.
     */
    public static function resolvedDefaultVatPercent(): float
    {
        $row = static::query()->first();
        if ($row !== null && $row->default_vat_percent !== null) {
            return (float) $row->default_vat_percent;
        }

        return (float) config('accounting.default_vat_percent', 15);
    }

    /**
     * رمز العملة للعرض (مثل SAR، USD). افتراضياً SAR إذا لم يُحفظ في الجدول بعد الترحيل.
     */
    public static function resolvedCurrencyCode(): string
    {
        $row = static::query()->first();
        $code = $row !== null ? trim((string) ($row->currency_code ?? '')) : '';

        return $code !== '' ? mb_strtoupper($code) : 'SAR';
    }
}
