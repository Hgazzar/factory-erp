<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TenantStoreSetting extends Model
{
    protected $fillable = [
        'tenant_user_id',
        'is_store_enabled',
        'cod_enabled',
        'field_delivery_enabled',
        'manual_transfer_enabled',
        'online_payment_enabled',
        'online_payment_provider',
        'online_payment_mode',
        'online_payment_public_key',
        'online_payment_secret_key',
        'tamara_enabled',
        'tabby_enabled',
        'paymob_integration_id',
        'paymob_hmac_secret',
        'tamara_api_token',
        'tabby_public_key',
        'tabby_secret_key',
        'hero_title',
        'hero_subtitle',
        'hero_offer_text',
        'about_us',
        'contact_us',
        'faq',
        'shipping_policy',
        'return_policy',
        'track_order_help',
        'privacy_policy',
        'social_facebook',
        'social_instagram',
        'social_twitter',
        'social_whatsapp',
        'default_pos_device_id',
    ];

    protected function casts(): array
    {
        return [
            'is_store_enabled' => 'boolean',
            'cod_enabled' => 'boolean',
            'field_delivery_enabled' => 'boolean',
            'manual_transfer_enabled' => 'boolean',
            'online_payment_enabled' => 'boolean',
            'tamara_enabled' => 'boolean',
            'tabby_enabled' => 'boolean',
        ];
    }

    public function tenantUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'tenant_user_id');
    }

    public function defaultPosDevice(): BelongsTo
    {
        return $this->belongsTo(PosDevice::class, 'default_pos_device_id');
    }

    public static function forTenant(int $tenantUserId): self
    {
        if ($tenantUserId < 1) {
            throw new \InvalidArgumentException('معرّف المستأجر غير صالح.');
        }

        return static::query()->firstOrCreate(
            ['tenant_user_id' => $tenantUserId],
            [
                'is_store_enabled' => true,
            'cod_enabled' => true,
            'field_delivery_enabled' => false,
            'manual_transfer_enabled' => false,
                'online_payment_enabled' => true,
                'online_payment_provider' => config('store.payment.default_provider', 'paymob'),
                'online_payment_mode' => config('store.payment.sandbox', true) ? 'sandbox' : 'live',
            ],
        );
    }

    public function acceptsOnlineCardPayments(): bool
    {
        if ($this->online_payment_enabled) {
            return true;
        }

        return config('store.payment.sandbox', true)
            && ($this->online_payment_mode ?? 'sandbox') !== 'live';
    }

    public function effectivePaymentProvider(): string
    {
        $provider = strtolower(trim((string) ($this->online_payment_provider ?: '')));

        return in_array($provider, config('store.payment.providers', ['paymob', 'stripe']), true)
            ? $provider
            : (string) config('store.payment.default_provider', 'paymob');
    }

    public function paymentProviderLabel(): string
    {
        return match ($this->effectivePaymentProvider()) {
            'stripe' => 'Stripe',
            'paymob' => 'Paymob',
            default => 'بطاقة',
        };
    }

    /**
     * @return array<string, string|null>
     */
    public function socialLinks(): array
    {
        return [
            'facebook' => $this->social_facebook,
            'instagram' => $this->social_instagram,
            'twitter' => $this->social_twitter,
            'whatsapp' => $this->social_whatsapp,
        ];
    }
}
