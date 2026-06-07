<?php

declare(strict_types=1);

namespace App\Models\Nursery;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChildMedication extends Model
{
    public const FREQ_ONCE_DAILY = 'once_daily';

    public const FREQ_TWICE_DAILY = 'twice_daily';

    public const FREQ_THREE_DAILY = 'three_daily';

    public const FREQ_AS_NEEDED = 'as_needed';

    protected $table = 'nursery_child_medications';

    protected $fillable = [
        'user_id',
        'child_id',
        'name',
        'dosage',
        'frequency',
        'schedule_notes',
        'notes',
        'sort_order',
    ];

    public function tenantUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function child(): BelongsTo
    {
        return $this->belongsTo(Child::class, 'child_id');
    }

    /**
     * @return array<string, string>
     */
    public static function frequencyOptions(): array
    {
        return [
            self::FREQ_ONCE_DAILY => 'مرة يومياً',
            self::FREQ_TWICE_DAILY => 'مرتين يومياً',
            self::FREQ_THREE_DAILY => '3 مرات يومياً',
            self::FREQ_AS_NEEDED => 'عند الحاجة',
        ];
    }

    public function frequencyLabel(): string
    {
        $key = (string) ($this->frequency ?? '');

        return self::frequencyOptions()[$key] ?? ($key !== '' ? $key : '—');
    }
}
