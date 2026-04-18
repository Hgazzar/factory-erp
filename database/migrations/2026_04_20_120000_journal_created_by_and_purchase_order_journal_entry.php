<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('journal_entries') && ! Schema::hasColumn('journal_entries', 'created_by')) {
            Schema::table('journal_entries', function (Blueprint $table): void {
                $table->foreignId('created_by')->nullable()->after('user_id')->constrained('users')->nullOnDelete();
            });
        }

        if (Schema::hasTable('purchase_orders') && ! Schema::hasColumn('purchase_orders', 'journal_entry_id')) {
            Schema::table('purchase_orders', function (Blueprint $table): void {
                $table->foreignId('journal_entry_id')->nullable()->after('status')->constrained('journal_entries')->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('purchase_orders') && Schema::hasColumn('purchase_orders', 'journal_entry_id')) {
            Schema::table('purchase_orders', function (Blueprint $table): void {
                $table->dropConstrainedForeignId('journal_entry_id');
            });
        }

        if (Schema::hasTable('journal_entries') && Schema::hasColumn('journal_entries', 'created_by')) {
            Schema::table('journal_entries', function (Blueprint $table): void {
                $table->dropConstrainedForeignId('created_by');
            });
        }
    }
};
