<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales_invoices', function (Blueprint $table) {
            if (! Schema::hasColumn('sales_invoices', 'subtotal')) {
                $table->decimal('subtotal', 15, 4)->default(0)->after('reference');
            }
            if (! Schema::hasColumn('sales_invoices', 'vat_rate')) {
                $table->decimal('vat_rate', 8, 2)->default(0)->after('subtotal');
            }
            if (! Schema::hasColumn('sales_invoices', 'vat_amount')) {
                $table->decimal('vat_amount', 15, 4)->default(0)->after('vat_rate');
            }
            if (! Schema::hasColumn('sales_invoices', 'payment_method')) {
                $table->string('payment_method', 20)->default('credit')->after('vat_amount');
            }
            if (! Schema::hasColumn('sales_invoices', 'due_date')) {
                $table->date('due_date')->nullable()->after('date');
            }
            if (! Schema::hasColumn('sales_invoices', 'status')) {
                $table->string('status', 20)->default('draft')->after('paid_amount');
            }
            if (! Schema::hasColumn('sales_invoices', 'journal_entry_id')) {
                $table->foreignId('journal_entry_id')->nullable()->constrained('journal_entries')->nullOnDelete();
            }
            if (! Schema::hasColumn('sales_invoices', 'cogs_journal_entry_id')) {
                $table->foreignId('cogs_journal_entry_id')->nullable()->constrained('journal_entries')->nullOnDelete();
            }
            if (! Schema::hasColumn('sales_invoices', 'posted_at')) {
                $table->timestamp('posted_at')->nullable();
            }
            if (! Schema::hasColumn('sales_invoices', 'inventory_posted_at')) {
                $table->timestamp('inventory_posted_at')->nullable();
            }
        });

        Schema::create('sales_invoice_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('sales_invoice_id')->constrained()->cascadeOnDelete();
            $table->foreignId('item_id')->constrained()->cascadeOnDelete();
            $table->string('description', 500)->nullable();
            $table->decimal('quantity', 15, 4)->default(0);
            $table->decimal('unit_price', 15, 4)->default(0);
            $table->decimal('discount', 15, 4)->default(0);
            $table->decimal('vat_percent', 8, 2)->nullable();
            $table->decimal('unit_cost', 15, 4)->nullable();
            $table->decimal('line_total', 15, 4)->default(0);
            $table->timestamps();
        });

        Schema::create('crm_activities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('sales_invoice_id')->nullable()->constrained('sales_invoices')->nullOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('type', 50);
            $table->text('note')->nullable();
            $table->string('result', 100)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('crm_activities');
        Schema::dropIfExists('sales_invoice_items');
    }
};
