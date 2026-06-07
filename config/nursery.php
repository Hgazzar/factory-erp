<?php

declare(strict_types=1);

return [
    'whatsapp' => [
        'enabled' => (bool) env('NURSERY_WHATSAPP_ENABLED', env('CLINIC_WHATSAPP_ENABLED', false)),
        'access_token' => env('NURSERY_WHATSAPP_ACCESS_TOKEN', env('CLINIC_WHATSAPP_ACCESS_TOKEN')),
        'phone_number_id' => env('NURSERY_WHATSAPP_PHONE_NUMBER_ID', env('CLINIC_WHATSAPP_PHONE_NUMBER_ID')),
        'api_version' => env('NURSERY_WHATSAPP_API_VERSION', env('CLINIC_WHATSAPP_API_VERSION', 'v21.0')),
        'default_country_code' => env('NURSERY_WHATSAPP_DEFAULT_COUNTRY_CODE', '966'),
    ],

    'subscriptions' => [
        'renewal_reminder_days' => (int) env('NURSERY_RENEWAL_REMINDER_DAYS', 30),
    ],

    'shift_late_grace_minutes' => (int) env('NURSERY_SHIFT_LATE_GRACE_MINUTES', 15),

    'portal' => [
        /** في التطوير: OTP ثابت يُسجَّل في الـ log — جاهز للربط بواتساب لاحقاً */
        'otp_log_only' => (bool) env('NURSERY_PORTAL_OTP_LOG_ONLY', true),
        'otp_length' => (int) env('NURSERY_PORTAL_OTP_LENGTH', 6),
        'otp_ttl_minutes' => (int) env('NURSERY_PORTAL_OTP_TTL_MINUTES', 10),
        'dev_otp_code' => env('NURSERY_PORTAL_DEV_OTP_CODE', '123456'),
    ],
];
