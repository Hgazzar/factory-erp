<?php

declare(strict_types=1);

return [
    'payment' => [
        'providers' => ['paymob', 'stripe'],
        'default_provider' => env('STORE_PAYMENT_PROVIDER', 'paymob'),
        'sandbox' => (bool) env('STORE_PAYMENT_SANDBOX', true),
        'default_country_code' => env('STORE_DEFAULT_COUNTRY_CODE', 'SA'),
        'methods_by_country' => [
            'SA' => ['cod', 'manual_transfer', 'card', 'tamara', 'tabby'],
            'EG' => ['cod', 'manual_transfer', 'card'],
            'AE' => ['cod', 'manual_transfer', 'card', 'tabby'],
            'default' => ['cod', 'manual_transfer'],
        ],
    ],
    'webhooks' => [
        /*
         * Paymob "Transaction Processed" callback URL (POST).
         * Full URL = APP_URL + paymob_path  →  use store_paymob_webhook_url() helper.
         * Configure the same URL in Paymob Dashboard → Developers → Payment Integrations → Integration ID → Processed callback URL.
         * Paymob appends ?hmac=… — verified against tenant paymob_hmac_secret (إعدادات المتجر).
         */
        'paymob_path' => env('STORE_PAYMOB_WEBHOOK_PATH', '/webhooks/store/paymob'),
    ],
    'whatsapp' => [
        'enabled' => (bool) env('STORE_WHATSAPP_ENABLED', env('CLINIC_WHATSAPP_ENABLED', false)),
        'access_token' => env('STORE_WHATSAPP_ACCESS_TOKEN', env('CLINIC_WHATSAPP_ACCESS_TOKEN')),
        'phone_number_id' => env('STORE_WHATSAPP_PHONE_NUMBER_ID', env('CLINIC_WHATSAPP_PHONE_NUMBER_ID')),
        'api_version' => env('STORE_WHATSAPP_API_VERSION', env('CLINIC_WHATSAPP_API_VERSION', 'v21.0')),
        'default_country_code' => env('STORE_WHATSAPP_DEFAULT_COUNTRY_CODE', env('CLINIC_WHATSAPP_DEFAULT_COUNTRY_CODE', '966')),
    ],

    /**
     * قدرات المتجر / POS لكل نيش — يُستخدم في Middleware، لوحة التاجر، والمزايا البريميوم.
     */
    'niches' => [
        'retail' => [
            'online_store_portal' => true,
            'provision_online_store' => true,
            'storefront_label_ar' => 'المتجر الإلكتروني',
            'settings_nav_label_ar' => 'إعدادات المتجر',
            'orders_nav_label_ar' => 'طلبات المتجر',
            'pos_lexicon_key' => 'modules.pos',
            'whatsapp_feature_keys' => ['retail_whatsapp_automation'],
        ],
        'full_erp' => [
            'online_store_portal' => true,
            'provision_online_store' => false,
            'storefront_label_ar' => 'المتجر الإلكتروني',
            'settings_nav_label_ar' => 'إعدادات المتجر',
            'orders_nav_label_ar' => 'طلبات المتجر',
            'pos_lexicon_key' => 'modules.pos',
            'whatsapp_feature_keys' => ['retail_whatsapp_automation'],
        ],
        'manufacturing' => [
            'online_store_portal' => true,
            'provision_online_store' => false,
            'storefront_label_ar' => 'معرض منتجات المصنع',
            'settings_nav_label_ar' => 'معرض المنتجات أونلاين',
            'orders_nav_label_ar' => 'طلبات المعرض',
            'pos_lexicon_key' => 'modules.pos',
            'whatsapp_feature_keys' => ['retail_whatsapp_automation'],
        ],
        'fleet_agents' => [
            'online_store_portal' => true,
            'provision_online_store' => false,
            'storefront_label_ar' => 'متجر المندوب',
            'settings_nav_label_ar' => 'متجر المندوب',
            'orders_nav_label_ar' => 'طلبات التحصيل',
            'pos_lexicon_key' => 'modules.pos',
            'whatsapp_feature_keys' => ['retail_whatsapp_automation'],
        ],
        'medical_clinics' => [
            'online_store_portal' => true,
            'provision_online_store' => false,
            'storefront_label_ar' => 'متجر المستلزمات',
            'settings_nav_label_ar' => 'متجر المستلزمات',
            'orders_nav_label_ar' => 'طلبات المستلزمات',
            'pos_lexicon_key' => 'modules.pos',
            'whatsapp_feature_keys' => ['clinic_whatsapp_automation', 'retail_whatsapp_automation'],
        ],
        'nurseries' => [
            'online_store_portal' => true,
            'provision_online_store' => false,
            'storefront_label_ar' => 'متجر أولياء الأمور',
            'settings_nav_label_ar' => 'متجر الحضانة',
            'orders_nav_label_ar' => 'طلبات المتجر',
            'pos_lexicon_key' => 'modules.pos',
            'whatsapp_feature_keys' => ['nursery_whatsapp_automation', 'retail_whatsapp_automation'],
        ],
        'restaurants' => [
            'online_store_portal' => true,
            'provision_online_store' => true,
            'storefront_label_ar' => 'طلبات المطعم أونلاين',
            'settings_nav_label_ar' => 'إعدادات الطلب أونلاين',
            'orders_nav_label_ar' => 'طلبات التوصيل',
            'pos_lexicon_key' => 'modules.pos',
            'whatsapp_feature_keys' => ['retail_whatsapp_automation'],
        ],
        '_default' => [
            'online_store_portal' => false,
            'provision_online_store' => false,
            'storefront_label_ar' => 'المتجر الإلكتروني',
            'settings_nav_label_ar' => 'المتجر الإلكتروني',
            'orders_nav_label_ar' => 'طلبات المتجر',
            'pos_lexicon_key' => 'modules.pos',
            'whatsapp_feature_keys' => ['retail_whatsapp_automation'],
        ],
    ],
];
