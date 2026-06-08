<?php

declare(strict_types=1);

/**
 * Akwad SaaS — القاموس الافتراضي + مسميات كل نيش.
 * المفتاح: dot notation (modules.inventory، entities.customer، …).
 * تُدمَج overrides النيش فوق defaults عند التحميل.
 */
return [

    'defaults' => [
        'modules.finance' => 'المحاسبة',
        'modules.inventory' => 'المخزون',
        'modules.warehouses' => 'المستودعات',
        'modules.items' => 'الأصناف',
        'modules.manufacturing' => 'التصنيع',
        'modules.sales' => 'المبيعات',
        'modules.purchases' => 'المشتريات',
        'modules.hr' => 'الموارد البشرية',
        'modules.pos' => 'نقاط البيع',
        'modules.crm' => 'إدارة العملاء',
        'modules.services' => 'الخدمات والصيانة',
        'modules.clinic' => 'العيادة',
        'modules.nursery' => 'الحضانة',
        'modules.fleet' => 'المناديب',
        'entities.customer' => 'العميل',
        'entities.supplier' => 'المورد',
        'entities.item' => 'الصنف',
        'entities.warehouse' => 'المستودع',
        'entities.invoice' => 'الفاتورة',
        'entities.employee' => 'الموظف',
    ],

    'niche_overrides' => [

        'manufacturing' => [
            'modules.inventory' => 'مخزن الخامات',
            'modules.warehouses' => 'مخازن الخامات',
            'modules.items' => 'المواد والمنتجات',
            'modules.manufacturing' => 'خط الإنتاج',
            'modules.purchases' => 'توريد الخامات',
            'modules.sales' => 'مبيعات المصنع',
            'modules.pos' => 'كاشير المعرض',
            'entities.customer' => 'عميل التوزيع',
            'entities.supplier' => 'مورد الخامات',
            'entities.warehouse' => 'مخزن خامات',
            'entities.item' => 'مادة / منتج',
        ],

        'retail' => [
            'modules.inventory' => 'المعرض والمستودع',
            'modules.warehouses' => 'المعارض والمستودعات',
            'modules.items' => 'المنتجات المعروضة',
            'modules.sales' => 'مبيعات المتجر',
            'modules.crm' => 'عملاء المتجر',
            'modules.pos' => 'كاشير المتجر',
            'entities.customer' => 'عميل المتجر',
            'entities.warehouse' => 'المعرض / المستودع',
            'entities.item' => 'منتج',
        ],

        'medical_clinics' => [
            'modules.clinic' => 'العيادة',
            'modules.crm' => 'ملفات المرضى',
            'modules.services' => 'الروشتات والخدمات',
            'modules.hr' => 'طاقم العيادة',
            'modules.pos' => 'كاشير المستلزمات',
            'entities.customer' => 'المريض',
            'entities.invoice' => 'فاتورة الكشف',
            'entities.employee' => 'طبيب / موظف',
        ],

        'nurseries' => [
            'modules.nursery' => 'الحضانة',
            'modules.hr' => 'معلمات الحضانة',
            'modules.finance' => 'الاشتراكات والمحاسبة',
            'modules.pos' => 'متجر الحضانة',
            'entities.customer' => 'ولي الأمر',
            'entities.invoice' => 'فاتورة الاشتراك',
            'entities.employee' => 'معلمة / موظف',
            'entities.child' => 'طفل',
            'entities.classroom' => 'فصل',
        ],

        'fleet_agents' => [
            'modules.fleet' => 'العمليات الميدانية',
            'modules.inventory' => 'العهدة',
            'modules.warehouses' => 'مخازن العهدة',
            'modules.sales' => 'التحصيل الميداني',
            'modules.crm' => 'المناديب والعملاء',
            'modules.pos' => 'كاشير المندوب',
            'entities.customer' => 'عميل المندوب',
            'entities.agent' => 'المندوب',
            'entities.warehouse' => 'عهدة',
            'entities.item' => 'بضاعة العهدة',
            'entities.invoice' => 'إيصال تحصيل',
        ],

        'full_erp' => [
            'modules.finance' => 'المالية',
            'modules.inventory' => 'المخزون',
            'modules.sales' => 'المبيعات',
            'modules.purchases' => 'المشتريات',
            'modules.manufacturing' => 'التصنيع',
            'modules.hr' => 'الموارد البشرية',
            'modules.crm' => 'إدارة العملاء',
            'modules.pos' => 'نقاط البيع',
            'modules.clinic' => 'العيادة',
            'modules.services' => 'الخدمات',
        ],

    ],

];
