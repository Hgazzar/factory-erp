<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * إضافة: المورد، نوع الخامة (الحد الأدنى للطلب min_stock موجود مسبقاً)
     */
    public function up(): void
    {
        Schema::table('items', function (Blueprint $table) {
            $table->string('supplier', 255)->nullable()->after('min_stock')->comment('المورد');
            $table->string('material_type', 100)->nullable()->after('supplier')->comment('نوع الخامة');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('items', function (Blueprint $table) {
            $table->dropColumn(['supplier', 'material_type']);
        });
    }
};
