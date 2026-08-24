<?php

declare(strict_types=1);

return [

    'agent_api' => [
        /** @var list<string> */
        'token_abilities' => ['fleet:agent'],
        'token_expiry_days' => (int) env('FLEET_AGENT_TOKEN_EXPIRY_DAYS', 90),
        'pin_min_length' => 4,
        'pin_max_length' => 8,
    ],

    'geofence' => [
        // العتبة الافتراضية لقبول الزيارة داخل النطاق (متر) — يمكن تجاوزها لكل عميل عبر geofence_radius.
        'default_radius_meters' => (int) env('FLEET_GEOFENCE_RADIUS_METERS', 150),
    ],

    // أسباب عدم البيع المعتمدة — قائمة مشتركة لتطبيق المندوب (الحقل يقبل نصاً حراً أيضاً).
    'no_sale_reasons' => [
        'stock_sufficient' => 'لدى العميل مخزون كافٍ',
        'shop_closed' => 'المحل مغلق',
        'owner_absent' => 'صاحب العمل غير موجود',
        'price_objection' => 'الأسعار مرتفعة / طلب خصم',
        'other' => 'سبب آخر',
    ],

];
