<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * تفاصيل قيود اليومية (أسطر القيد)
     */
    public function up(): void
    {
        Schema::create('journal_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('journal_entry_id')->constrained('journal_entries')->cascadeOnDelete();
            $table->foreignId('account_id')->constrained('accounts')->cascadeOnDelete();
            $table->text('description')->nullable();
            $table->decimal('debit', 15, 4)->default(0);
            $table->decimal('credit', 15, 4)->default(0);
            $table->timestamps();

            $table->index(['journal_entry_id']);
            $table->index(['account_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('journal_items');
    }
};

