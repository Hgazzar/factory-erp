<?php

declare(strict_types=1);

use App\Support\PremiumFeatureKeys;

/**
 * كتالوج المزايا البريميوم حسب النيش — يُعرض في لوحة السوبر أدمن فقط.
 */
return [

    'niches' => [

        'retail' => [
            [
                'key' => PremiumFeatureKeys::RETAIL_MULTI_BRANCHES,
                'name_ar' => 'تعدد الفروع',
                'description_ar' => 'إدارة أكثر من فرع/مخزن في لوحة التاجر ونقاط البيع.',
                'hint' => 'premium_feature_retail_multi_branches',
            ],
            [
                'key' => PremiumFeatureKeys::RETAIL_POS_DEVICE_LINK,
                'name_ar' => 'ربط أجهزة الكاشير (بريميوم)',
                'description_ar' => 'تسجيل وربط أجهزة POS فعلية (MAC/توكن). لا يُفعّل موديول POS — يتطلب أن يكون موديول POS مفعّلاً في «الموديولات المتاحة» أدناه.',
                'hint' => 'premium_feature_retail_pos_device_link',
                'requires_module' => 'pos',
            ],
            [
                'key' => PremiumFeatureKeys::RETAIL_WHATSAPP_AUTOMATION,
                'name_ar' => 'الواتساب التلقائي',
                'description_ar' => 'إشعارات واتساب تلقائية للعملاء (طلبات، عروض، تذكيرات).',
                'hint' => 'premium_feature_retail_whatsapp_automation',
            ],
        ],

        'manufacturing' => [
            [
                'key' => PremiumFeatureKeys::MANUFACTURING_SMART_PRODUCTION_ENTRY,
                'name_ar' => 'شاشة تسجيل الإنتاج الذكية',
                'description_ar' => 'واجهة إنتاج متقدمة مع اقتراحات وربط بالورديات.',
                'hint' => 'premium_feature_manufacturing_smart_production_entry',
            ],
            [
                'key' => PremiumFeatureKeys::MANUFACTURING_INVENTORY_AUTO_LINK,
                'name_ar' => 'الربط المخزني المؤتمت',
                'description_ar' => 'خصم/إضافة مخزون تلقائياً عند تسجيل الإنتاج.',
                'hint' => 'premium_feature_manufacturing_inventory_auto_link',
            ],
            [
                'key' => PremiumFeatureKeys::MANUFACTURING_MACHINE_DOWNTIME,
                'name_ar' => 'تسجيل أعطال الماكينات',
                'description_ar' => 'تتبع توقفات وصيانة خطوط الإنتاج والماكينات.',
                'hint' => 'premium_feature_manufacturing_machine_downtime',
            ],
        ],

        'nurseries' => [
            [
                'key' => PremiumFeatureKeys::NURSERY_WHATSAPP_AUTOMATION,
                'name_ar' => 'تنبيهات واتساب الحضانة',
                'description_ar' => 'تذكير دفع وتجديد الاشتراكات وإشعارات لأولياء الأمور.',
                'hint' => 'premium_feature_nursery_whatsapp_automation',
            ],
            [
                'key' => PremiumFeatureKeys::NURSERY_SUBSCRIPTION_FINANCE,
                'name_ar' => 'ترحيل اشتراكات الحضانة للمالية',
                'description_ar' => 'قيد إيراد تلقائي عند تحصيل اشتراك طفل مدفوع.',
                'hint' => 'premium_feature_nursery_subscription_finance',
                'requires_module' => 'finance',
            ],
            [
                'key' => PremiumFeatureKeys::NURSERY_PORTAL,
                'name_ar' => 'بوابة أولياء الأمور',
                'description_ar' => 'بوابة عامة لمتابعة حضور الطفل والاشتراكات والتقويم والأدوية.',
                'hint' => 'premium_feature_nursery_parent_portal',
            ],
        ],

        'medical_clinics' => [
            [
                'key' => PremiumFeatureKeys::CLINIC_MEDICAL_INSURANCE,
                'name_ar' => 'منظومة شركات التأمين',
                'description_ar' => 'خطط التأمين والتغطية على الفواتير والمرضى.',
                'hint' => 'premium_feature_clinic_medical_insurance',
            ],
            [
                'key' => PremiumFeatureKeys::CLINIC_BRANCH_APPOINTMENTS,
                'name_ar' => 'إدارة حجوزات الفروع',
                'description_ar' => 'حجوزات متعددة الفروع ولوحة مواعيد لكل فرع.',
                'hint' => 'premium_feature_clinic_branch_appointments',
            ],
            [
                'key' => PremiumFeatureKeys::CLINIC_WHATSAPP_AUTOMATION,
                'name_ar' => 'نظام التنبيهات عبر الواتساب',
                'description_ar' => 'تأكيد المواعيد وتذكيرات تلقائية عبر واتساب.',
                'hint' => 'premium_feature_clinic_whatsapp_automation',
            ],
        ],

    ],

    /**
     * عند نيش full_erp تُعرض مجموعات كل النيشات أدناه.
     *
     * @var list<string>
     */
    'full_erp_niche_keys' => ['retail', 'manufacturing', 'nurseries', 'medical_clinics'],

];
