<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stock_transfers', function (Blueprint $table) {
            $table->date('expected_arrival_date')->nullable()->after('transfer_date')->comment('تاريخ الوصول المتوقع');
        });

        Schema::table('stock_transfer_items', function (Blueprint $table) {
            $table->decimal('unit_cost', 15, 4)->default(0)->after('quantity')->comment('تكلفة الوحدة عند التحويل من بيانات الصنف');
        });
    }

    public function down(): void
    {
        Schema::table('stock_transfers', function (Blueprint $table) {
            $table->dropColumn('expected_arrival_date');
        });
        Schema::table('stock_transfer_items', function (Blueprint $table) {
            $table->dropColumn('unit_cost');
        });
    }
};
