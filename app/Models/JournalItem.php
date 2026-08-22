<?php

namespace App\Models;

use App\Models\Concerns\ResolvesRouteBindingForTenant;
use App\Models\Scopes\BelongsToTenantContextScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class JournalItem extends Model
{
    use HasFactory;
    use ResolvesRouteBindingForTenant;

    protected $fillable = [
        'user_id',
        'journal_entry_id',
        'account_id',
        'description',
        'cost_center',
        'debit',
        'credit',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope(new BelongsToTenantContextScope);

        static::creating(function (JournalItem $model): void {
            if (! $model->user_id && $model->journal_entry_id) {
                $model->user_id = (int) (JournalEntry::withoutGlobalScopes()
                    ->where('id', $model->journal_entry_id)
                    ->value('user_id') ?? app(\App\Services\Tenant\TenantContext::class)->resolveTenantUserId() ?? auth()->id() ?? 1);
            }
        });
    }

    protected function casts(): array
    {
        return [
            'debit' => 'decimal:4',
            'credit' => 'decimal:4',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function journalEntry(): BelongsTo
    {
        return $this->belongsTo(JournalEntry::class);
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }
}

