<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('manufacturing_runs', function (Blueprint $table) {
            $table->foreignId('bom_list_id')->nullable()->after('user_id')->constrained('bom_lists')->nullOnDelete();
            $table->foreignId('machine_id')->nullable()->after('warehouse_id')->constrained('machines')->nullOnDelete();
            $table->date('start_date')->nullable()->after('production_date');
            $table->date('due_date')->nullable()->after('start_date');
        });
    }

    public function down(): void
    {
        Schema::table('manufacturing_runs', function (Blueprint $table) {
            $table->dropConstrainedForeignIds(['bom_list_id', 'machine_id']);
            $table->dropColumn(['start_date', 'due_date']);
        });
    }
};
