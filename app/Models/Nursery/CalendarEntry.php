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
class CalendarEntry extends Model
{
    use ResolvesRouteBindingForTenant;

    public const TYPE_LESSON = 'lesson';

    public const TYPE_ACTIVITY = 'activity';

    public const TYPE_ANNOUNCEMENT = 'announcement';

    protected $table = 'nursery_calendar_entries';

    protected $fillable = [
        'user_id',
        'entry_type',
        'title',
        'unit_id',
        'unit_lesson_id',
        'starts_at',
        'ends_at',
        'notes',
        'classroom_ids',
        'child_ids',
        'media_links',
        'is_recurring',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'classroom_ids' => 'array',
            'child_ids' => 'array',
            'media_links' => 'array',
            'is_recurring' => 'boolean',
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function typeLabels(): array
    {
        return [
            self::TYPE_LESSON => 'درس',
            self::TYPE_ACTIVITY => 'نشاط',
            self::TYPE_ANNOUNCEMENT => 'إعلان',
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function typeColors(): array
    {
        return [
            self::TYPE_LESSON => '#069494',
            self::TYPE_ACTIVITY => '#34d399',
            self::TYPE_ANNOUNCEMENT => '#60a5fa',
        ];
    }

    public function tenantUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class, 'unit_id');
    }

    public function unitLesson(): BelongsTo
    {
        return $this->belongsTo(UnitLesson::class, 'unit_lesson_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function displayTitle(): string
    {
        $prefix = self::typeLabels()[$this->entry_type] ?? $this->entry_type;

        return $prefix.' — '.$this->title;
    }
}
