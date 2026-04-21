<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('manufacturing_run_lines', function (Blueprint $table) {
            $table->foreignId('bom_list_line_id')->nullable()->after('manufacturing_run_id')->constrained('bom_list_lines')->nullOnDelete();
            $table->decimal('planned_quantity', 15, 4)->nullable()->after('warehouse_id');
            $table->decimal('planned_scrap_percent', 8, 4)->nullable()->after('planned_quantity');
            $table->decimal('actual_scrap_percent', 8, 4)->nullable()->after('planned_scrap_percent');
        });
    }

    public function down(): void
    {
        Schema::table('manufacturing_run_lines', function (Blueprint $table) {
            $table->dropConstrainedForeignIds(['bom_list_line_id']);
            $table->dropColumn(['planned_quantity', 'planned_scrap_percent', 'actual_scrap_percent']);
        });
    }
};
