<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * إضافة الكمية المخططة لكل وردية إنتاج
     */
    public function up(): void
    {
        Schema::table('production_shifts', function (Blueprint $table) {
            $table->decimal('planned_quantity', 15, 4)
                ->default(0)
                ->after('actual_end_at')
                ->comment('الكمية المخططة للوردية');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('production_shifts', function (Blueprint $table) {
            $table->dropColumn('planned_quantity');
        });
    }
};

