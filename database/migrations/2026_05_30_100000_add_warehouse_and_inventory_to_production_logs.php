<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('production_logs', function (Blueprint $table): void {
            if (! Schema::hasColumn('production_logs', 'warehouse_id')) {
                $table->foreignId('warehouse_id')
                    ->nullable()
                    ->after('item_id')
                    ->constrained('warehouses')
                    ->nullOnDelete()
                    ->comment('مستودع صرف الخام وإدخال التام');
            }
            if (! Schema::hasColumn('production_logs', 'inventory_synced_at')) {
                $table->timestamp('inventory_synced_at')->nullable()->after('updated_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('production_logs', function (Blueprint $table): void {
            if (Schema::hasColumn('production_logs', 'inventory_synced_at')) {
                $table->dropColumn('inventory_synced_at');
            }
            if (Schema::hasColumn('production_logs', 'warehouse_id')) {
                $table->dropConstrainedForeignId('warehouse_id');
            }
        });
    }
};
