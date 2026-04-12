<?php

return [
    /*
    | أسباب التسوية — للمحاسب لتوجيه الخسارة/الكسب للحساب الصحيح
    */
    'adjustment_reasons' => [
        'damage' => 'تلف',
        'gifts' => 'هدايا',
        'samples' => 'عينات',
        'count_error' => 'خطأ جرد',
        'expired' => 'منتهي الصلاحية',
        'other' => 'أخرى',
    ],

    'adjustment_types' => [
        'add' => 'إضافة كمية',
        'deduct' => 'خصم كمية',
    ],

    'audit_types' => [
        'full' => 'كلي (جميع الأصناف)',
        'partial' => 'جزئي (تصنيف معين)',
    ],

    'audit_categories' => [
        'raw_material' => 'مواد خام',
        'finished_good' => 'منتج تام',
        'service' => 'خدمة',
    ],

    'audit_statuses' => [
        'draft' => 'مسودة',
        'approved' => 'معتمد',
    ],
];
