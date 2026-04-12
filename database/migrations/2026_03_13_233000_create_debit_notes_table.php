<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('debit_notes', function (Blueprint $table) {
            $table->id();
            $table->string('note_number', 30)->unique();
            $table->foreignId('supplier_id')->constrained('suppliers')->cascadeOnDelete();
            $table->foreignId('purchase_invoice_id')->nullable()->constrained('purchase_invoices')->nullOnDelete();
            $table->date('date');
            $table->string('reference', 100)->nullable();
            $table->string('original_invoice_ref', 100)->nullable();
            $table->decimal('amount', 15, 4)->default(0);
            $table->decimal('tax_amount', 15, 4)->default(0);
            $table->string('reason_type', 100)->nullable();
            $table->text('reason')->nullable();
            $table->text('notes')->nullable();
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
        Schema::dropIfExists('debit_notes');
    }
};

