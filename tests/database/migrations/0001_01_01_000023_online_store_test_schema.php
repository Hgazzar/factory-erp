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
                $table->longText('return_policy')->nullable();
                $table->longText('track_order_help')->nullable();
                $table->longText('privacy_policy')->nullable();
                $table->string('social_facebook', 255)->nullable();
                $table->string('social_instagram', 255)->nullable();
                $table->string('social_twitter', 255)->nullable();
                $table->string('social_whatsapp', 64)->nullable();
                $table->foreignId('default_pos_device_id')->nullable();
                $table->timestamps();
            });
        }

        if (Schema::hasTable('pos_products') && ! Schema::hasColumn('pos_products', 'is_published_online')) {
            Schema::table('pos_products', function (Blueprint $table) {
                $table->boolean('is_published_online')->default(false)->after('is_active');
            });
        }

        if (Schema::hasTable('pos_sales')) {
            Schema::table('pos_sales', function (Blueprint $table) {
                if (! Schema::hasColumn('pos_sales', 'sale_channel')) {
                    $table->string('sale_channel', 32)->nullable();
                }
                if (! Schema::hasColumn('pos_sales', 'customer_name')) {
                    $table->string('customer_name')->nullable();
                }
                if (! Schema::hasColumn('pos_sales', 'customer_phone')) {
                    $table->string('customer_phone', 32)->nullable();
                }
                if (! Schema::hasColumn('pos_sales', 'customer_address')) {
                    $table->text('customer_address')->nullable();
                }
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('tenant_store_settings');
    }
};
