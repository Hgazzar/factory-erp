<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class CrmMembership extends Model
{
    protected $fillable = [
        'user_id',
        'code',
        'name',
        'level',
        'discount_type',
        'discount_value',
        'min_spending',
        'color',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'user_id' => 'integer',
            'level' => 'integer',
            'discount_value' => 'decimal:2',
            'min_spending' => 'decimal:2',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $membership): void {
            if (! filled($membership->code) && (int) $membership->user_id > 0) {
                $membership->code = static::generateNextCodeForTenant((int) $membership->user_id);
            }
        });
    }

    public function scopeForTenant(Builder $query, int $tenantId): Builder
    {
        return $query->where('user_id', $tenantId);
    }

    public static function statusLabels(): array
    {
        return [
            'active' => 'نشط',
            'paused' => 'متوقف',
        ];
    }

    public static function discountTypeLabels(): array
    {
        return [
            'percentage' => 'نسبة',
            'fixed' => 'مبلغ ثابت',
        ];
    }

    public static function generateNextCodeForTenant(int $tenantId): string
    {
        $codes = static::query()
            ->forTenant($tenantId)
            ->where('code', 'like', 'MEM-%')
            ->pluck('code');

        $max = 0;
        foreach ($codes as $code) {
            if (preg_match('/^MEM-(\d+)$/', (string) $code, $m)) {
                $max = max($max, (int) $m[1]);
            }
        }

        return 'MEM-'.str_pad((string) ($max + 1), 3, '0', STR_PAD_LEFT);
    }
}

