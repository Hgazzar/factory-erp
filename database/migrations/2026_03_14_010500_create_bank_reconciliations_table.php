<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bank_reconciliations', function (Blueprint $table) {
            $table->id();
            $table->string('reconciliation_number', 100)->unique();
            $table->foreignId('account_id')->constrained('accounts')->cascadeOnUpdate()->restrictOnDelete();
            $table->date('statement_date');
            $table->decimal('statement_balance', 15, 2);
            $table->decimal('book_balance', 15, 2);
            $table->decimal('difference', 15, 2)->default(0);
            $table->enum('status', ['draft', 'completed'])->default('draft');
            $table->timestamps();

            $table->index(['status', 'statement_date']);
            $table->index('account_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bank_reconciliations');
    }
};
