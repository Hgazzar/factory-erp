<?php

/**
 * قيم النماذج الموحّدة لشاشة عميل CRM محتمل (قوائم منسدلة قابلة للبحث).
 */
return [
    'sources' => [
        ['value' => 'website', 'label' => 'الموقع الإلكتروني'],
        ['value' => 'social', 'label' => 'وسائل التواصل الاجتماعي'],
        ['value' => 'referral', 'label' => 'إحالة'],
        ['value' => 'exhibition', 'label' => 'معرض / فعالية'],
        ['value' => 'cold_call', 'label' => 'اتصال بارِد'],
        ['value' => 'partner', 'label' => 'شريك'],
        ['value' => 'other', 'label' => 'أخرى'],
    ],
    'sectors' => [
        ['value' => '', 'label' => 'لا شيء'],
        ['value' => 'retail', 'label' => 'التجزئة'],
        ['value' => 'manufacturing', 'label' => 'التصنيع'],
        ['value' => 'services', 'label' => 'الخدمات'],
        ['value' => 'technology', 'label' => 'التقنية'],
        ['value' => 'healthcare', 'label' => 'الرعاية الصحية'],
        ['value' => 'construction', 'label' => 'البناء والتشييد'],
        ['value' => 'education', 'label' => 'التعليم'],
        ['value' => 'other', 'label' => 'أخرى'],
    ],
    'company_sizes' => [
        ['value' => '', 'label' => 'لا شيء'],
        ['value' => '1-10', 'label' => '1 – 10 موظفين'],
        ['value' => '11-50', 'label' => '11 – 50'],
        ['value' => '51-200', 'label' => '51 – 200'],
        ['value' => '201-500', 'label' => '201 – 500'],
        ['value' => '501+', 'label' => '501 فأكثر'],
    ],
];
