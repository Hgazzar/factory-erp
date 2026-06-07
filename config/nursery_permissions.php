<?php

declare(strict_types=1);

/**
 * صلاحيات موديول الحضانة — مفاتيح ثابتة ومسميات من الشاشات المرجعية.
 */
return [
    'groups' => [
        'login' => [
            'label' => 'تسجيل الدخول',
            'permissions' => [
                'login.app' => 'دخول التطبيق',
                'login.portal' => 'دخول البوابة',
            ],
        ],
        'employees' => [
            'label' => 'الموظفين',
            'permissions' => [
                'employees.manage' => 'إدارة',
                'employees.archive' => 'أرشفة',
                'employees.delete' => 'حذف',
            ],
        ],
        'children' => [
            'label' => 'الأطفال',
            'permissions' => [
                'children.manage' => 'إدارة',
                'children.archive' => 'أرشفة',
                'children.delete' => 'حذف',
                'children.parents_page' => 'صفحة أولياء الأمور',
                'children.files_page' => 'صفحة الملفات',
            ],
        ],
        'classrooms' => [
            'label' => 'الفصول',
            'permissions' => [
                'classrooms.assign_child' => 'تعيين طفل للفصل',
                'classrooms.manage' => 'إدارة',
                'classrooms.archive' => 'أرشفة',
            ],
        ],
        'units' => [
            'label' => 'الوحدات',
            'permissions' => [
                'units.manage' => 'إدارة',
                'units.archive' => 'أرشفة',
                'units.delete' => 'حذف',
            ],
        ],
        'scheduling' => [
            'label' => 'جدولة الدروس والأنشطة',
            'permissions' => [
                'scheduling.manage' => 'إدارة',
                'scheduling.delete' => 'حذف',
            ],
        ],
        'attendance' => [
            'label' => 'الحضور والانصراف',
            'permissions' => [
                'attendance.children' => 'إدارة حضور الأطفال',
                'attendance.staff' => 'إدارة حضور طاقم العمل',
            ],
        ],
        'subscriptions' => [
            'label' => 'الاشتراكات',
            'permissions' => [
                'subscriptions.manage' => 'إدارة',
            ],
        ],
        'settings' => [
            'label' => 'إعدادات الحضانة',
            'permissions' => [
                'settings.manage' => 'إدارة',
            ],
        ],
    ],

    /** قوالب افتراضية حسب دور التشغيل */
    'role_templates' => [
        'reception' => [
            'login.app',
            'children.manage',
            'children.parents_page',
            'children.files_page',
            'classrooms.assign_child',
            'attendance.children',
            'subscriptions.manage',
        ],
        'teacher' => [
            'login.app',
            'children.manage',
            'children.files_page',
            'classrooms.assign_child',
            'attendance.children',
        ],
    ],
];
