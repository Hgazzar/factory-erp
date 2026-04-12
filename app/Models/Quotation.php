<?php

namespace App\Models;

use App\Models\Concerns\ResolvesRouteBindingForTenant;
use App\Models\Scopes\BelongsToAuthenticatedUserScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Quotation extends Model
{
    use HasFactory;
    use ResolvesRouteBindingForTenant;

    public const STATUS_DRAFT = 'draft';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_REJECTED = 'rejected';

    public const STATUS_CONVERTED_TO_ORDER = 'converted_to_order';

    /** @var list<string> */
    public const STATUSES = [
        self::STATUS_DRAFT,
        self::STATUS_APPROVED,
        self::STATUS_REJECTED,
        self::STATUS_CONVERTED_TO_ORDER,
    ];

    protected $fillable = [
        'user_id',
        'quotation_number',
        'customer_id',
        'date',
        'valid_until',
        'notes',
        'internal_notes',
        'terms',
        'status',
        'total_amount',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'valid_until' => 'date',
            'total_amount' => 'decimal:4',
        ];
    }

    protected static function booted(): void
    {
        static::addGlobalScope(new BelongsToAuthenticatedUserScope);
    }

    public static function generateNextQuotationNumberForUser(int $userId): string
    {
        $codes = static::withoutGlobalScopes()
            ->where('user_id', $userId)
            ->whereNotNull('quotation_number')
            ->where('quotation_number', 'like', 'QT-%')
            ->pluck('quotation_number');

        $max = 0;
        foreach ($codes as $c) {
            if (preg_match('/^QT-(\d+)$/i', (string) $c, $m)) {
                $max = max($max, (int) $m[1]);
            }
        }

        $next = $max + 1;
        do {
            $code = 'QT-'.str_pad((string) $next, 4, '0', STR_PAD_LEFT);
            $exists = static::withoutGlobalScopes()
                ->where('user_id', $userId)
                ->where('quotation_number', $code)
                ->exists();
            $next++;
        } while ($exists);

        return $code;
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Backward compatibility: legacy views use $quotation->total.
     */
    public function getTotalAttribute(): mixed
    {
        return $this->total_amount;
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(QuotationItem::class);
    }
}
