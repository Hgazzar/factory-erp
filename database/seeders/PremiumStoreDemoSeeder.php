<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\PosProduct;
use App\Models\PosProductCategory;
use App\Models\StoreBanner;
use App\Models\StoreCoupon;
use App\Models\TenantStoreSetting;
use App\Models\User;
use Illuminate\Database\Seeder;

final class PremiumStoreDemoSeeder extends Seeder
{
    public function run(?int $tenantUserId = null): void
    {
        $tenantUserId ??= (int) User::query()->orderBy('id')->value('id');
        if ($tenantUserId < 1) {
            $this->command?->warn('No tenant user found for PremiumStoreDemoSeeder.');

            return;
        }

        TenantStoreSetting::forTenant($tenantUserId)->update([
            'is_store_enabled' => true,
            'hero_title' => 'مجموعة أكواد الفاخرة',
            'hero_subtitle' => 'تجربة تسوق بمعايير عالمية',
            'hero_offer_text' => 'خصم 20% — عرض محدود',
        ]);

        if (StoreBanner::query()->where('tenant_user_id', $tenantUserId)->doesntExist()) {
            $slides = [
                ['title' => 'مجموعة الربيع', 'subtitle' => 'أناقة عصرية بلمسة فاخرة', 'image' => 'photo-1441986300917-64674bd600d8'],
                ['title' => 'إكسسوارات مميزة', 'subtitle' => 'تفاصيل تصنع الفرق', 'image' => 'photo-1483985988355-763728e3685b'],
            ];
            foreach ($slides as $i => $slide) {
                StoreBanner::query()->create([
                    'tenant_user_id' => $tenantUserId,
                    'title' => $slide['title'],
                    'subtitle' => $slide['subtitle'],
                    'cta_label' => 'تسوق الآن',
                    'image_url' => "https://images.unsplash.com/{$slide['image']}?w=1600&q=80&auto=format&fit=crop",
                    'sort_order' => $i,
                    'is_active' => true,
                ]);
            }
        }

        if (StoreCoupon::query()->where('tenant_user_id', $tenantUserId)->where('code', 'AKWAD20')->doesntExist()) {
            StoreCoupon::query()->create([
                'tenant_user_id' => $tenantUserId,
                'code' => 'AKWAD20',
                'type' => StoreCoupon::TYPE_PERCENT,
                'value' => 20,
                'min_cart_subtotal' => 100,
                'max_uses' => 500,
                'is_active' => true,
            ]);
        }

        $publishedCount = PosProduct::withoutGlobalScopes()
            ->where('user_id', $tenantUserId)
            ->where('is_published_online', true)
            ->count();

        if ($publishedCount >= 8) {
            $this->command?->info("Tenant #{$tenantUserId} already has {$publishedCount} published products.");

            return;
        }

        $category = PosProductCategory::query()->firstOrCreate(
            ['user_id' => $tenantUserId, 'code' => 'PREMIUM'],
            ['name' => 'مختارات فاخرة', 'is_active' => true],
        );

        $catalog = [
            ['name' => 'حقيبة جلد إيطالي', 'price' => 890, 'sale' => 712, 'rating' => 4.8, 'reviews' => 124, 'img' => 'photo-1548036328-c9fa89d128fa', 'featured' => true],
            ['name' => 'ساعة كلاسيكية ذهبية', 'price' => 1250, 'sale' => 999, 'rating' => 4.9, 'reviews' => 89, 'img' => 'photo-1523275335684-37898b6baf30', 'trending' => true],
            ['name' => 'عطر فاخر 100مل', 'price' => 420, 'sale' => 336, 'rating' => 4.7, 'reviews' => 210, 'img' => 'photo-1541643600919-78b084683601', 'bestseller' => true],
            ['name' => 'نظارات شمسية تيتانيوم', 'price' => 560, 'sale' => 448, 'rating' => 4.6, 'reviews' => 67, 'img' => 'photo-1572635196237-14b492f7fedd', 'featured' => true],
            ['name' => 'حذاء رياضي فاخر', 'price' => 680, 'sale' => 544, 'rating' => 4.5, 'reviews' => 156, 'img' => 'photo-1542291026-7eec264c27ff', 'trending' => true],
            ['name' => 'وشاح كشمير', 'price' => 320, 'sale' => 256, 'rating' => 4.8, 'reviews' => 43, 'img' => 'photo-1520903922593-1d4d0ddfba1e', 'bestseller' => true],
            ['name' => 'سماعات لاسلكية برو', 'price' => 750, 'sale' => 599, 'rating' => 4.7, 'reviews' => 312, 'img' => 'photo-1505740420928-5e560c06d30e', 'featured' => true],
            ['name' => 'محفظة جلدية رفيعة', 'price' => 280, 'sale' => 224, 'rating' => 4.4, 'reviews' => 98, 'img' => 'photo-1627123424574-72475859493d', 'trending' => true],
        ];

        foreach ($catalog as $i => $item) {
            $sku = 'PRM-'.str_pad((string) ($i + 1), 3, '0', STR_PAD_LEFT);
            if (PosProduct::withoutGlobalScopes()->where('user_id', $tenantUserId)->where('sku', $sku)->exists()) {
                continue;
            }

            PosProduct::query()->create([
                'user_id' => $tenantUserId,
                'pos_product_category_id' => $category->id,
                'name' => $item['name'],
                'sku' => $sku,
                'description' => 'منتج عرض توضيحي بجودة فاخرة — من مجموعة أكواد Premium.',
                'cost_price' => round($item['sale'] * 0.5, 2),
                'sale_price' => $item['sale'],
                'compare_at_price' => $item['price'],
                'vat_percent' => 15,
                'opening_quantity' => 25,
                'current_quantity' => 25,
                'is_active' => true,
                'is_published_online' => true,
                'image_url' => "https://images.unsplash.com/{$item['img']}?w=800&q=80&auto=format&fit=crop",
                'gallery_urls' => [
                    "https://images.unsplash.com/{$item['img']}?w=1200&q=80&auto=format&fit=crop",
                ],
                'avg_rating' => $item['rating'],
                'review_count' => $item['reviews'],
                'is_featured' => (bool) ($item['featured'] ?? false),
                'is_trending' => (bool) ($item['trending'] ?? false),
                'is_bestseller' => (bool) ($item['bestseller'] ?? false),
                'seo_title' => $item['name'].' | '.$category->name,
                'seo_description' => 'تسوق '.$item['name'].' بأفضل سعر — تجربة أكواد الفاخرة.',
            ]);
        }

        $this->command?->info("Premium store demo seeded for tenant #{$tenantUserId}.");
    }
}
