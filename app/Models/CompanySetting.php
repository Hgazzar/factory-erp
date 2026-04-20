<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CompanySetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'tax_number',
        'default_vat_percent',
        'commercial_register',
        'address',
        'logo_url',
    ];

    protected $casts = [
        'default_vat_percent' => 'float',
    ];

    /**
     * نسبة الضريبة الافتراضية للواجهات والنماذج: إعداد المنشأة ثم config ثم 15٪.
     */
    public static function resolvedDefaultVatPercent(): float
    {
        $row = static::query()->first();
        if ($row !== null && $row->default_vat_percent !== null) {
            return (float) $row->default_vat_percent;
        }

        return (float) config('accounting.default_vat_percent', 15);
    }
}
