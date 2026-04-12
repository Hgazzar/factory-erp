<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EinvoiceSetting extends Model
{
    protected $table = 'einvoice_settings';

    /**
     * @var list<string>
     */
    protected $hidden = [
        'certificate',
        'private_key',
        'compliance_secret',
    ];

    protected $fillable = [
        'provider',
        'environment',
        'retry_attempts',
        'retry_delay_minutes',
        'enabled',
        'auto_send_on_issue',
        'zatca_tax_number',
        'zatca_seller_name',
        'zatca_seller_name_ar',
        'csr_path',
        'private_key_path',
        'otp',
        'certificate',
        'private_key',
        'request_id',
        'compliance_secret',
    ];

    protected function casts(): array
    {
        return [
            'retry_attempts' => 'integer',
            'retry_delay_minutes' => 'integer',
            'enabled' => 'boolean',
            'auto_send_on_issue' => 'boolean',
        ];
    }

    public static function get(): self
    {
        $s = self::first();
        if (! $s) {
            $s = self::create([
                'provider' => 'zatca',
                'environment' => 'sandbox',
                'retry_attempts' => 3,
                'retry_delay_minutes' => 0,
            ]);
        }

        return $s;
    }
}
