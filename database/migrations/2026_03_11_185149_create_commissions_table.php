<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('commissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('sales_invoice_id')->constrained('sales_invoices')->cascadeOnDelete();
            $table->decimal('base_amount', 15, 4)->default(0);
            $table->decimal('rate_percent', 5, 2)->default(0);
            $table->decimal('commission_amount', 15, 4)->default(0);
            $table->date('calculated_at')->nullable();
            $table->string('status', 30)->default('pending_approval'); // pending_approval|approved|pending_payment|paid|rejected
            $table->timestamps();

            $table->index(['user_id', 'calculated_at']);
            $table->index(['status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('commissions');
    }
};
