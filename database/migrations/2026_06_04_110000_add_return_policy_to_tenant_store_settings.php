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
            return;
        }

        Schema::table('tenant_store_settings', function (Blueprint $table): void {
            if (! Schema::hasColumn('tenant_store_settings', 'return_policy')) {
                $table->longText('return_policy')->nullable()->after('shipping_policy');
            }
            if (! Schema::hasColumn('tenant_store_settings', 'track_order_help')) {
                $table->longText('track_order_help')->nullable()->after('return_policy');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('tenant_store_settings')) {
            return;
        }

        Schema::table('tenant_store_settings', function (Blueprint $table): void {
            if (Schema::hasColumn('tenant_store_settings', 'track_order_help')) {
                $table->dropColumn('track_order_help');
            }
            if (Schema::hasColumn('tenant_store_settings', 'return_policy')) {
                $table->dropColumn('return_policy');
            }
        });
    }
};
