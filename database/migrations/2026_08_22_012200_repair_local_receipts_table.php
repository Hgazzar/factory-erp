<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Local schema repair: finance receipts table missing from factory_erp drift.
 * Original create migration exists (2026_03_01_200200) but was never applied on this DB.
 * Idempotent — safe to re-run.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('receipts')) {
            Schema::create('receipts', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->foreignId('customer_id')->nullable()->constrained('customers')->nullOnDelete();
                $table->date('date');
                $table->string('reference', 50)->nullable();
                $table->decimal('amount', 15, 4)->default(0);
                $table->foreignId('journal_entry_id')->nullable()->constrained('journal_entries')->nullOnDelete();
                $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
                $table->timestamps();

                $table->index(['user_id', 'date']);
            });

            return;
        }

        if (! Schema::hasColumn('receipts', 'user_id')) {
            Schema::table('receipts', function (Blueprint $table) {
                $table->foreignId('user_id')->after('id')->constrained('users')->cascadeOnDelete();
            });
        }
    }

    public function down(): void
    {
        // Keep table — repair migrations are not rolled back on shared local DBs.
    }
};
