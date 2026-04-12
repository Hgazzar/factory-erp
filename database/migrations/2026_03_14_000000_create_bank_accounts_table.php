<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bank_accounts', function (Blueprint $table) {
            $table->id();
            $table->string('bank_name', 150);
            $table->string('branch_name', 150)->nullable();
            $table->string('account_number', 100)->unique();
            $table->string('iban', 34)->nullable()->unique();
            $table->string('currency', 3)->default('SAR');
            $table->decimal('opening_balance', 15, 2)->default(0);
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['status', 'bank_name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bank_accounts');
    }
};
