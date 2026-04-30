<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CompanySetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
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

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * إعدادات المنشأة لمستأجر واحد (مستخدم ERP الرئيسي).
     */
    public static function forTenant(int $userId): ?self
    {
        if ($userId < 1) {
            return null;
        }

        return static::query()->where('user_id', $userId)->first();
    }

    /**
     * نسبة الضريبة الافتراضية للواجهات والنماذج: إعداد المنشأة ثم config ثم 15٪.
     */
    public static function resolvedDefaultVatPercent(?int $userId = null): float
    {
        $uid = $userId ?? (auth()->check() ? (int) auth()->id() : null);
        if ($uid !== null && $uid > 0) {
            $row = static::forTenant($uid);
            if ($row !== null && $row->default_vat_percent !== null) {
                return (float) $row->default_vat_percent;
            }
        }

        return (float) config('accounting.default_vat_percent', 15);
    }

    /**
     * رمز العملة للعرض (مثل SAR، USD). افتراضياً SAR إذا لم يُحفظ في الجدول بعد الترحيل.
     */
    public static function resolvedCurrencyCode(?int $userId = null): string
    {
        $uid = $userId ?? (auth()->check() ? (int) auth()->id() : null);
        if ($uid !== null && $uid > 0) {
            $row = static::forTenant($uid);
            $code = $row !== null ? trim((string) ($row->currency_code ?? '')) : '';
            if ($code !== '') {
                return mb_strtoupper($code);
            }
        }

        return 'SAR';
    }
}
