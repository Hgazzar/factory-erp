<?php

declare(strict_types=1);

namespace App\Models\Nursery;

use App\Models\Concerns\ResolvesRouteBindingForTenant;
use App\Models\Scopes\BelongsToTenantContextScope;
use App\Models\User;
use App\Support\NurseryChildDailyActivityCatalog;
use Illuminate\Database\Eloquent\Attributes\ScopedBy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[ScopedBy([BelongsToTenantContextScope::class])]
class ChildDailyActivity extends Model
{
    use ResolvesRouteBindingForTenant;

    protected $table = 'nursery_child_daily_activities';

    protected $fillable = [
        'user_id',
        'child_id',
        'activity_date',
        'type',
        'payload',
        'note',
        'is_parent_visible',
        'recorded_by',
        'recorded_at',
    ];

    protected function casts(): array
    {
        return [
            'activity_date' => 'date',
            'payload' => 'array',
            'is_parent_visible' => 'boolean',
            'recorded_at' => 'datetime',
        ];
    }

    public function tenantUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function child(): BelongsTo
    {
        return $this->belongsTo(Child::class, 'child_id');
    }

    public function recorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    public function typeLabel(): string
    {
        return NurseryChildDailyActivityCatalog::label((string) $this->type);
    }

    public function summaryLine(): string
    {
        $payload = is_array($this->payload) ? $this->payload : [];
        $type = (string) $this->type;

        $line = match ($type) {
            NurseryChildDailyActivityCatalog::TYPE_MEAL => implode(' · ', array_filter([
                NurseryChildDailyActivityCatalog::optionLabel($type, 'meal', (string) ($payload['meal'] ?? '')),
                NurseryChildDailyActivityCatalog::optionLabel($type, 'amount', (string) ($payload['amount'] ?? '')),
            ])),
            NurseryChildDailyActivityCatalog::TYPE_NAP => $this->napLine($payload),
            NurseryChildDailyActivityCatalog::TYPE_DIAPER => NurseryChildDailyActivityCatalog::optionLabel(
                $type,
                'change',
                (string) ($payload['change'] ?? ''),
            ),
            NurseryChildDailyActivityCatalog::TYPE_TOILET => NurseryChildDailyActivityCatalog::optionLabel(
                $type,
                'result',
                (string) ($payload['result'] ?? ''),
            ),
            NurseryChildDailyActivityCatalog::TYPE_MOOD => NurseryChildDailyActivityCatalog::optionLabel(
                $type,
                'mood',
                (string) ($payload['mood'] ?? ''),
            ),
            NurseryChildDailyActivityCatalog::TYPE_ACTIVITY => trim((string) ($payload['title'] ?? '')),
            NurseryChildDailyActivityCatalog::TYPE_MEDICATION => $this->medicationLine($payload),
            default => '',
        };

        $note = trim((string) ($this->note ?? ''));
        if ($line === '') {
            return $note !== '' ? $note : $this->typeLabel();
        }

        return $note !== '' ? $line.' — '.$note : $line;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function napLine(array $payload): string
    {
        $start = trim((string) ($payload['started_at'] ?? ''));
        $end = trim((string) ($payload['ended_at'] ?? ''));

        if ($start !== '' && $end !== '') {
            return $start.'–'.$end;
        }

        return $start !== '' ? 'من '.$start : '';
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function medicationLine(array $payload): string
    {
        $name = trim((string) ($payload['medication_name'] ?? ''));
        $status = NurseryChildDailyActivityCatalog::optionLabel(
            NurseryChildDailyActivityCatalog::TYPE_MEDICATION,
            'status',
            (string) ($payload['status'] ?? ''),
        );
        $time = trim((string) ($payload['given_at'] ?? ''));
        $dosage = trim((string) ($payload['dosage'] ?? ''));

        $parts = array_filter([
            $name !== '' ? $name : null,
            $status !== '—' ? $status : null,
            $time !== '' ? 'الساعة '.$time : null,
            $dosage !== '' ? 'الجرعة: '.$dosage : null,
        ]);

        return implode(' · ', $parts);
    }
}
