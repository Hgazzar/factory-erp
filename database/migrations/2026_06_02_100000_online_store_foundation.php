<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('tenant_store_settings')) {
            Schema::create('tenant_store_settings', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_user_id')->unique()->constrained('users')->cascadeOnDelete();
                $table->boolean('is_store_enabled')->default(true);
                $table->string('hero_title')->nullable();
                $table->string('hero_subtitle')->nullable();
                $table->text('hero_offer_text')->nullable();
                $table->longText('about_us')->nullable();
                $table->longText('contact_us')->nullable();
                $table->longText('faq')->nullable();
                $table->longText('shipping_policy')->nullable();
                $table->longText('privacy_policy')->nullable();
                $table->string('social_facebook', 255)->nullable();
                $table->string('social_instagram', 255)->nullable();
                $table->string('social_twitter', 255)->nullable();
                $table->string('social_whatsapp', 64)->nullable();
                $table->foreignId('default_pos_device_id')->nullable()->constrained('pos_devices')->nullOnDelete();
                $table->timestamps();
            });
        }

        if (Schema::hasTable('pos_products') && ! Schema::hasColumn('pos_products', 'is_published_online')) {
            Schema::table('pos_products', function (Blueprint $table) {
                $table->boolean('is_published_online')->default(false)->after('is_active');
                $table->index(['user_id', 'is_published_online', 'is_active'], 'pos_products_online_catalog_idx');
            });
        }

        if (Schema::hasTable('pos_sales')) {
            Schema::table('pos_sales', function (Blueprint $table) {
                if (! Schema::hasColumn('pos_sales', 'sale_channel')) {
                    $table->string('sale_channel', 32)->nullable()->after('payment_method');
                }
                if (! Schema::hasColumn('pos_sales', 'customer_name')) {
                    $table->string('customer_name')->nullable()->after('sale_channel');
                }
                if (! Schema::hasColumn('pos_sales', 'customer_phone')) {
                    $table->string('customer_phone', 32)->nullable()->after('customer_name');
                }
                if (! Schema::hasColumn('pos_sales', 'customer_address')) {
                    $table->text('customer_address')->nullable()->after('customer_phone');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('pos_sales')) {
            Schema::table('pos_sales', function (Blueprint $table) {
                foreach (['sale_channel', 'customer_name', 'customer_phone', 'customer_address'] as $col) {
                    if (Schema::hasColumn('pos_sales', $col)) {
                        $table->dropColumn($col);
                    }
                }
            });
        }

        if (Schema::hasTable('pos_products') && Schema::hasColumn('pos_products', 'is_published_online')) {
            Schema::table('pos_products', function (Blueprint $table) {
                $table->dropIndex('pos_products_online_catalog_idx');
                $table->dropColumn('is_published_online');
            });
        }

        Schema::dropIfExists('tenant_store_settings');
    }
};
