<?php

namespace App\Models;

use App\Models\Scopes\BelongsToAuthenticatedUserScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PosSale extends Model
{
    public const STATUS_COMPLETED = 'completed';

    public const STATUS_VOIDED = 'voided';

    public const PAYMENT_CASH = 'cash';

    public const PAYMENT_CARD = 'card';

    public const PAYMENT_BANK = 'bank';

    public const PAYMENT_MIXED = 'mixed';

    public const PAYMENT_OTHER = 'other';

    protected $fillable = [
        'user_id',
        'pos_device_id',
        'pos_session_id',
        'receipt_number',
        'total_price',
        'payment_method',
        'status',
        'journal_entry_id',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope(new BelongsToAuthenticatedUserScope);
    }

    protected function casts(): array
    {
        return [
            'total_price' => 'decimal:4',
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

    public function lines(): HasMany
    {
        return $this->hasMany(PosSaleLine::class);
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', self::STATUS_COMPLETED);
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
}
