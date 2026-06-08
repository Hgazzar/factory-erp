<?php

declare(strict_types=1);

use App\Support\ErpFormat;

if (! function_exists('erp_money')) {
    function erp_money(float|string|int|null $value): string
    {
        return ErpFormat::money($value);
    }
}

if (! function_exists('erp_qty')) {
    function erp_qty(float|string|int|null $value): string
    {
        return ErpFormat::qty($value);
    }
}

if (! function_exists('niche_label')) {
    /**
     * مسمى ديناميكي حسب نيش المستأجر الحالي.
     */
    function niche_label(string $termKey, ?string $fallback = null): string
    {
        return app(\App\Services\Tenant\NicheLexiconService::class)
            ->label($termKey, null, $fallback);
    }
}

if (! function_exists('niche_module_label')) {
    function niche_module_label(string $moduleKey): string
    {
        return app(\App\Services\Tenant\NicheLexiconService::class)
            ->moduleLabel($moduleKey);
    }
}

if (! function_exists('store_paymob_webhook_url')) {
    /**
     * Production Paymob processed-callback URL (POST). Must match APP_URL on the server.
     */
    function store_paymob_webhook_url(): string
    {
        $base = rtrim((string) config('app.url'), '/');
        $path = (string) config('store.webhooks.paymob_path', '/webhooks/store/paymob');

        if ($path === '' || $path[0] !== '/') {
            $path = '/'.$path;
        }

        return $base.$path;
    }
}

if (! function_exists('store_niche_nav_label')) {
    function store_niche_nav_label(string $field = 'storefront'): string
    {
        if (! auth()->check()) {
            return 'المتجر الإلكتروني';
        }

        $nicheKey = app(\App\Services\Tenant\NicheLexiconService::class)
            ->resolveNicheKey((int) auth()->id());
        $caps = app(\App\Services\Store\StoreNicheCapabilities::class);

        return match ($field) {
            'settings' => $caps->settingsNavLabel($nicheKey),
            'orders' => $caps->ordersNavLabel($nicheKey),
            default => $caps->storefrontLabel($nicheKey),
        };
    }
}
