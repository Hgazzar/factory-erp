<?php

namespace App\Models;

use App\Models\Concerns\ResolvesRouteBindingForTenant;
use App\Models\Scopes\BelongsToAuthenticatedUserScope;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PurchaseOrder extends Model
{
    use HasFactory;
    use ResolvesRouteBindingForTenant;

    protected $fillable = [
        'user_id',
        'order_number',
        'supplier_id',
        'order_date',
        'currency',
        'reference',
        'expected_delivery_date',
        'delivery_address',
        'shipping_cost',
        'internal_notes',
        'notes',
        'terms_and_conditions',
        'status',
        'subtotal',
        'total_discount',
        'total_tax',
        'total',
    ];

    protected function casts(): array
    {
        return [
            'order_date' => 'date',
            'expected_delivery_date' => 'date',
            'shipping_cost' => 'decimal:4',
            'subtotal' => 'decimal:4',
            'total_discount' => 'decimal:4',
            'total_tax' => 'decimal:4',
            'total' => 'decimal:4',
        ];
    }

    /**
     * رقم الأمر للعرض: PO202603-0001 → PO-0001 (إزالة سنة/شهر بعد PO عند وجودهما).
     */
    protected function displayOrderNumber(): Attribute
    {
        return Attribute::get(function (): string {
            $fallback = 'PO-'.str_pad((string) $this->id, 4, '0', STR_PAD_LEFT);
            $s = trim((string) ($this->order_number ?? ''));
            if ($s === '') {
                return $fallback;
            }
            $out = preg_replace('/^PO\d{6}-/i', 'PO-', $s);

            return (is_string($out) && $out !== '') ? $out : $fallback;
        });
    }

    protected static function booted(): void
    {
        static::addGlobalScope(new BelongsToAuthenticatedUserScope);
    }

    public static function generateNextOrderNumberForUser(int $userId): string
    {
        $nums = static::withoutGlobalScopes()
            ->where('user_id', $userId)
            ->whereNotNull('order_number')
            ->where('order_number', 'like', 'PO-%')
            ->pluck('order_number');

        $max = 0;
        foreach ($nums as $n) {
            if (preg_match('/^PO-(\d+)$/i', (string) $n, $m)) {
                $max = max($max, (int) $m[1]);
            }
        }

        $next = $max + 1;
        do {
            $code = 'PO-'.str_pad((string) $next, 4, '0', STR_PAD_LEFT);
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

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(PurchaseOrderItem::class, 'purchase_order_id');
    }
}
