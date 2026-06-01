<?php

declare(strict_types=1);

return [

    'whatsapp' => [
        'enabled' => (bool) env('CLINIC_WHATSAPP_ENABLED', false),
        'access_token' => env('CLINIC_WHATSAPP_ACCESS_TOKEN'),
        'phone_number_id' => env('CLINIC_WHATSAPP_PHONE_NUMBER_ID'),
        'api_version' => env('CLINIC_WHATSAPP_API_VERSION', 'v21.0'),
        'default_country_code' => env('CLINIC_WHATSAPP_DEFAULT_COUNTRY_CODE', '20'),
    ],

    'portal' => [
        'booking_lookahead_days' => (int) env('CLINIC_PORTAL_BOOKING_LOOKAHEAD_DAYS', 30),
        'manage_cutoff_hours' => (int) env('CLINIC_PORTAL_MANAGE_CUTOFF_HOURS', 24),
    ],

];
