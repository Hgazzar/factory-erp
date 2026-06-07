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
class NurseryShift extends Model
{
    use ResolvesRouteBindingForTenant;

    protected $table = 'nursery_shifts';

    protected $fillable = [
        'user_id',
        'name',
        'start_time',
        'end_time',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'start_time' => 'datetime:H:i',
            'end_time' => 'datetime:H:i',
            'is_active' => 'boolean',
        ];
    }

    public function tenantUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function formattedRange(): string
    {
        $start = $this->start_time?->format('H:i') ?? '';
        $end = $this->end_time?->format('H:i') ?? '';

        return $start !== '' && $end !== '' ? "{$start} – {$end}" : '—';
    }
}
