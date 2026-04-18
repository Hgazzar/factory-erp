<?php

namespace App\Models;

use App\Models\Concerns\ResolvesRouteBindingForTenant;
use App\Models\Scopes\BelongsToAuthenticatedUserScope;
use App\Traits\HasAttachments;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SalesOrder extends Model
{
    use HasAttachments;
    use HasFactory;
    use ResolvesRouteBindingForTenant;

    public const STATUS_PENDING = 'معلق';

    public const STATUS_COMPLETED = 'مكتمل';

    public const STATUS_CANCELLED = 'ملغي';

    protected $fillable = [
        'user_id',
        'order_number',
        'customer_id',
        'quotation_id',
        'order_date',
        'expected_delivery',
        'total',
        'status',
        'reference',
        'notes',
        'journal_entry_id',
    ];

    protected function casts(): array
    {
        return [
            'order_date' => 'date',
            'expected_delivery' => 'date',
            'total' => 'decimal:4',
        ];
    }

    protected static function booted(): void
    {
        static::addGlobalScope(new BelongsToAuthenticatedUserScope);
    }

    public static function generateNextOrderNumberForUser(int $userId): string
    {
        $codes = static::withoutGlobalScopes()
            ->where('user_id', $userId)
            ->whereNotNull('order_number')
            ->pluck('order_number');

        $max = 0;
        foreach ($codes as $c) {
            if (preg_match('/^SO-(\d+)$/i', (string) $c, $m)) {
                $max = max($max, (int) $m[1]);
            }
        }

        $next = $max + 1;
        do {
            $code = 'SO-'.$next;
            $exists = static::withoutGlobalScopes()
                ->where('user_id', $userId)
                ->where('order_number', $code)
                ->exists();
            $next++;
        } while ($exists);

        return $code;
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function quotation(): BelongsTo
    {
        return $this->belongsTo(Quotation::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(SalesOrderItem::class);
    }

    public function deliveryOrders(): HasMany
    {
        return $this->hasMany(DeliveryOrder::class);
    }

    public function accountingJournalEntry(): BelongsTo
    {
        return $this->belongsTo(JournalEntry::class, 'journal_entry_id');
    }
}
