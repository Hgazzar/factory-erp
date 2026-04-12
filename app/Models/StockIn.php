<?php

namespace App\Models;

use App\Models\Concerns\ResolvesRouteBindingForTenant;
use App\Models\Scopes\BelongsToAuthenticatedUserScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StockIn extends Model
{
    use ResolvesRouteBindingForTenant;

    protected $fillable = [
        'user_id',
        'document_number',
        'supplier_id',
        'settlement_type',
        'reference',
        'date',
        'notes',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope(new BelongsToAuthenticatedUserScope);

        static::creating(function (StockIn $model): void {
            if (! $model->user_id && $model->supplier_id) {
                $model->user_id = (int) (Supplier::withoutGlobalScopes()
                    ->where('id', $model->supplier_id)
                    ->value('user_id') ?? auth()->id() ?? 1);
            }
        });
    }

    protected function casts(): array
    {
        return [
            'date' => 'date',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function lines(): HasMany
    {
        return $this->hasMany(StockInLine::class);
    }
}
