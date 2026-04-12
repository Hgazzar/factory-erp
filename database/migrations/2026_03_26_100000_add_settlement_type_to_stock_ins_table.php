<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('stock_ins') || Schema::hasColumn('stock_ins', 'settlement_type')) {
            return;
        }
        Schema::table('stock_ins', function (Blueprint $table) {
            $table->string('settlement_type', 20)->default('on_account')->after('supplier_id');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('stock_ins') || ! Schema::hasColumn('stock_ins', 'settlement_type')) {
            return;
        }
        Schema::table('stock_ins', function (Blueprint $table) {
            $table->dropColumn('settlement_type');
        });
    }
};
