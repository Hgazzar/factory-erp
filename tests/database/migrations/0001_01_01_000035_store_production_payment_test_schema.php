<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('company_settings') && ! Schema::hasColumn('company_settings', 'country_code')) {
            Schema::table('company_settings', function (Blueprint $table): void {
                $table->string('country_code', 2)->nullable();
            });
        }

        if (Schema::hasTable('tenant_store_settings')) {
            Schema::table('tenant_store_settings', function (Blueprint $table): void {
                foreach ([
                    'cod_enabled' => fn () => $table->boolean('cod_enabled')->default(true),
                    'manual_transfer_enabled' => fn () => $table->boolean('manual_transfer_enabled')->default(false),
                    'tamara_enabled' => fn () => $table->boolean('tamara_enabled')->default(false),
                    'tabby_enabled' => fn () => $table->boolean('tabby_enabled')->default(false),
                    'paymob_integration_id' => fn () => $table->string('paymob_integration_id', 64)->nullable(),
                    'paymob_hmac_secret' => fn () => $table->string('paymob_hmac_secret', 512)->nullable(),
                    'tamara_api_token' => fn () => $table->string('tamara_api_token', 512)->nullable(),
                    'tabby_public_key' => fn () => $table->string('tabby_public_key', 512)->nullable(),
                    'tabby_secret_key' => fn () => $table->string('tabby_secret_key', 512)->nullable(),
                ] as $col => $callback) {
                    if (! Schema::hasColumn('tenant_store_settings', $col)) {
                        $callback();
                    }
                }
            });
        }

        if (Schema::hasTable('pos_sales')) {
            Schema::table('pos_sales', function (Blueprint $table): void {
                if (! Schema::hasColumn('pos_sales', 'payment_receipt_path')) {
                    $table->string('payment_receipt_path', 512)->nullable();
                }
                if (! Schema::hasColumn('pos_sales', 'whatsapp_received_notified_at')) {
                    $table->timestamp('whatsapp_received_notified_at')->nullable();
                }
            });
        }
    }

    public function down(): void
    {
        // test schema
    }
};
