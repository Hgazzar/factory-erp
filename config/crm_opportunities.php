<?php

/**
 * مراحل الفرص (مفاتيح إنجليزية في قاعدة البيانات، عرض عربي في الواجهة).
 */
return [
    'stages' => [
        ['value' => 'draft', 'label' => 'مسودة'],
        ['value' => 'qualification', 'label' => 'تأهيل'],
        ['value' => 'proposal', 'label' => 'عرض سعر'],
        ['value' => 'negotiation', 'label' => 'تفاوض'],
        ['value' => 'closed_won', 'label' => 'مغلقة — فوز'],
        ['value' => 'closed_lost', 'label' => 'مغلقة — خسارة'],
    ],
];
