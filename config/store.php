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
];
