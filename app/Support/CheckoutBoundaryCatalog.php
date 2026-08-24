<?php

declare(strict_types=1);

namespace App\Support;

/**
 * حدود checkout — مصدر واحد للتحقق في الاختبارات والمراجعة.
 *
 * Clinic (مواعيد/فواتير) و Nursery (اشتراكات) لا يملكان Paymob/checkout مستقل.
 * أي دفع أونلاين للمنتجات يمر عبر routes/store.portal.* + StoreCheckoutService.
 */
final class CheckoutBoundaryCatalog
{
    /** @var list<string> */
    public const ISOLATED_MODULE_PATH_PREFIXES = [
        'app/Services/Clinic/',
        'app/Services/Nursery/',
        'app/Http/Controllers/Clinic/',
        'app/Http/Controllers/Nursery/',
    ];

    /** @return list<string> */
    public static function forbiddenTokensInIsolatedModules(): array
    {
        return [
            'PaymobGateway',
            'PaymentGatewayRegistry',
            'PaymobHmacVerifier',
            'accept.paymob.com',
            'StoreCheckoutService',
            'store.webhooks.paymob',
        ];
    }

    /** @return list<string> */
    public static function storeEnabledNicheKeys(): array
    {
        return [
            'retail',
            'restaurants',
            'full_erp',
            'manufacturing',
            'fleet_agents',
            'medical_clinics',
            'nurseries',
        ];
    }

    /** @return list<string> */
    public static function premiumCatalogNicheKeys(): array
    {
        return [
            'retail',
            'restaurants',
            'manufacturing',
            'fleet_agents',
            'medical_clinics',
            'nurseries',
        ];
    }
}
