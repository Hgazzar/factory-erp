<?php

declare(strict_types=1);

/**
 * Akwad SaaS — النيشات وموديولاتها الافتراضية.
 * يُستخدم عند إنشاء مستأجر جديد وعند تحميل سياق النيش في الـ Middleware.
 */
return [

    'niches' => [

        'manufacturing' => [
            'key' => 'manufacturing',
            'name_ar' => 'المصانع',
            'name_en' => 'Manufacturing',
            'description_ar' => 'إنتاج، قوائم مواد (BOM)، ومخازن الخامات.',
            'modules' => [
                'finance',
                'inventory',
                'manufacturing',
                'purchases',
                'sales',
                'hr',
            ],
        ],

        'retail' => [
            'key' => 'retail',
            'name_ar' => 'المتجر التجاري',
            'name_en' => 'Retail / E-commerce',
            'description_ar' => 'نقاط البيع، إدارة العملاء، والمبيعات.',
            'modules' => [
                'finance',
                'inventory',
                'sales',
                'pos',
                'crm',
                'purchases',
            ],
            'default_premium_features' => [
                'online_store',
                'retail_whatsapp_automation',
            ],
        ],

        'medical_clinics' => [
            'key' => 'medical_clinics',
            'name_ar' => 'العيادات الطبية',
            'name_en' => 'Medical Clinics',
            'description_ar' => 'ملفات المرضى، الروشتات، والحجوزات.',
            'modules' => [
                'finance',
                'clinic',
                'crm',
                'services',
                'hr',
            ],
        ],

        'nurseries' => [
            'key' => 'nurseries',
            'name_ar' => 'الحضانة',
            'name_en' => 'Nurseries',
            'description_ar' => 'الأطفال، الفصول، أولياء الأمور، والاشتراكات.',
            'modules' => [
                'finance',
                'nursery',
                'hr',
            ],
            'default_premium_features' => [
                'nursery_portal',
            ],
        ],

        'fleet_agents' => [
            'key' => 'fleet_agents',
            'name_ar' => 'المناديب',
            'name_en' => 'Fleet / Field Agents',
            'description_ar' => 'عهدة المناديب، خطوط السير، والتحصيل الميداني.',
            'modules' => [
                'finance',
                'fleet',
            ],
            'default_premium_features' => [
                'fleet_field_ops',
            ],
        ],

        // 🌟 النيش الجديد: نظام الـ ERP الشامل والمتكامل
        'full_erp' => [
            'key' => 'full_erp',
            'name_ar' => 'نظام ERP متكامل (كل الموديولات)',
            'name_en' => 'Enterprise Resource Planning (Full Suite)',
            'description_ar' => 'النظام الإداري الشامل: مالية، مخازن، مبيعات، مشتريات، تصنيع، HR، والعيادات.',
            'modules' => [
                'finance',
                'inventory',
                'purchases',
                'sales',
                'hr',
                'manufacturing',
                'crm',
                'pos',
                'clinic',
                'services',
            ],
        ],

    ],

];