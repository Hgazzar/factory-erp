<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('purchase_returns')) {
            Schema::table('purchase_returns', function (Blueprint $table): void {
                if (! Schema::hasColumn('purchase_returns', 'subtotal')) {
                    $table->decimal('subtotal', 15, 4)->default(0)->after('reason');
                }
                if (! Schema::hasColumn('purchase_returns', 'journal_entry_id')) {
                    $table->foreignId('journal_entry_id')
                        ->nullable()
                        ->after('debit_note_id')
                        ->constrained('journal_entries')
                        ->nullOnDelete();
                }
                if (! Schema::hasColumn('purchase_returns', 'posted_at')) {
                    $table->timestamp('posted_at')->nullable()->after('journal_entry_id');
                }
                if (! Schema::hasColumn('purchase_returns', 'inventory_posted_at')) {
                    $table->timestamp('inventory_posted_at')->nullable()->after('posted_at');
                }
            });
        }

        if (Schema::hasTable('purchase_return_items')) {
            Schema::table('purchase_return_items', function (Blueprint $table): void {
                if (! Schema::hasColumn('purchase_return_items', 'user_id')) {
                    $table->foreignId('user_id')
                        ->nullable()
                        ->after('id')
                        ->constrained('users')
                        ->cascadeOnDelete();
                }
                if (! Schema::hasColumn('purchase_return_items', 'purchase_invoice_item_id')) {
                    $table->foreignId('purchase_invoice_item_id')
                        ->nullable()
                        ->after('item_id')
                        ->constrained('purchase_invoice_items')
                        ->nullOnDelete();
                }
                if (! Schema::hasColumn('purchase_return_items', 'discount')) {
                    $table->decimal('discount', 15, 4)->default(0)->after('unit_price');
                }
                if (! Schema::hasColumn('purchase_return_items', 'unit_cost')) {
                    $table->decimal('unit_cost', 15, 4)->nullable()->after('vat_percent')
                        ->comment('تكلفة الوحدة المرتجعة (من الفاتورة أو المخزون)');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('purchase_return_items')) {
            Schema::table('purchase_return_items', function (Blueprint $table): void {
                if (Schema::hasColumn('purchase_return_items', 'purchase_invoice_item_id')) {
                    $table->dropConstrainedForeignId('purchase_invoice_item_id');
                }
                if (Schema::hasColumn('purchase_return_items', 'user_id')) {
                    $table->dropConstrainedForeignId('user_id');
                }
                foreach (['discount', 'unit_cost'] as $col) {
                    if (Schema::hasColumn('purchase_return_items', $col)) {
                        $table->dropColumn($col);
                    }
                }
            });
        }

        if (Schema::hasTable('purchase_returns')) {
            Schema::table('purchase_returns', function (Blueprint $table): void {
                if (Schema::hasColumn('purchase_returns', 'journal_entry_id')) {
                    $table->dropConstrainedForeignId('journal_entry_id');
                }
                foreach (['subtotal', 'posted_at', 'inventory_posted_at'] as $col) {
                    if (Schema::hasColumn('purchase_returns', $col)) {
                        $table->dropColumn($col);
                    }
                }
            });
        }
    }
};
