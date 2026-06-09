<?php

declare(strict_types=1);

namespace App\Models\Fleet;

use App\Models\Concerns\ResolvesRouteBindingForTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FleetCollection extends Model
{
    use ResolvesRouteBindingForTenant;

    public const STATUS_DRAFT = 'draft';

    public const STATUS_CONFIRMED = 'confirmed';

    public const STATUS_VOID = 'void';

    public const PAYMENT_COD = 'cod';

    public const PAYMENT_TRANSFER = 'transfer';

    public const PAYMENT_CREDIT = 'credit';

    protected $table = 'fleet_collections';

    protected $fillable = [
        'user_id',
        'agent_id',
        'customer_id',
        'route_id',
        'route_stop_id',
        'collection_number',
        'collected_on',
        'payment_method',
        'subtotal',
        'status',
        'notes',
        'recorded_by',
        'confirmed_at',
    ];

    protected function casts(): array
    {
        return [
            'collected_on' => 'date',
            'subtotal' => 'float',
            'confirmed_at' => 'datetime',
        ];
    }

    public function agent(): BelongsTo
    {
        return $this->belongsTo(FleetAgent::class, 'agent_id');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(FleetCustomer::class, 'customer_id');
    }

    public function route(): BelongsTo
    {
        return $this->belongsTo(FleetRoute::class, 'route_id');
    }

    public function routeStop(): BelongsTo
    {
        return $this->belongsTo(FleetRouteStop::class, 'route_stop_id');
    }

    /** @return HasMany<FleetCollectionLine, $this> */
    public function lines(): HasMany
    {
        return $this->hasMany(FleetCollectionLine::class, 'collection_id');
    }

    public function isDraft(): bool
    {
        return $this->status === self::STATUS_DRAFT;
    }

    public function isConfirmed(): bool
    {
        return $this->status === self::STATUS_CONFIRMED;
    }

    /** @return array<string, string> */
    public static function paymentMethodLabels(): array
    {
        return [
            self::PAYMENT_COD => 'تحصيل نقدي (COD)',
            self::PAYMENT_TRANSFER => 'تحويل بنكي',
            self::PAYMENT_CREDIT => 'آجل / ذمة',
        ];
    }
}
