<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('pos_products')) {
            Schema::table('pos_products', function (Blueprint $table) {
                if (! Schema::hasColumn('pos_products', 'image_url')) {
                    $table->string('image_url', 512)->nullable()->after('description');
                }
                if (! Schema::hasColumn('pos_products', 'compare_at_price')) {
                    $table->decimal('compare_at_price', 15, 4)->nullable()->after('sale_price');
                }
                if (! Schema::hasColumn('pos_products', 'avg_rating')) {
                    $table->decimal('avg_rating', 3, 2)->default(0)->after('vat_percent');
                }
                if (! Schema::hasColumn('pos_products', 'review_count')) {
                    $table->unsignedInteger('review_count')->default(0)->after('avg_rating');
                }
                if (! Schema::hasColumn('pos_products', 'is_featured')) {
                    $table->boolean('is_featured')->default(false)->after('is_published_online');
                }
                if (! Schema::hasColumn('pos_products', 'is_trending')) {
                    $table->boolean('is_trending')->default(false)->after('is_featured');
                }
                if (! Schema::hasColumn('pos_products', 'is_bestseller')) {
                    $table->boolean('is_bestseller')->default(false)->after('is_trending');
                }
                if (! Schema::hasColumn('pos_products', 'seo_title')) {
                    $table->string('seo_title')->nullable()->after('is_bestseller');
                }
                if (! Schema::hasColumn('pos_products', 'seo_description')) {
                    $table->text('seo_description')->nullable()->after('seo_title');
                }
                if (! Schema::hasColumn('pos_products', 'gallery_urls')) {
                    $table->json('gallery_urls')->nullable()->after('image_url');
                }
            });
        }

        if (! Schema::hasTable('store_banners')) {
            Schema::create('store_banners', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_user_id')->constrained('users')->cascadeOnDelete();
                $table->string('title');
                $table->string('subtitle')->nullable();
                $table->string('cta_label', 64)->nullable();
                $table->string('cta_url', 512)->nullable();
                $table->string('image_url', 512);
                $table->unsignedSmallInteger('sort_order')->default(0);
                $table->boolean('is_active')->default(true);
                $table->timestamps();

                $table->index(['tenant_user_id', 'is_active', 'sort_order']);
            });
        }

        if (! Schema::hasTable('store_coupons')) {
            Schema::create('store_coupons', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_user_id')->constrained('users')->cascadeOnDelete();
                $table->string('code', 32);
                $table->string('type', 16); // fixed | percent
                $table->decimal('value', 15, 4);
                $table->decimal('min_cart_subtotal', 15, 4)->default(0);
                $table->unsignedInteger('max_uses')->nullable();
                $table->unsignedInteger('used_count')->default(0);
                $table->timestamp('starts_at')->nullable();
                $table->timestamp('expires_at')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();

                $table->unique(['tenant_user_id', 'code']);
            });
        }

        if (Schema::hasTable('pos_sales') && ! Schema::hasColumn('pos_sales', 'coupon_code')) {
            Schema::table('pos_sales', function (Blueprint $table) {
                $table->string('coupon_code', 32)->nullable()->after('customer_address');
                $table->decimal('discount_amount', 15, 4)->default(0)->after('coupon_code');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('pos_sales')) {
            Schema::table('pos_sales', function (Blueprint $table) {
                foreach (['coupon_code', 'discount_amount'] as $col) {
                    if (Schema::hasColumn('pos_sales', $col)) {
                        $table->dropColumn($col);
                    }
                }
            });
        }

        Schema::dropIfExists('store_coupons');
        Schema::dropIfExists('store_banners');
    }
};
