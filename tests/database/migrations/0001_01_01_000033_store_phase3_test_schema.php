<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('pos_sales')) {
            Schema::table('pos_sales', function (Blueprint $table): void {
                if (! Schema::hasColumn('pos_sales', 'collection_journal_entry_id')) {
                    $table->foreignId('collection_journal_entry_id')->nullable()->constrained('journal_entries')->nullOnDelete();
                }
                if (! Schema::hasColumn('pos_sales', 'payment_gateway_reference')) {
                    $table->string('payment_gateway_reference', 128)->nullable();
                }
                if (! Schema::hasColumn('pos_sales', 'delivered_at')) {
                    $table->timestamp('delivered_at')->nullable();
                }
            });
        }

        if (Schema::hasTable('tenant_store_settings')) {
            Schema::table('tenant_store_settings', function (Blueprint $table): void {
                if (! Schema::hasColumn('tenant_store_settings', 'online_payment_enabled')) {
                    $table->boolean('online_payment_enabled')->default(false);
                }
                if (! Schema::hasColumn('tenant_store_settings', 'online_payment_provider')) {
                    $table->string('online_payment_provider', 32)->nullable();
                }
                if (! Schema::hasColumn('tenant_store_settings', 'online_payment_mode')) {
                    $table->string('online_payment_mode', 16)->default('sandbox');
                }
                if (! Schema::hasColumn('tenant_store_settings', 'online_payment_public_key')) {
                    $table->string('online_payment_public_key', 512)->nullable();
                }
                if (! Schema::hasColumn('tenant_store_settings', 'online_payment_secret_key')) {
                    $table->string('online_payment_secret_key', 512)->nullable();
                }
            });
        }
    }

    public function down(): void
    {
        // test schema — no down
    }
};
