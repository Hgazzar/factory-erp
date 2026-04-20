<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('fixed_assets')) {
            Schema::table('fixed_assets', function (Blueprint $table): void {
                if (! Schema::hasColumn('fixed_assets', 'ledger_account_id')) {
                    $table->foreignId('ledger_account_id')
                        ->nullable()
                        ->after('cost_center_id')
                        ->constrained('accounts')
                        ->nullOnDelete();
                }
                if (! Schema::hasColumn('fixed_assets', 'payment_method')) {
                    $table->string('payment_method', 20)->nullable()->after('ledger_account_id');
                }
                if (! Schema::hasColumn('fixed_assets', 'bank_account_id')) {
                    $table->foreignId('bank_account_id')
                        ->nullable()
                        ->after('payment_method')
                        ->constrained('bank_accounts')
                        ->nullOnDelete();
                }
                if (! Schema::hasColumn('fixed_assets', 'journal_entry_id')) {
                    $table->foreignId('journal_entry_id')
                        ->nullable()
                        ->after('bank_account_id')
                        ->constrained('journal_entries')
                        ->nullOnDelete();
                }
                if (! Schema::hasColumn('fixed_assets', 'source_payment_id')) {
                    $table->foreignId('source_payment_id')
                        ->nullable()
                        ->after('journal_entry_id')
                        ->constrained('payments')
                        ->nullOnDelete();
                }
            });
        }

        if (Schema::hasTable('payments') && ! Schema::hasColumn('payments', 'fixed_asset_id')) {
            Schema::table('payments', function (Blueprint $table): void {
                $table->foreignId('fixed_asset_id')
                    ->nullable()
                    ->after('bank_account_id')
                    ->constrained('fixed_assets')
                    ->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('payments') && Schema::hasColumn('payments', 'fixed_asset_id')) {
            Schema::table('payments', function (Blueprint $table): void {
                $table->dropForeign(['fixed_asset_id']);
                $table->dropColumn('fixed_asset_id');
            });
        }

        if (Schema::hasTable('fixed_assets')) {
            Schema::table('fixed_assets', function (Blueprint $table): void {
                if (Schema::hasColumn('fixed_assets', 'source_payment_id')) {
                    $table->dropForeign(['source_payment_id']);
                    $table->dropColumn('source_payment_id');
                }
                if (Schema::hasColumn('fixed_assets', 'journal_entry_id')) {
                    $table->dropForeign(['journal_entry_id']);
                    $table->dropColumn('journal_entry_id');
                }
                if (Schema::hasColumn('fixed_assets', 'bank_account_id')) {
                    $table->dropForeign(['bank_account_id']);
                    $table->dropColumn('bank_account_id');
                }
                if (Schema::hasColumn('fixed_assets', 'payment_method')) {
                    $table->dropColumn('payment_method');
                }
                if (Schema::hasColumn('fixed_assets', 'ledger_account_id')) {
                    $table->dropForeign(['ledger_account_id']);
                    $table->dropColumn('ledger_account_id');
                }
            });
        }
    }
};
