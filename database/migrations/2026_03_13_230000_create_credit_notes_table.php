<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('credit_notes', function (Blueprint $table) {
            $table->id();
            $table->string('note_number', 30)->unique();
            $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();
            $table->foreignId('sales_invoice_id')->nullable()->constrained('sales_invoices')->nullOnDelete();
            $table->date('date');
            $table->string('original_invoice_ref', 100)->nullable()->comment('فاتورة الأصل');
            $table->decimal('amount', 15, 4);
            $table->decimal('tax_amount', 15, 4)->default(0);
            $table->text('reason')->nullable();
            $table->enum('status', ['draft', 'approved', 'cancelled'])->default('draft');
            $table->foreignId('journal_entry_id')->nullable()->constrained('journal_entries')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['status', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('credit_notes');
    }
};

