<?php

namespace App\Models;

use App\Models\Concerns\ResolvesRouteBindingForTenant;
use App\Models\Scopes\BelongsToAuthenticatedUserScope;
use App\Traits\HasAttachments;
use Filament\Forms\Components\Select;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class Supplier extends Model
{
    use HasAttachments;
    use HasFactory;
    use ResolvesRouteBindingForTenant;

    protected $fillable = [
        'user_id',
        'code',
        'name',
        'name_ar',
        'contact_name',
        'phone',
        'mobile',
        'email',
        'website',
        'address',
        'supplier_type',
        'rating',
        'tax_number',
        'commercial_register',
        'credit_limit',
        'payment_terms_days',
        'currency',
        'bank_name',
        'bank_account_number',
        'iban',
        'swift_code',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'credit_limit' => 'decimal:4',
        ];
    }

    protected static function booted(): void
    {
        static::addGlobalScope(new BelongsToAuthenticatedUserScope);

        static::deleting(function (Supplier $supplier): void {
            foreach ($supplier->documents as $doc) {
                if ($doc->file_path && Storage::disk('public')->exists($doc->file_path)) {
                    Storage::disk('public')->delete($doc->file_path);
                }
            }
            $supplier->documents()->delete();
        });
    }

    public static function generateNextCodeForUser(int $userId): string
    {
        $codes = static::withoutGlobalScopes()
            ->where('user_id', $userId)
            ->where('code', 'like', 'SUP-%')
            ->pluck('code');

        $max = 0;
        foreach ($codes as $c) {
            if (preg_match('/^SUP-(\d+)$/i', (string) $c, $m)) {
                $max = max($max, (int) $m[1]);
            }
        }

        $next = $max + 1;
        do {
            $code = 'SUP-'.str_pad((string) $next, 4, '0', STR_PAD_LEFT);
            $exists = static::withoutGlobalScopes()
                ->where('user_id', $userId)
                ->where('code', $code)
                ->exists();
            $next++;
        } while ($exists);

        return $code;
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(SupplierDocument::class);
    }

    public function purchaseInvoices(): HasMany
    {
        return $this->hasMany(PurchaseInvoice::class);
    }

    public function purchaseOrders(): HasMany
    {
        return $this->hasMany(PurchaseOrder::class);
    }

    public static function localePrefersArabic(?string $locale = null): bool
    {
        $locale = strtolower(str_replace('_', '-', (string) ($locale ?? app()->getLocale())));

        return str_starts_with($locale, 'ar');
    }

    public function getLocalizedDisplayName(?string $locale = null): string
    {
        $preferAr = self::localePrefersArabic($locale);
        $primary = $preferAr ? $this->name_ar : $this->name;
        $secondary = $preferAr ? $this->name : $this->name_ar;
        $label = trim((string) ($primary ?: $secondary));

        if ($label === '') {
            $label = trim((string) $this->code);
        }

        return $label;
    }

    /**
     * تسمية نوع المورد للعرض (عربي عند الحاجة، مع ترجمة القيم الشائعة بالإنجليزية).
     */
    public function getLocalizedSupplierTypeLabel(?string $locale = null): string
    {
        $raw = trim((string) ($this->supplier_type ?? ''));
        if ($raw === '') {
            return '—';
        }

        if (! self::localePrefersArabic($locale)) {
            return $raw;
        }

        $key = mb_strtolower(str_replace([' ', '-'], '_', $raw));

        return match ($key) {
            'raw_materials' => 'مواد خام',
            'local', 'محلي' => 'محلي',
            'international', 'دولي' => 'دولي',
            'factory', 'مصنع' => 'مصنع',
            'distributor', 'موزع' => 'موزع',
            'service_provider', 'مزود_خدمات', 'مزود خدمات' => 'مزود خدمات',
            default => $raw,
        };
    }

    protected function localizedDisplayName(): Attribute
    {
        return Attribute::get(fn (): string => $this->getLocalizedDisplayName());
    }

    /**
     * Defaults for Filament Select fields bound to a BelongsTo supplier relationship.
     */
    public static function applyFilamentRelationshipSelect(Select $select, string $relationshipName = 'supplier'): Select
    {
        return $select
            ->relationship($relationshipName, 'name')
            ->getOptionLabelFromRecordUsing(function (Model $record): string {
                return $record instanceof self
                    ? $record->getLocalizedDisplayName()
                    : (string) ($record->getAttribute('name') ?? '');
            })
            ->searchable(['name', 'name_ar', 'code']);
    }
}
