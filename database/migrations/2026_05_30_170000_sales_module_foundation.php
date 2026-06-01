<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('customers')) {
            Schema::table('customers', function (Blueprint $table): void {
                if (! Schema::hasColumn('customers', 'opening_balance')) {
                    $table->decimal('opening_balance', 15, 4)->default(0)->after('payment_terms_days');
                }
                if (! Schema::hasColumn('customers', 'opening_balance_date')) {
                    $table->date('opening_balance_date')->nullable()->after('opening_balance');
                }
                if (! Schema::hasColumn('customers', 'opening_balance_journal_entry_id')) {
                    $table->foreignId('opening_balance_journal_entry_id')
                        ->nullable()
                        ->after('opening_balance_date')
                        ->constrained('journal_entries')
                        ->nullOnDelete();
                }
                if (! Schema::hasColumn('customers', 'receivable_ledger_account_id')) {
                    $table->foreignId('receivable_ledger_account_id')
                        ->nullable()
                        ->after('opening_balance_journal_entry_id')
                        ->constrained('accounts')
                        ->nullOnDelete();
                }
            });
        }

        if (Schema::hasTable('sales_invoices')) {
            Schema::table('sales_invoices', function (Blueprint $table): void {
                if (! Schema::hasColumn('sales_invoices', 'subtotal')) {
                    $table->decimal('subtotal', 15, 4)->default(0)->after('payment_method');
                }
                if (! Schema::hasColumn('sales_invoices', 'status')) {
                    $table->string('status', 20)->default('draft')->after('paid_amount');
                }
                if (! Schema::hasColumn('sales_invoices', 'posted_at')) {
                    $table->timestamp('posted_at')->nullable()->after('journal_entry_id');
                }
                if (! Schema::hasColumn('sales_invoices', 'inventory_posted_at')) {
                    $table->timestamp('inventory_posted_at')->nullable()->after('posted_at');
                }
                if (! Schema::hasColumn('sales_invoices', 'cogs_journal_entry_id')) {
                    $table->foreignId('cogs_journal_entry_id')
                        ->nullable()
                        ->after('inventory_posted_at')
                        ->constrained('journal_entries')
                        ->nullOnDelete();
                }
            });

            if (Schema::hasColumn('sales_invoices', 'status')) {
                DB::table('sales_invoices')
                    ->whereNotNull('journal_entry_id')
                    ->whereNull('posted_at')
                    ->update(['posted_at' => DB::raw('updated_at')]);

                DB::table('sales_invoices')
                    ->where('invoice_status', 'draft')
                    ->update(['status' => 'draft']);

                DB::table('sales_invoices')
                    ->where('invoice_status', 'issued')
                    ->whereColumn('paid_amount', '>=', 'total')
                    ->where('total', '>', 0)
                    ->update(['status' => 'paid']);

                DB::table('sales_invoices')
                    ->where('invoice_status', 'issued')
                    ->where('paid_amount', '>', 0)
                    ->whereColumn('paid_amount', '<', 'total')
                    ->update(['status' => 'partial']);

                DB::table('sales_invoices')
                    ->where('invoice_status', 'issued')
                    ->where(function ($q): void {
                        $q->where('paid_amount', '<=', 0)
                            ->orWhereNull('paid_amount');
                    })
                    ->update(['status' => 'unpaid']);
            }
        }

        if (Schema::hasTable('sales_invoice_items')) {
            Schema::table('sales_invoice_items', function (Blueprint $table): void {
                if (! Schema::hasColumn('sales_invoice_items', 'description')) {
                    $table->string('description', 500)->nullable()->after('item_id');
                }
                if (! Schema::hasColumn('sales_invoice_items', 'discount')) {
                    $table->decimal('discount', 15, 4)->default(0)->after('unit_price');
                }
                if (! Schema::hasColumn('sales_invoice_items', 'vat_percent')) {
                    $table->decimal('vat_percent', 8, 2)->nullable()->after('discount')
                        ->comment('null = نسبة ضريبة المنشأة الافتراضية');
                }
                if (! Schema::hasColumn('sales_invoice_items', 'unit_cost')) {
                    $table->decimal('unit_cost', 15, 4)->nullable()->after('vat_percent')
                        ->comment('تكلفة الوحدة عند الترحيل (COGS)');
                }
            });
        }

        if (Schema::hasTable('crm_activities') && ! Schema::hasColumn('crm_activities', 'sales_invoice_id')) {
            Schema::table('crm_activities', function (Blueprint $table): void {
                $table->foreignId('sales_invoice_id')
                    ->nullable()
                    ->after('customer_id')
                    ->constrained('sales_invoices')
                    ->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('crm_activities') && Schema::hasColumn('crm_activities', 'sales_invoice_id')) {
            Schema::table('crm_activities', function (Blueprint $table): void {
                $table->dropConstrainedForeignId('sales_invoice_id');
            });
        }

        if (Schema::hasTable('sales_invoice_items')) {
            Schema::table('sales_invoice_items', function (Blueprint $table): void {
                foreach (['description', 'discount', 'vat_percent', 'unit_cost'] as $col) {
                    if (Schema::hasColumn('sales_invoice_items', $col)) {
                        $table->dropColumn($col);
                    }
                }
            });
        }

        if (Schema::hasTable('sales_invoices')) {
            Schema::table('sales_invoices', function (Blueprint $table): void {
                if (Schema::hasColumn('sales_invoices', 'cogs_journal_entry_id')) {
                    $table->dropConstrainedForeignId('cogs_journal_entry_id');
                }
                foreach (['subtotal', 'status', 'posted_at', 'inventory_posted_at'] as $col) {
                    if (Schema::hasColumn('sales_invoices', $col)) {
                        $table->dropColumn($col);
                    }
                }
            });
        }

        if (Schema::hasTable('customers')) {
            Schema::table('customers', function (Blueprint $table): void {
                if (Schema::hasColumn('customers', 'receivable_ledger_account_id')) {
                    $table->dropConstrainedForeignId('receivable_ledger_account_id');
                }
                if (Schema::hasColumn('customers', 'opening_balance_journal_entry_id')) {
                    $table->dropConstrainedForeignId('opening_balance_journal_entry_id');
                }
                foreach (['opening_balance_date', 'opening_balance'] as $col) {
                    if (Schema::hasColumn('customers', $col)) {
                        $table->dropColumn($col);
                    }
                }
            });
        }
    }
};
