<?php

declare(strict_types=1);

namespace App\Models\Nursery;

use App\Models\Concerns\ResolvesRouteBindingForTenant;
use App\Models\Scopes\BelongsToTenantContextScope;
use App\Models\User;
use Illuminate\Database\Eloquent\Attributes\ScopedBy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[ScopedBy([BelongsToTenantContextScope::class])]
class NurseryOutboundMessage extends Model
{
    use ResolvesRouteBindingForTenant;

    public const TYPE_SUBSCRIPTION_PAID_CONFIRMATION = 'subscription_paid_confirmation';

    public const TYPE_PAYMENT_REMINDER = 'payment_reminder';

    public const TYPE_RENEWAL_REMINDER = 'renewal_reminder';

    public const TYPE_GUARDIAN_OTP = 'guardian_otp';

    public const TYPE_GUARDIAN_INVITE = 'guardian_invite';

    public const STATUS_QUEUED = 'queued';

    public const STATUS_PROCESSING = 'processing';

    public const STATUS_SENT = 'sent';

    public const STATUS_FAILED = 'failed';

    public const STATUS_SKIPPED_CONFIG = 'skipped_config';

    public const RELATED_SUBSCRIPTION = 'subscription';

    public const RELATED_GUARDIAN = 'guardian';

    protected $table = 'nursery_outbound_messages';

    protected $fillable = [
        'user_id',
        'type',
        'dedupe_key',
        'status',
        'attempts',
        'payload',
        'related_type',
        'related_id',
        'provider_message_id',
        'error',
        'queued_at',
        'sent_at',
        'failed_at',
    ];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'attempts' => 'integer',
            'related_id' => 'integer',
            'queued_at' => 'datetime',
            'sent_at' => 'datetime',
            'failed_at' => 'datetime',
        ];
    }

    public function tenantUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function isTerminalSent(): bool
    {
        return $this->status === self::STATUS_SENT;
    }

    public function isInFlight(): bool
    {
        return in_array($this->status, [self::STATUS_QUEUED, self::STATUS_PROCESSING], true);
    }
}
