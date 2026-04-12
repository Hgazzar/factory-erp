<?php

namespace Database\Seeders;

use App\Models\Unit;
use Illuminate\Database\Seeder;

class UnitSeeder extends Seeder
{
    /**
     * وحدات قياس أساسية لموديول الأصناف.
     */
    public function run(): void
    {
        $units = [
            // وحدات عدّ
            ['code' => 'EA', 'name_ar' => 'وحدة', 'name_en' => 'Each', 'symbol' => 'وحدة', 'conversion_factor' => 1],
            ['code' => 'PCS', 'name_ar' => 'قطعة', 'name_en' => 'Piece', 'symbol' => 'قطعة', 'conversion_factor' => 1],
            ['code' => 'PAIR', 'name_ar' => 'زوج', 'name_en' => 'Pair', 'symbol' => 'زوج', 'conversion_factor' => 1],
            ['code' => 'SET', 'name_ar' => 'طقم', 'name_en' => 'Set', 'symbol' => 'طقم', 'conversion_factor' => 1],

            // وحدات تعبئة وتغليف
            ['code' => 'PKG', 'name_ar' => 'عبوة', 'name_en' => 'Package', 'symbol' => 'عبوة', 'conversion_factor' => 1],
            ['code' => 'BAG', 'name_ar' => 'كيس', 'name_en' => 'Bag', 'symbol' => 'كيس', 'conversion_factor' => 1],
            ['code' => 'SACK', 'name_ar' => 'شوال', 'name_en' => 'Sack', 'symbol' => 'شوال', 'conversion_factor' => 1],
            ['code' => 'BOTTLE', 'name_ar' => 'زجاجة', 'name_en' => 'Bottle', 'symbol' => 'زجاجة', 'conversion_factor' => 1],
            ['code' => 'CAN', 'name_ar' => 'علبة', 'name_en' => 'Can', 'symbol' => 'علبة', 'conversion_factor' => 1],
            ['code' => 'CTN', 'name_ar' => 'كرتونة', 'name_en' => 'Carton', 'symbol' => 'كرتونة', 'conversion_factor' => 1],
            ['code' => 'BOX', 'name_ar' => 'صندوق', 'name_en' => 'Box', 'symbol' => 'صندوق', 'conversion_factor' => 1],
            ['code' => 'PAL', 'name_ar' => 'بالتة', 'name_en' => 'Pallet', 'symbol' => 'بالتة', 'conversion_factor' => 1],

            // وحدات وزن
            ['code' => 'MG', 'name_ar' => 'ملّيغرام', 'name_en' => 'Milligram', 'symbol' => 'مغ', 'conversion_factor' => 1],
            ['code' => 'G', 'name_ar' => 'جرام', 'name_en' => 'Gram', 'symbol' => 'جم', 'conversion_factor' => 1],
            ['code' => 'KG', 'name_ar' => 'كيلوغرام', 'name_en' => 'Kilogram', 'symbol' => 'كجم', 'conversion_factor' => 1],
            ['code' => 'TON', 'name_ar' => 'طن', 'name_en' => 'Ton', 'symbol' => 'طن', 'conversion_factor' => 1],

            // وحدات طول
            ['code' => 'MM', 'name_ar' => 'ملّيمتر', 'name_en' => 'Millimeter', 'symbol' => 'مم', 'conversion_factor' => 1],
            ['code' => 'CM', 'name_ar' => 'سنتيمتر', 'name_en' => 'Centimeter', 'symbol' => 'سم', 'conversion_factor' => 1],
            ['code' => 'M', 'name_ar' => 'متر', 'name_en' => 'Meter', 'symbol' => 'م', 'conversion_factor' => 1],
            ['code' => 'KM', 'name_ar' => 'كيلومتر', 'name_en' => 'Kilometer', 'symbol' => 'كم', 'conversion_factor' => 1],

            // وحدات مساحة
            ['code' => 'CM2', 'name_ar' => 'سنتيمتر مربع', 'name_en' => 'Square Centimeter', 'symbol' => 'سم²', 'conversion_factor' => 1],
            ['code' => 'M2', 'name_ar' => 'متر مربع', 'name_en' => 'Square Meter', 'symbol' => 'م²', 'conversion_factor' => 1],

            // وحدات حجم
            ['code' => 'CM3', 'name_ar' => 'سنتيمتر مكعب', 'name_en' => 'Cubic Centimeter', 'symbol' => 'سم³', 'conversion_factor' => 1],
            ['code' => 'M3', 'name_ar' => 'متر مكعب', 'name_en' => 'Cubic Meter', 'symbol' => 'م³', 'conversion_factor' => 1],

            // وحدات سوائل
            ['code' => 'ML', 'name_ar' => 'ملّيلتر', 'name_en' => 'Millilitre', 'symbol' => 'مل', 'conversion_factor' => 1],
            ['code' => 'L', 'name_ar' => 'لتر', 'name_en' => 'Litre', 'symbol' => 'لتر', 'conversion_factor' => 1],

            // وحدات وقت (عند الحاجة)
            ['code' => 'SEC', 'name_ar' => 'ثانية', 'name_en' => 'Second', 'symbol' => 'ث', 'conversion_factor' => 1],
            ['code' => 'MIN', 'name_ar' => 'دقيقة', 'name_en' => 'Minute', 'symbol' => 'د', 'conversion_factor' => 1],
            ['code' => 'HR', 'name_ar' => 'ساعة', 'name_en' => 'Hour', 'symbol' => 'س', 'conversion_factor' => 1],
            ['code' => 'DAY', 'name_ar' => 'يوم', 'name_en' => 'Day', 'symbol' => 'يوم', 'conversion_factor' => 1],
        ];

        foreach ($units as $data) {
            Unit::updateOrCreate(
                ['code' => $data['code']],
                array_merge($data, ['is_active' => true])
            );
        }
    }
}
