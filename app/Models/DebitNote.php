<?php

namespace App\Models;

use App\Models\Concerns\ResolvesRouteBindingForTenant;
use App\Models\Scopes\BelongsToAuthenticatedUserScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DebitNote extends Model
{
    use HasFactory;
    use ResolvesRouteBindingForTenant;

    protected $fillable = [
        'user_id',
        'note_number',
        'supplier_id',
        'purchase_invoice_id',
        'date',
        'reference',
        'original_invoice_ref',
        'amount',
        'tax_amount',
        'reason_type',
        'reason',
        'notes',
        'status',
        'journal_entry_id',
        'approved_at',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'amount' => 'decimal:4',
            'tax_amount' => 'decimal:4',
            'approved_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $debitNote): void {
            if (! $debitNote->user_id && $debitNote->supplier_id) {
                $debitNote->user_id = (int) (Supplier::withoutGlobalScopes()
                    ->where('id', $debitNote->supplier_id)
                    ->value('user_id') ?? auth()->id() ?? 1);
            }
            if (! $debitNote->note_number) {
                $debitNote->note_number = self::generateNoteNumber($debitNote->date);
            }
        });

        static::addGlobalScope(new BelongsToAuthenticatedUserScope);
    }

    public static function generateNoteNumber(string|\DateTimeInterface|null $date = null): string
    {
        $year = $date ? date('Y', strtotime((string) $date)) : now()->format('Y');
        $prefix = 'DN-'.$year.'-';

        $driver = self::query()->getConnection()->getDriverName();
        $seqExpr = match ($driver) {
            'pgsql' => 'MAX(CAST(RIGHT(note_number::text, 4) AS INTEGER))',
            'sqlite', 'sqlite3' => 'MAX(CAST(substr(note_number, -4) AS INTEGER))',
            default => 'MAX(CAST(SUBSTRING(note_number, -4) AS UNSIGNED))',
        };

        $lastNumber = self::query()
            ->where('note_number', 'like', $prefix.'%')
            ->selectRaw("{$seqExpr} as seq")
            ->value('seq');

        $next = ((int) $lastNumber) + 1;

        return $prefix.str_pad((string) $next, 4, '0', STR_PAD_LEFT);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function purchaseInvoice(): BelongsTo
    {
        return $this->belongsTo(PurchaseInvoice::class);
    }

    public function journalEntry(): BelongsTo
    {
        return $this->belongsTo(JournalEntry::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(DebitNoteItem::class);
    }
}
