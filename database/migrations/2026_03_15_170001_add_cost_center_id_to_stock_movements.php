<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stock_movements', function (Blueprint $table) {
            $table->foreignId('cost_center_id')
                ->nullable()
                ->after('reference_id')
                ->constrained('cost_centers')
                ->nullOnDelete()
                ->comment('مركز التكلفة لاستخراج تقارير تكلفة الهالك لكل قسم');
        });
    }

    public function down(): void
    {
        Schema::table('stock_movements', function (Blueprint $table) {
            $table->dropConstrainedForeignId('cost_center_id');
        });
    }
};
