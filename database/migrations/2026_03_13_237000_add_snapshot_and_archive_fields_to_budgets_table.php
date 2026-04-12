<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('budgets', function (Blueprint $table) {
            if (!Schema::hasColumn('budgets', 'final_snapshot')) {
                $table->json('final_snapshot')->nullable()->after('status');
            }
            if (!Schema::hasColumn('budgets', 'closed_at')) {
                $table->timestamp('closed_at')->nullable()->after('final_snapshot');
            }
            if (!Schema::hasColumn('budgets', 'archived_at')) {
                $table->timestamp('archived_at')->nullable()->after('closed_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('budgets', function (Blueprint $table) {
            if (Schema::hasColumn('budgets', 'archived_at')) {
                $table->dropColumn('archived_at');
            }
            if (Schema::hasColumn('budgets', 'closed_at')) {
                $table->dropColumn('closed_at');
            }
            if (Schema::hasColumn('budgets', 'final_snapshot')) {
                $table->dropColumn('final_snapshot');
            }
        });
    }
};

