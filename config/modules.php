<?php

declare(strict_types=1);

/**
 * Akwad System — سجل الموديولات (Phase 1: Module Registry).
 * المراحل القادمة ستربط الباقات والنيشات (ecommerce, clinic, factory…) بنفس المفاتيح.
 */
return [

    'modules' => [
        'core' => [
            'name_ar' => 'النواة',
            'name_en' => 'Core',
            'description_ar' => 'لوحة التحكم، الملف الشخصي، إعدادات المنشأة.',
            'is_core' => true,
            'niche_tags' => ['platform'],
            'sort_order' => 0,
        ],
        'finance' => [
            'name_ar' => 'المحاسبة',
            'name_en' => 'Finance',
            'description_ar' => 'دليل الحسابات، القيود، المصروفات، التقارير المالية.',
            'is_core' => false,
            'niche_tags' => ['erp', 'accounting', 'factory', 'ecommerce', 'clinic'],
            'sort_order' => 10,
        ],
        'inventory' => [
            'name_ar' => 'المخزون',
            'name_en' => 'Inventory',
            'description_ar' => 'الأصناف، المستودعات، الحركات، الجرد.',
            'is_core' => false,
            'niche_tags' => ['erp', 'factory', 'ecommerce'],
            'sort_order' => 20,
        ],
        'manufacturing' => [
            'name_ar' => 'التصنيع',
            'name_en' => 'Manufacturing',
            'description_ar' => 'قوائم المواد، أوامر العمل، الترحيل.',
            'is_core' => false,
            'niche_tags' => ['erp', 'factory'],
            'sort_order' => 30,
        ],
        'sales' => [
            'name_ar' => 'المبيعات',
            'name_en' => 'Sales',
            'description_ar' => 'العملاء، عروض الأسعار، الفواتير، التوريد.',
            'is_core' => false,
            'niche_tags' => ['erp', 'ecommerce', 'factory'],
            'sort_order' => 40,
        ],
        'purchases' => [
            'name_ar' => 'المشتريات',
            'name_en' => 'Purchases',
            'description_ar' => 'الموردون، أوامر الشراء، فواتير الموردين.',
            'is_core' => false,
            'niche_tags' => ['erp', 'factory', 'ecommerce'],
            'sort_order' => 50,
        ],
        'hr' => [
            'name_ar' => 'الموارد البشرية',
            'name_en' => 'Human Resources',
            'description_ar' => 'الموظفون، الحضور، الرواتب.',
            'is_core' => false,
            'niche_tags' => ['erp', 'factory', 'clinic'],
            'sort_order' => 60,
        ],
        'pos' => [
            'name_ar' => 'نقاط البيع',
            'name_en' => 'Point of Sale',
            'description_ar' => 'الكاشير، الجلسات، الإيصالات.',
            'is_core' => false,
            'niche_tags' => ['ecommerce', 'retail', 'factory', 'fleet', 'clinic', 'nursery'],
            'sort_order' => 70,
        ],
        'crm' => [
            'name_ar' => 'إدارة العملاء',
            'name_en' => 'CRM',
            'description_ar' => 'العملاء المحتملون، الفرص، المواعيد، الولاء.',
            'is_core' => false,
            'niche_tags' => ['crm', 'clinic', 'ecommerce'],
            'sort_order' => 80,
        ],
        'services' => [
            'name_ar' => 'الخدمات والصيانة',
            'name_en' => 'Services',
            'description_ar' => 'طلبات الخدمة والصيانة الميدانية.',
            'is_core' => false,
            'niche_tags' => ['services', 'clinic'],
            'sort_order' => 90,
        ],
        'clinic' => [
            'name_ar' => 'العيادة',
            'name_en' => 'Clinic',
            'description_ar' => 'المرضى، الحجوزات، والروشتات الطبية.',
            'is_core' => false,
            'niche_tags' => ['clinic'],
            'sort_order' => 85,
        ],
        'nursery' => [
            'name_ar' => 'الحضانة',
            'name_en' => 'Nursery',
            'description_ar' => 'الأطفال، الفصول، حضور وانصراف يومي.',
            'is_core' => false,
            'niche_tags' => ['nursery'],
            'sort_order' => 86,
        ],
        'fleet' => [
            'name_ar' => 'المناديب',
            'name_en' => 'Fleet / Field Ops',
            'description_ar' => 'المناديب، عملاء الميدان، وكتalog خفيف للبضاعة.',
            'is_core' => false,
            'niche_tags' => ['fleet'],
            'sort_order' => 87,
        ],
    ],

    /**
     * بادئات المسارات → مفتاح الموديول (للتحقق التلقائي لاحقاً في Phase 2 APIs).
     */
    'route_prefixes' => [
        'finance' => 'finance',
        'inventory' => 'inventory',
        'items' => 'inventory',
        'warehouses' => 'inventory',
        'manufacturing' => 'manufacturing',
        'sales' => 'sales',
        'purchases' => 'purchases',
        'hr' => 'hr',
        'pos' => 'pos',
        'crm' => 'crm',
        'services' => 'services',
        'clinic' => 'clinic',
        'nursery' => 'nursery',
        'fleet' => 'fleet',
        'operations' => 'manufacturing',
    ],

];
