<?php

declare(strict_types=1);

namespace App\Models\Nursery;

use App\Models\Concerns\ResolvesRouteBindingForTenant;
use App\Models\JournalEntry;
use App\Models\Scopes\BelongsToTenantContextScope;
use App\Models\User;
use Illuminate\Database\Eloquent\Attributes\ScopedBy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[ScopedBy([BelongsToTenantContextScope::class])]
class Subscription extends Model
{
    use ResolvesRouteBindingForTenant;

    /** @deprecated Legacy operational status — still accepted in queries. New rows use unpaid/paid. */
    public const STATUS_ACTIVE = 'active';

    public const STATUS_UNPAID = 'unpaid';

    public const STATUS_PAID = 'paid';

    public const STATUS_CANCELLED = 'cancelled';

    public const STATUS_EXPIRED = 'expired';

    /** @var list<string> */
    public const PAYMENT_METHODS = ['cash', 'transfer', 'card'];

    protected $table = 'nursery_subscriptions';

    protected $fillable = [
        'user_id',
        'child_id',
        'plan_id',
        'starts_on',
        'ends_on',
        'amount_after_tax',
        'discount_amount',
        'notes',
        'is_paid',
        'status',
        'payment_reminder_sent_at',
        'renewal_reminder_sent_at',
        'created_by',
        'journal_entry_id',
        'reversal_journal_entry_id',
        'renewed_from_id',
        'paid_at',
        'payment_method',
    ];

    protected function casts(): array
    {
        return [
            'starts_on' => 'date',
            'ends_on' => 'date',
            'amount_after_tax' => 'decimal:2',
            'discount_amount' => 'decimal:2',
            'is_paid' => 'boolean',
            'payment_reminder_sent_at' => 'datetime',
            'renewal_reminder_sent_at' => 'datetime',
            'paid_at' => 'datetime',
        ];
    }

    public function journalEntry(): BelongsTo
    {
        return $this->belongsTo(JournalEntry::class, 'journal_entry_id');
    }

    public function reversalJournalEntry(): BelongsTo
    {
        return $this->belongsTo(JournalEntry::class, 'reversal_journal_entry_id');
    }

    public function renewedFrom(): BelongsTo
    {
        return $this->belongsTo(self::class, 'renewed_from_id');
    }

    public function renewals(): HasMany
    {
        return $this->hasMany(self::class, 'renewed_from_id');
    }

    public function tenantUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function child(): BelongsTo
    {
        return $this->belongsTo(Child::class, 'child_id');
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(SubscriptionPlan::class, 'plan_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Statuses that remain operational (not cancelled / expired).
     *
     * @return list<string>
     */
    public static function operationalStatuses(): array
    {
        return [
            self::STATUS_ACTIVE,
            self::STATUS_UNPAID,
            self::STATUS_PAID,
        ];
    }

    public function isActive(): bool
    {
        return in_array((string) $this->status, self::operationalStatuses(), true);
    }

    public function isAlreadyPaid(): bool
    {
        return $this->is_paid || $this->status === self::STATUS_PAID;
    }

    public function isCancelled(): bool
    {
        return $this->status === self::STATUS_CANCELLED;
    }

    public function isExpired(): bool
    {
        return $this->status === self::STATUS_EXPIRED;
    }

    public function canBeMarkedPaid(): bool
    {
        return ! $this->isCancelled() && ! $this->isAlreadyPaid();
    }

    public function canBeRenewed(): bool
    {
        return ! $this->isCancelled() && ! $this->hasOpenSuccessor();
    }

    public function hasOpenSuccessor(): bool
    {
        return self::query()
            ->where('renewed_from_id', $this->id)
            ->where('status', '!=', self::STATUS_CANCELLED)
            ->exists();
    }

    public function finalAmount(): float
    {
        return max(0, round((float) $this->amount_after_tax - (float) $this->discount_amount, 2));
    }
}
