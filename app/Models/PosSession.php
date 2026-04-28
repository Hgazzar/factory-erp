<?php

namespace App\Models;

use App\Models\Scopes\BelongsToAuthenticatedUserScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PosSession extends Model
{
    public const STATUS_OPEN = 'open';

    public const STATUS_CLOSED = 'closed';

    protected $fillable = [
        'user_id',
        'pos_device_id',
        'employee_id',
        'production_shift_id',
        'opening_balance',
        'closing_balance',
        'status',
        'opened_at',
        'closed_at',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope(new BelongsToAuthenticatedUserScope);
    }

    protected function casts(): array
    {
        return [
            'opening_balance' => 'decimal:4',
            'closing_balance' => 'decimal:4',
            'opened_at' => 'datetime',
            'closed_at' => 'datetime',
        ];
    }

    public function posDevice(): BelongsTo
    {
        return $this->belongsTo(PosDevice::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function productionShift(): BelongsTo
    {
        return $this->belongsTo(ProductionShift::class);
    }

    public function posSales(): HasMany
    {
        return $this->hasMany(PosSale::class);
    }

    public function scopeOpen($query)
    {
        return $query->where('status', self::STATUS_OPEN);
    }
}
