<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Local schema repair: remaining D1 Finance tables missing from factory_erp drift.
 * Idempotent — safe to re-run.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('bank_reconciliations')) {
            Schema::create('bank_reconciliations', function (Blueprint $table) {
                $table->id();
                $table->string('reconciliation_number', 100)->unique();
                $table->foreignId('account_id')->constrained('accounts')->cascadeOnUpdate()->restrictOnDelete();
                $table->date('statement_date');
                $table->decimal('statement_balance', 15, 2);
                $table->decimal('book_balance', 15, 2);
                $table->decimal('difference', 15, 2)->default(0);
                $table->string('status', 20)->default('draft');
                $table->timestamps();

                $table->index(['status', 'statement_date']);
                $table->index('account_id');
            });
        }

        if (! Schema::hasTable('tax_rates')) {
            Schema::create('tax_rates', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->string('code', 32);
                $table->string('name_ar');
                $table->string('name_en')->nullable();
                $table->decimal('rate_percent', 8, 4)->default(0);
                $table->foreignId('ledger_account_id')->nullable()->constrained('accounts')->nullOnDelete();
                $table->boolean('is_active')->default(true);
                $table->timestamps();

                $table->unique(['user_id', 'code']);
            });
        }

        if (! Schema::hasTable('payment_method_accounts')) {
            Schema::create('payment_method_accounts', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->string('method_key', 20);
                $table->foreignId('ledger_account_id')->constrained('accounts')->restrictOnDelete();
                $table->timestamps();

                $table->unique(['user_id', 'method_key']);
            });
        }
    }

    public function down(): void
    {
        // Keep tables — repair migrations are not rolled back on shared local DBs.
    }
};
