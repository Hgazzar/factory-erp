<?php

declare(strict_types=1);

return [
    /**
     * الفئات العمرية للفصل والوحدات — مفاتيح ثابتة.
     *
     * @var array<string, string>
     */
    'age_groups' => [
        '0_6m' => '0-6 شهور',
        '6m_1y' => '6 شهور - سنة واحدة',
        '1_2y' => 'سنة - سنتين',
        '2_3y' => 'سنتين - 3 سنوات',
        '3_4y' => '3 - 4 سنوات',
        '4_5y' => '4 - 5 سنوات',
        '5_6y' => '5 - 6 سنوات',
        'over_6y' => 'أكثر من 6 سنوات',
    ],

    'whatsapp' => [
        'enabled' => (bool) env('NURSERY_WHATSAPP_ENABLED', false),
        'access_token' => env('NURSERY_WHATSAPP_ACCESS_TOKEN'),
        'phone_number_id' => env('NURSERY_WHATSAPP_PHONE_NUMBER_ID'),
        'api_version' => env('NURSERY_WHATSAPP_API_VERSION', 'v21.0'),
        'default_country_code' => env('NURSERY_WHATSAPP_DEFAULT_COUNTRY_CODE', '966'),
    ],

    'subscriptions' => [
        'renewal_reminder_days' => (int) env('NURSERY_RENEWAL_REMINDER_DAYS', 30),
    ],

    'shift_late_grace_minutes' => (int) env('NURSERY_SHIFT_LATE_GRACE_MINUTES', 15),

    'portal' => [
        /** في التطوير: OTP ثابت يُسجَّل في الـ log — الإرسال عبر Outbox/واتساب */
        'otp_log_only' => (bool) env('NURSERY_PORTAL_OTP_LOG_ONLY', true),
        'otp_length' => (int) env('NURSERY_PORTAL_OTP_LENGTH', 6),
        'otp_ttl_minutes' => (int) env('NURSERY_PORTAL_OTP_TTL_MINUTES', 10),
        'dev_otp_code' => env('NURSERY_PORTAL_DEV_OTP_CODE', '123456'),
    ],

    /**
     * أنواع سجل يوم الطفل — مفاتيح ثابتة للتسجيل والعرض.
     *
     * @var array<string, array<string, mixed>>
     */
    'daily_activities' => [
        'types' => [
            'meal' => [
                'label' => 'وجبة',
                'parent_visible' => true,
                'options' => [
                    'meal' => [
                        'breakfast' => 'فطور',
                        'lunch' => 'غداء',
                        'snack' => 'وجبة خفيفة',
                        'dinner' => 'عشاء',
                    ],
                    'amount' => [
                        'eaten' => 'أكل كامل',
                        'partial' => 'أكل جزئي',
                        'refused' => 'رفض',
                    ],
                ],
            ],
            'nap' => [
                'label' => 'قيلولة',
                'parent_visible' => true,
                'options' => [],
            ],
            'diaper' => [
                'label' => 'حفاض',
                'parent_visible' => true,
                'options' => [
                    'change' => [
                        'wet' => 'مبلل',
                        'soiled' => 'متسخ',
                        'dry' => 'جاف',
                        'both' => 'مبلل ومتسخ',
                    ],
                ],
            ],
            'toilet' => [
                'label' => 'الحمام',
                'parent_visible' => true,
                'options' => [
                    'result' => [
                        'success' => 'نجح',
                        'attempt' => 'محاولة',
                    ],
                ],
            ],
            'mood' => [
                'label' => 'المزاج',
                'parent_visible' => true,
                'options' => [
                    'mood' => [
                        'happy' => 'سعيد',
                        'calm' => 'هادئ',
                        'sad' => 'حزين',
                        'tired' => 'متعب',
                        'upset' => 'منزعج',
                    ],
                ],
            ],
            'activity' => [
                'label' => 'نشاط',
                'parent_visible' => true,
                'options' => [],
            ],
            'medication' => [
                'label' => 'جرعة دواء',
                'parent_visible' => true,
                'options' => [
                    'status' => [
                        'given' => 'أُعطيت',
                        'partial' => 'أُعطيت جزئيًا',
                        'refused' => 'رفضها الطفل',
                        'missed' => 'لم تُعطَ / فاتت',
                    ],
                ],
            ],
            'note' => [
                'label' => 'ملاحظة المعلمة',
                'parent_visible' => false,
                'options' => [],
            ],
        ],
    ],
];
