<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cheques', function (Blueprint $table) {
            $table->id();
            $table->enum('type', ['incoming', 'outgoing'])->comment('incoming=وارد, outgoing=صادر');
            $table->string('cheque_number', 100)->unique();
            $table->string('bank_name', 150);
            $table->decimal('amount', 15, 2);
            $table->string('party_name', 150)->nullable()->comment('العميل/المورد');
            $table->string('beneficiary_name', 150)->nullable()->comment('المستفيد');
            $table->date('issue_date')->nullable();
            $table->date('due_date');
            $table->enum('status', ['pending', 'cleared', 'bounced', 'cancelled'])->default('pending');
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['type', 'status']);
            $table->index('due_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cheques');
    }
};
