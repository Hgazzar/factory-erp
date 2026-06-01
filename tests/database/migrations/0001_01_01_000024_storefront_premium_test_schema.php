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
                foreach ([
                    'image_url' => 'string',
                    'compare_at_price' => 'decimal',
                    'avg_rating' => 'decimal',
                    'review_count' => 'integer',
                    'is_featured' => 'boolean',
                    'is_trending' => 'boolean',
                    'is_bestseller' => 'boolean',
                    'seo_title' => 'string',
                    'seo_description' => 'text',
                    'gallery_urls' => 'json',
                ] as $col => $type) {
                    if (! Schema::hasColumn('pos_products', $col)) {
                        if ($col === 'image_url') {
                            $table->string('image_url', 512)->nullable();
                        } elseif ($col === 'compare_at_price') {
                            $table->decimal('compare_at_price', 15, 4)->nullable();
                        } elseif ($col === 'avg_rating') {
                            $table->decimal('avg_rating', 3, 2)->default(0);
                        } elseif ($col === 'review_count') {
                            $table->unsignedInteger('review_count')->default(0);
                        } elseif (str_starts_with($col, 'is_')) {
                            $table->boolean($col)->default(false);
                        } elseif ($col === 'seo_description') {
                            $table->text('seo_description')->nullable();
                        } elseif ($col === 'gallery_urls') {
                            $table->json('gallery_urls')->nullable();
                        } else {
                            $table->string($col)->nullable();
                        }
                    }
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
            });
        }

        if (! Schema::hasTable('store_coupons')) {
            Schema::create('store_coupons', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_user_id')->constrained('users')->cascadeOnDelete();
                $table->string('code', 32);
                $table->string('type', 16);
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

        if (Schema::hasTable('pos_sales')) {
            Schema::table('pos_sales', function (Blueprint $table) {
                if (! Schema::hasColumn('pos_sales', 'coupon_code')) {
                    $table->string('coupon_code', 32)->nullable();
                }
                if (! Schema::hasColumn('pos_sales', 'discount_amount')) {
                    $table->decimal('discount_amount', 15, 4)->default(0);
                }
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('store_coupons');
        Schema::dropIfExists('store_banners');
    }
};
