<?php

namespace App\Models;

use App\Models\Scopes\BelongsToAuthenticatedUserScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PosSale extends Model
{
    public const STATUS_COMPLETED = 'completed';

    public const STATUS_PENDING = 'pending';

    /** محجوز للمستقبل — التسليم قبل التحصيل */
    public const STATUS_DELIVERED = 'delivered';

    public const STATUS_COLLECTED = 'collected';

    public const STATUS_PENDING_VERIFICATION = 'pending_verification';

    public const STATUS_VOIDED = 'voided';

    /** alias used in merchant UI — maps to voided */
    public const STATUS_CANCELLED = 'cancelled';

    public const PAYMENT_CASH = 'cash';

    public const PAYMENT_CARD = 'card';

    public const PAYMENT_BANK = 'bank';

    public const PAYMENT_MIXED = 'mixed';

    public const PAYMENT_OTHER = 'other';

    public const PAYMENT_COD = 'cod';

    public const PAYMENT_MANUAL_TRANSFER = 'manual_transfer';

    public const CHANNEL_ONLINE_STORE = 'online_store';

    public const CHANNEL_POS_TERMINAL = 'pos_terminal';

    protected $fillable = [
        'user_id',
        'pos_device_id',
        'pos_session_id',
        'receipt_number',
        'invoice_number',
        'total_price',
        'subtotal_amount',
        'vat_amount',
        'total_amount',
        'cogs_amount',
        'payment_method',
        'sale_channel',
        'customer_name',
        'customer_phone',
        'customer_address',
        'coupon_code',
        'discount_amount',
        'status',
        'delivered_at',
        'whatsapp_delivered_notified_at',
        'whatsapp_invoice_notified_at',
        'journal_entry_id',
        'collection_journal_entry_id',
        'payment_gateway_reference',
        'payment_receipt_path',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope(new BelongsToAuthenticatedUserScope);
    }

    protected function casts(): array
    {
        return [
            'total_price' => 'decimal:4',
            'subtotal_amount' => 'decimal:4',
            'vat_amount' => 'decimal:4',
            'total_amount' => 'decimal:4',
            'cogs_amount' => 'decimal:4',
            'discount_amount' => 'decimal:4',
            'delivered_at' => 'datetime',
            'whatsapp_delivered_notified_at' => 'datetime',
            'whatsapp_invoice_notified_at' => 'datetime',
            'whatsapp_received_notified_at' => 'datetime',
        ];
    }

    public function posDevice(): BelongsTo
    {
        return $this->belongsTo(PosDevice::class);
    }

    public function posSession(): BelongsTo
    {
        return $this->belongsTo(PosSession::class);
    }

    public function journalEntry(): BelongsTo
    {
        return $this->belongsTo(JournalEntry::class);
    }

    public function collectionJournalEntry(): BelongsTo
    {
        return $this->belongsTo(JournalEntry::class, 'collection_journal_entry_id');
    }

    public function lines(): HasMany
    {
        return $this->hasMany(PosSaleLine::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(PosSaleItem::class, 'pos_sale_id');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', self::STATUS_COMPLETED);
    }

    /**
     * @return list<string>
     */
    public static function revenueRecognizedStatuses(): array
    {
        return [self::STATUS_COMPLETED, self::STATUS_COLLECTED, self::STATUS_DELIVERED];
    }

    public function scopeRevenueRecognized($query)
    {
        return $query->whereIn('status', self::revenueRecognizedStatuses());
    }

    public function scopeOnlineStore($query)
    {
        return $query->where('sale_channel', self::CHANNEL_ONLINE_STORE);
    }

    public function scopeForTenant($query, int $tenantUserId)
    {
        return $query->where('user_id', $tenantUserId);
    }

    public function scopeWithOptionalStatus($query, ?string $status)
    {
        $status = trim((string) $status);
        if ($status !== '' && $status !== 'all') {
            $query->where('status', $status);
        }

        return $query;
    }

    /**
     * Orders that still need merchant action (fulfillment, verification, or collection).
     */
    public function scopeAwaitingMerchantAction($query)
    {
        return $query->whereIn('status', self::awaitingMerchantActionStatuses());
    }

    public function scopeCreatedOnDate($query, $date)
    {
        return $query->whereDate('created_at', $date);
    }

    public function scopeCreatedSince($query, $dateTime)
    {
        return $query->where('created_at', '>=', $dateTime);
    }

    /**
     * @return list<string>
     */
    public static function awaitingMerchantActionStatuses(): array
    {
        return [
            self::STATUS_PENDING,
            self::STATUS_PENDING_VERIFICATION,
            self::STATUS_DELIVERED,
        ];
    }

    public function scopePendingOnlineCod($query)
    {
        return $query
            ->where('status', self::STATUS_PENDING)
            ->where('payment_method', self::PAYMENT_COD)
            ->where('sale_channel', self::CHANNEL_ONLINE_STORE);
    }

    public function isOnlineCodPending(): bool
    {
        return $this->status === self::STATUS_PENDING
            && $this->payment_method === self::PAYMENT_COD
            && $this->sale_channel === self::CHANNEL_ONLINE_STORE;
    }

    public function isOnlineStoreOrder(): bool
    {
        return $this->sale_channel === self::CHANNEL_ONLINE_STORE;
    }

    public function isPendingVerification(): bool
    {
        return $this->status === self::STATUS_PENDING_VERIFICATION;
    }

    /**
     * @return array<string, string>
     */
    public static function onlineOrderStatusLabels(): array
    {
        return [
            self::STATUS_PENDING => 'بانتظار التسليم',
            self::STATUS_PENDING_VERIFICATION => 'بانتظار التحقق من التحويل',
            self::STATUS_DELIVERED => 'تم التسليم',
            self::STATUS_COLLECTED => 'تم التحصيل',
            self::STATUS_COMPLETED => 'مكتمل',
            self::STATUS_VOIDED => 'ملغى',
        ];
    }

    /**
     * رقم إيصال فريد لكل مستخدم (POS-YYYYMMDD-####).
     */
    public static function nextReceiptNumber(int $userId): string
    {
        $prefix = 'POS-'.now()->format('Ymd').'-';

        $last = static::withoutGlobalScopes()
            ->where('user_id', $userId)
            ->where('receipt_number', 'like', $prefix.'%')
            ->orderByDesc('receipt_number')
            ->value('receipt_number');

        $seq = 1;
        if ($last && preg_match('/-(\d+)$/', $last, $m)) {
            $seq = (int) $m[1] + 1;
        }

        return $prefix.str_pad((string) $seq, 4, '0', STR_PAD_LEFT);
    }

    public static function nextInvoiceNumber(int $userId): string
    {
        $prefix = 'POS-INV-'.now()->format('Ymd').'-';

        $last = static::withoutGlobalScopes()
            ->where('user_id', $userId)
            ->where('invoice_number', 'like', $prefix.'%')
            ->orderByDesc('invoice_number')
            ->value('invoice_number');

        $seq = 1;
        if (is_string($last) && preg_match('/-(\d+)$/', $last, $m)) {
            $seq = (int) $m[1] + 1;
        }

        return $prefix.str_pad((string) $seq, 4, '0', STR_PAD_LEFT);
    }
}
