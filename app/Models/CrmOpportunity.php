<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CrmOpportunity extends Model
{
    protected $table = 'crm_opportunities';

    protected $fillable = [
        'user_id',
        'opportunity_number',
        'title',
        'customer_id',
        'stage',
        'description',
        'estimated_value',
        'probability',
        'weighted_value',
        'expected_closing_date',
        'next_follow_up_date',
        'competitor_notes',
        'assigned_user_id',
    ];

    protected function casts(): array
    {
        return [
            'estimated_value' => 'decimal:2',
            'weighted_value' => 'decimal:2',
            'probability' => 'integer',
            'expected_closing_date' => 'date',
            'next_follow_up_date' => 'date',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (CrmOpportunity $opportunity) {
            if (empty($opportunity->opportunity_number)) {
                $opportunity->opportunity_number = static::generateNextOpportunityNumberForUser((int) $opportunity->user_id);
            }
        });

        static::saving(function (CrmOpportunity $opportunity) {
            $est = (float) ($opportunity->estimated_value ?? 0);
            $p = max(0, min(100, (int) ($opportunity->probability ?? 0)));
            $opportunity->probability = $p;
            $opportunity->weighted_value = round($est * $p / 100, 2);
        });
    }

    /** استعلام مقيد بالمستأجر (مالك السجل). */
    public static function forTenant(int $userId): Builder
    {
        return static::where('crm_opportunities.user_id', $userId);
    }

    /**
     * أعلى رقم بعد بادئة OPP- لهذا المستخدم، ثم زيادة واحدة (OPP-0001).
     */
    public static function generateNextOpportunityNumberForUser(int $userId): string
    {
        $nums = static::withoutGlobalScopes()
            ->where('user_id', $userId)
            ->whereNotNull('opportunity_number')
            ->where('opportunity_number', 'like', 'OPP-%')
            ->pluck('opportunity_number');

        $max = 0;
        foreach ($nums as $n) {
            if (preg_match('/^OPP-(\d+)$/i', (string) $n, $m)) {
                $max = max($max, (int) $m[1]);
            }
        }

        $next = $max + 1;
        if ($next < 1) {
            $next = 1;
        }

        do {
            $candidate = 'OPP-'.str_pad((string) $next, 4, '0', STR_PAD_LEFT);
            $exists = static::withoutGlobalScopes()
                ->where('user_id', $userId)
                ->where('opportunity_number', $candidate)
                ->exists();
            $next++;
        } while ($exists);

        return $candidate;
    }

    public static function labelForStage(?string $stage): string
    {
        if ($stage === null || $stage === '') {
            return '—';
        }
        foreach (config('crm_opportunities.stages', []) as $row) {
            if (($row['value'] ?? '') === $stage) {
                return (string) ($row['label'] ?? $stage);
            }
        }

        return $stage;
    }

    /** شارات عرض المرحلة (فئات Tailwind). */
    public static function badgeClassesForStage(?string $stage): string
    {
        return match ($stage) {
            'draft' => 'bg-gray-100 text-gray-800 border border-gray-200',
            'qualification' => 'bg-sky-50 text-sky-900 border border-sky-100',
            'proposal' => 'bg-blue-50 text-blue-900 border border-blue-100',
            'negotiation' => 'bg-amber-50 text-amber-900 border border-amber-100',
            'closed_won' => 'bg-emerald-50 text-emerald-800 border border-emerald-100',
            'closed_lost' => 'bg-red-50 text-red-800 border border-red-100',
            default => 'bg-gray-50 text-gray-600 border border-gray-100',
        };
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function assignedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_user_id');
    }
}
