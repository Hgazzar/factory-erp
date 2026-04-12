<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            if (! Schema::hasColumn('payments', 'cost_center_id')) {
                $table->foreignId('cost_center_id')
                    ->nullable()
                    ->after('expense_account_id')
                    ->constrained('cost_centers')
                    ->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            if (Schema::hasColumn('payments', 'cost_center_id')) {
                $table->dropConstrainedForeignId('cost_center_id');
            }
        });
    }
};
