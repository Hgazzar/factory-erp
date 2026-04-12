<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('budget_items', function (Blueprint $table) {
            if (Schema::hasColumn('budget_items', 'budget_id') && Schema::hasColumn('budget_items', 'account_id')) {
                $table->dropUnique('budget_items_budget_id_account_id_unique');
            }

            if (! Schema::hasColumn('budget_items', 'cost_center_id')) {
                $table->foreignId('cost_center_id')->nullable()->after('account_id')->constrained('cost_centers')->nullOnDelete();
            }

            if (! Schema::hasColumn('budget_items', 'monthly_amounts')) {
                $table->json('monthly_amounts')->nullable()->after('planned_amount');
            }
        });
    }

    public function down(): void
    {
        Schema::table('budget_items', function (Blueprint $table) {
            if (Schema::hasColumn('budget_items', 'monthly_amounts')) {
                $table->dropColumn('monthly_amounts');
            }
            if (Schema::hasColumn('budget_items', 'cost_center_id')) {
                $table->dropConstrainedForeignId('cost_center_id');
            }
            $table->unique(['budget_id', 'account_id']);
        });
    }
};

