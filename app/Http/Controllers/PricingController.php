<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\View\View;

final class PricingController extends Controller
{
    public function index(): View
    {
        return view('pricing', [
            'plans' => $this->plans(),
            'comparison' => $this->comparisonMatrix(),
        ]);
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function plans(): array
    {
        return [
            [
                'key' => 'basic',
                'name' => 'Basic Retail',
                'name_ar' => 'التجزئة الأساسية',
                'tagline' => 'للمتاجر الصغيرة والبوتيكات',
                'monthly' => 299,
                'yearly' => 2990,
                'popular' => false,
                'modules' => [
                    'نقاط البيع (POS)',
                    'المخزون الأساسي',
                    'فواتير المبيعات',
                    'متجر إلكتروني',
                    'تقارير يومية',
                ],
            ],
            [
                'key' => 'plus',
                'name' => 'Premium Plus',
                'name_ar' => 'بريميوم بلس',
                'tagline' => 'الخيار المثالي للنمو السريع',
                'monthly' => 599,
                'yearly' => 5990,
                'popular' => true,
                'modules' => [
                    'كل ميزات Basic',
                    'المحاسبة والقيود',
                    'إدارة العملاء (CRM)',
                    'المشتريات والموردون',
                    'لوحة تحكم متقدمة',
                    'متجر Premium',
                ],
            ],
            [
                'key' => 'enterprise',
                'name' => 'Enterprise ERP',
                'name_ar' => 'المصانع والمؤسسات',
                'tagline' => 'حلول موديولية للمصانع والمجموعات',
                'monthly' => 1499,
                'yearly' => 14990,
                'popular' => false,
                'custom' => true,
                'modules' => [
                    'كل موديولات المنصة',
                    'التصنيع وقوائم المواد',
                    'الموارد البشرية والرواتب',
                    'تعدد الفروع',
                    'API و Sanctum',
                    'دعم أولوية',
                ],
            ],
        ];
    }

    /**
     * @return list<array{category: string, rows: list<array{feature: string, basic: string|bool, plus: string|bool, enterprise: string|bool>}>}>
     */
    private function comparisonMatrix(): array
    {
        $yes = true;
        $no = false;
        $partial = 'أساسي';

        return [
            [
                'category' => 'المحاسبة والمالية',
                'rows' => [
                    ['feature' => 'دليل الحسابات', 'basic' => $no, 'plus' => $yes, 'enterprise' => $yes],
                    ['feature' => 'القيود اليومية', 'basic' => $no, 'plus' => $yes, 'enterprise' => $yes],
                    ['feature' => 'تقارير الأرباح والخسائر', 'basic' => $partial, 'plus' => $yes, 'enterprise' => $yes],
                    ['feature' => 'الشيكات والبنوك', 'basic' => $no, 'plus' => $yes, 'enterprise' => $yes],
                ],
            ],
            [
                'category' => 'الموارد البشرية',
                'rows' => [
                    ['feature' => 'إدارة الموظفين', 'basic' => $no, 'plus' => $partial, 'enterprise' => $yes],
                    ['feature' => 'الحضور والانصراف', 'basic' => $no, 'plus' => $no, 'enterprise' => $yes],
                    ['feature' => 'مسير الرواتب', 'basic' => $no, 'plus' => $no, 'enterprise' => $yes],
                ],
            ],
            [
                'category' => 'الإنتاج والتصنيع',
                'rows' => [
                    ['feature' => 'قوائم المواد (BOM)', 'basic' => $no, 'plus' => $no, 'enterprise' => $yes],
                    ['feature' => 'أوامر الإنتاج', 'basic' => $no, 'plus' => $no, 'enterprise' => $yes],
                    ['feature' => 'تتبع الخامات', 'basic' => $no, 'plus' => $no, 'enterprise' => $yes],
                ],
            ],
            [
                'category' => 'التجارة الإلكترونية',
                'rows' => [
                    ['feature' => 'متجر إلكتروني /s/slug', 'basic' => $yes, 'plus' => $yes, 'enterprise' => $yes],
                    ['feature' => 'نقاط البيع POS', 'basic' => $yes, 'plus' => $yes, 'enterprise' => $yes],
                    ['feature' => 'CRM والولاء', 'basic' => $no, 'plus' => $yes, 'enterprise' => $yes],
                    ['feature' => 'كوبونات المتجر', 'basic' => $partial, 'plus' => $yes, 'enterprise' => $yes],
                ],
            ],
        ];
    }
}
