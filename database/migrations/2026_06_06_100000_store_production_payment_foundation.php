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
                $table->string('country_code', 2)->nullable()->after('currency_code');
            });
        }

        if (Schema::hasTable('tenant_store_settings')) {
            Schema::table('tenant_store_settings', function (Blueprint $table): void {
                if (! Schema::hasColumn('tenant_store_settings', 'cod_enabled')) {
                    $table->boolean('cod_enabled')->default(true)->after('is_store_enabled');
                }
                if (! Schema::hasColumn('tenant_store_settings', 'manual_transfer_enabled')) {
                    $table->boolean('manual_transfer_enabled')->default(false)->after('cod_enabled');
                }
                if (! Schema::hasColumn('tenant_store_settings', 'tamara_enabled')) {
                    $table->boolean('tamara_enabled')->default(false)->after('online_payment_secret_key');
                }
                if (! Schema::hasColumn('tenant_store_settings', 'tabby_enabled')) {
                    $table->boolean('tabby_enabled')->default(false)->after('tamara_enabled');
                }
                if (! Schema::hasColumn('tenant_store_settings', 'paymob_integration_id')) {
                    $table->string('paymob_integration_id', 64)->nullable()->after('tabby_enabled');
                }
                if (! Schema::hasColumn('tenant_store_settings', 'paymob_hmac_secret')) {
                    $table->string('paymob_hmac_secret', 512)->nullable()->after('paymob_integration_id');
                }
                if (! Schema::hasColumn('tenant_store_settings', 'tamara_api_token')) {
                    $table->string('tamara_api_token', 512)->nullable()->after('paymob_hmac_secret');
                }
                if (! Schema::hasColumn('tenant_store_settings', 'tabby_public_key')) {
                    $table->string('tabby_public_key', 512)->nullable()->after('tamara_api_token');
                }
                if (! Schema::hasColumn('tenant_store_settings', 'tabby_secret_key')) {
                    $table->string('tabby_secret_key', 512)->nullable()->after('tabby_public_key');
                }
            });
        }

        if (Schema::hasTable('pos_sales')) {
            Schema::table('pos_sales', function (Blueprint $table): void {
                if (! Schema::hasColumn('pos_sales', 'payment_receipt_path')) {
                    $table->string('payment_receipt_path', 512)->nullable()->after('payment_gateway_reference');
                }
                if (! Schema::hasColumn('pos_sales', 'whatsapp_received_notified_at')) {
                    $table->timestamp('whatsapp_received_notified_at')->nullable()->after('whatsapp_invoice_notified_at');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('company_settings') && Schema::hasColumn('company_settings', 'country_code')) {
            Schema::table('company_settings', function (Blueprint $table): void {
                $table->dropColumn('country_code');
            });
        }

        if (Schema::hasTable('tenant_store_settings')) {
            Schema::table('tenant_store_settings', function (Blueprint $table): void {
                foreach ([
                    'cod_enabled', 'manual_transfer_enabled', 'tamara_enabled', 'tabby_enabled',
                    'paymob_integration_id', 'paymob_hmac_secret', 'tamara_api_token',
                    'tabby_public_key', 'tabby_secret_key',
                ] as $col) {
                    if (Schema::hasColumn('tenant_store_settings', $col)) {
                        $table->dropColumn($col);
                    }
                }
            });
        }

        if (Schema::hasTable('pos_sales')) {
            Schema::table('pos_sales', function (Blueprint $table): void {
                foreach (['payment_receipt_path', 'whatsapp_received_notified_at'] as $col) {
                    if (Schema::hasColumn('pos_sales', $col)) {
                        $table->dropColumn($col);
                    }
                }
            });
        }
    }
};
