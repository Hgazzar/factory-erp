<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('payments') || ! Schema::hasTable('expense_categories')) {
            return;
        }

        Schema::table('payments', function (Blueprint $table) {
            if (! Schema::hasColumn('payments', 'expense_category_id')) {
                $after = Schema::hasColumn('payments', 'category_id') ? 'category_id' : 'expense_account_id';
                $table->foreignId('expense_category_id')
                    ->nullable()
                    ->after($after)
                    ->constrained('expense_categories')
                    ->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('payments') || ! Schema::hasColumn('payments', 'expense_category_id')) {
            return;
        }

        Schema::table('payments', function (Blueprint $table) {
            $table->dropForeign(['expense_category_id']);
        });
    }
};
