<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * معدل إهلاك لكل وحدة منتَجة على الماكينة (يُضرب في كمية إخراج أمر العمل).
     */
    public function up(): void
    {
        Schema::table('machines', function (Blueprint $table) {
            $table->decimal('depreciation_rate_per_unit', 15, 4)->default(0)->after('is_active');
        });
    }

    public function down(): void
    {
        Schema::table('machines', function (Blueprint $table) {
            $table->dropColumn('depreciation_rate_per_unit');
        });
    }
};
