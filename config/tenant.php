<?php

declare(strict_types=1);

/**
 * إعدادات مشتركة لكل المستأجرين (نيش-محايد).
 */
return [

    'branding' => [
        'logo_directory' => 'tenant',
        'legacy_logo_prefixes' => ['tenant/', 'nursery/'],

        'defaults' => [
            'medical_clinics' => [
                'primary' => '#0d9488',
                'secondary' => '#ccfbf1',
            ],
            'nurseries' => [
                'primary' => '#0F766E',
                'secondary' => '#F0FDFA',
            ],
            'retail' => [
                'primary' => '#dc2626',
                'secondary' => '#fee2e2',
            ],
            'manufacturing' => [
                'primary' => '#2563eb',
                'secondary' => '#dbeafe',
            ],
            'fleet_agents' => [
                'primary' => '#7c3aed',
                'secondary' => '#ede9fe',
            ],
            '_default' => [
                'primary' => '#4f46e5',
                'secondary' => '#e0e7ff',
            ],
        ],
    ],

];
