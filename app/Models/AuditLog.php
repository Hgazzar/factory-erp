<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class AuditLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'actor_id',
        'target_user_id',
        'action',
        'old_role',
        'new_role',
        'logged_at',
        'subject_type',
        'subject_id',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'logged_at' => 'datetime',
            'meta' => 'array',
        ];
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }

    public function targetUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'target_user_id');
    }

    public function subject(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * أحداث وحدات (نقاط بيع، إلخ.) مع الإبقاء على توافق عمود target_user_id غير القابل للفراغ حالياً.
     */
    public static function logModuleEvent(string $action, array $meta = [], ?Model $subject = null): self
    {
        $actorId = auth()->id();
        if (! $actorId) {
            throw new RuntimeException('يجب تسجيل الدخول لتسجيل عملية المراجعة.');
        }

        return static::create([
            'actor_id' => $actorId,
            'target_user_id' => $actorId,
            'action' => $action,
            'old_role' => null,
            'new_role' => null,
            'logged_at' => now(),
            'subject_type' => $subject ? $subject::class : null,
            'subject_id' => $subject?->getKey(),
            'meta' => $meta ?: null,
        ]);
    }

    /**
     * تدقيق إجراءات التحكم المالي (إعادة مسودة، مسح جذري، إلخ.).
     *
     * @param  array<string, mixed>  $meta
     */
    public static function logFinancialControl(string $actionType, int $targetUserId, ?string $subjectType = null, ?int $subjectId = null, array $meta = []): self
    {
        $actorId = auth()->id();
        if (! $actorId) {
            throw new RuntimeException('يجب تسجيل الدخول لتسجيل عملية المراجعة.');
        }

        Log::info('audit_logs.logFinancialControl: قبل الإدراج', [
            'action' => $actionType,
            'actor_id' => (int) $actorId,
            'target_user_id' => $targetUserId,
            'target_equals_actor' => (int) $actorId === $targetUserId,
            'subject_type' => $subjectType,
            'subject_id' => $subjectId,
            'meta' => $meta,
        ]);

        return static::create([
            'actor_id' => $actorId,
            'target_user_id' => $targetUserId,
            'action' => $actionType,
            'old_role' => null,
            'new_role' => null,
            'logged_at' => now(),
            'subject_type' => $subjectType,
            'subject_id' => $subjectId,
            'meta' => $meta ?: null,
        ]);
    }
}

