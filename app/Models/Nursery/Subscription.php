<?php

declare(strict_types=1);

namespace App\Models\Nursery;

use App\Models\Concerns\ResolvesRouteBindingForTenant;
use App\Models\Scopes\BelongsToTenantContextScope;
use App\Models\JournalEntry;
use App\Models\User;
use Illuminate\Database\Eloquent\Attributes\ScopedBy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[ScopedBy([BelongsToTenantContextScope::class])]
class Subscription extends Model
{
    use ResolvesRouteBindingForTenant;

    public const STATUS_ACTIVE = 'active';

    public const STATUS_CANCELLED = 'cancelled';

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

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    public function finalAmount(): float
    {
        return max(0, round((float) $this->amount_after_tax - (float) $this->discount_amount, 2));
    }
}
