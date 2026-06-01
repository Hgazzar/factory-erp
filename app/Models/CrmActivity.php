<?php

namespace App\Models;

use App\Models\Scopes\CrmActivityTenantScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CrmActivity extends Model
{
    protected $table = 'crm_activities';

    public const TYPE_PHONE_CALL = 'phone_call';

    public const TYPE_VISIT = 'visit';

    public const TYPE_EMAIL = 'email';

    public const TYPE_WHATSAPP = 'whatsapp';

    public const TYPE_MEETING = 'meeting';

    public const TYPE_OTHER = 'other';

    public const TYPE_APPOINTMENT = 'appointment';

    public const TYPE_SALES_INVOICE = 'sales_invoice';

    protected $fillable = [
        'customer_id',
        'sales_invoice_id',
        'user_id',
        'type',
        'note',
        'result',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope(new CrmActivityTenantScope);
    }

    /**
     * أنواع المتابعة في مودال «تسجيل مكالمة/متابعة» (بدون الموعد).
     *
     * @return array<string, string>
     */
    public static function followUpTypesForModal(): array
    {
        return [
            self::TYPE_PHONE_CALL => 'مكالمة هاتفية',
            self::TYPE_VISIT => 'زيارة',
            self::TYPE_EMAIL => 'بريد إلكتروني',
            self::TYPE_WHATSAPP => 'واتساب',
            self::TYPE_MEETING => 'اجتماع',
            self::TYPE_OTHER => 'أخرى',
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function typeLabels(): array
    {
        return array_merge(self::followUpTypesForModal(), [
            self::TYPE_APPOINTMENT => 'موعد مجدول',
            self::TYPE_SALES_INVOICE => 'فاتورة مبيعات',
        ]);
    }

    public static function labelForType(string $type): string
    {
        return self::typeLabels()[$type] ?? $type;
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function salesInvoice(): BelongsTo
    {
        return $this->belongsTo(SalesInvoice::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
