<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('suppliers')) {
            Schema::table('suppliers', function (Blueprint $table): void {
                if (! Schema::hasColumn('suppliers', 'city')) {
                    $table->string('city', 100)->nullable()->after('address');
                }
                if (! Schema::hasColumn('suppliers', 'region')) {
                    $table->string('region', 100)->nullable()->after('city');
                }
                if (! Schema::hasColumn('suppliers', 'country')) {
                    $table->string('country', 100)->nullable()->after('region');
                }
                if (! Schema::hasColumn('suppliers', 'postal_code')) {
                    $table->string('postal_code', 30)->nullable()->after('country');
                }
                if (! Schema::hasColumn('suppliers', 'opening_balance')) {
                    $table->decimal('opening_balance', 15, 4)->default(0)->after('payment_terms_days');
                }
                if (! Schema::hasColumn('suppliers', 'opening_balance_date')) {
                    $table->date('opening_balance_date')->nullable()->after('opening_balance');
                }
                if (! Schema::hasColumn('suppliers', 'opening_balance_journal_entry_id')) {
                    $table->foreignId('opening_balance_journal_entry_id')
                        ->nullable()
                        ->after('opening_balance_date')
                        ->constrained('journal_entries')
                        ->nullOnDelete();
                }
                if (! Schema::hasColumn('suppliers', 'payable_ledger_account_id')) {
                    $table->foreignId('payable_ledger_account_id')
                        ->nullable()
                        ->after('opening_balance_journal_entry_id')
                        ->constrained('accounts')
                        ->nullOnDelete();
                }
            });
        }

        if (Schema::hasTable('purchase_invoices')) {
            Schema::table('purchase_invoices', function (Blueprint $table): void {
                if (! Schema::hasColumn('purchase_invoices', 'subtotal')) {
                    $table->decimal('subtotal', 15, 4)->default(0)->after('currency');
                }
                if (! Schema::hasColumn('purchase_invoices', 'posted_at')) {
                    $table->timestamp('posted_at')->nullable()->after('journal_entry_id');
                }
                if (! Schema::hasColumn('purchase_invoices', 'inventory_posted_at')) {
                    $table->timestamp('inventory_posted_at')->nullable()->after('posted_at');
                }
            });
        }

        if (Schema::hasTable('purchase_invoice_items')) {
            Schema::table('purchase_invoice_items', function (Blueprint $table): void {
                if (! Schema::hasColumn('purchase_invoice_items', 'weighted_unit_cost')) {
                    $table->decimal('weighted_unit_cost', 15, 4)->nullable()->after('unit_price')
                        ->comment('متوسط التكلفة بعد ترحيل الفاتورة');
                }
            });
        }

        if (Schema::hasTable('payments') && ! Schema::hasColumn('payments', 'purchase_invoice_id')) {
            Schema::table('payments', function (Blueprint $table): void {
                $table->foreignId('purchase_invoice_id')
                    ->nullable()
                    ->after('supplier_id')
                    ->constrained('purchase_invoices')
                    ->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('payments') && Schema::hasColumn('payments', 'purchase_invoice_id')) {
            Schema::table('payments', function (Blueprint $table): void {
                $table->dropConstrainedForeignId('purchase_invoice_id');
            });
        }

        if (Schema::hasTable('purchase_invoice_items') && Schema::hasColumn('purchase_invoice_items', 'weighted_unit_cost')) {
            Schema::table('purchase_invoice_items', function (Blueprint $table): void {
                $table->dropColumn('weighted_unit_cost');
            });
        }

        if (Schema::hasTable('purchase_invoices')) {
            Schema::table('purchase_invoices', function (Blueprint $table): void {
                foreach (['subtotal', 'posted_at', 'inventory_posted_at'] as $col) {
                    if (Schema::hasColumn('purchase_invoices', $col)) {
                        $table->dropColumn($col);
                    }
                }
            });
        }

        if (Schema::hasTable('suppliers')) {
            Schema::table('suppliers', function (Blueprint $table): void {
                if (Schema::hasColumn('suppliers', 'payable_ledger_account_id')) {
                    $table->dropConstrainedForeignId('payable_ledger_account_id');
                }
                if (Schema::hasColumn('suppliers', 'opening_balance_journal_entry_id')) {
                    $table->dropConstrainedForeignId('opening_balance_journal_entry_id');
                }
                foreach (['opening_balance_date', 'opening_balance', 'postal_code', 'country', 'region', 'city'] as $col) {
                    if (Schema::hasColumn('suppliers', $col)) {
                        $table->dropColumn($col);
                    }
                }
            });
        }
    }
};
