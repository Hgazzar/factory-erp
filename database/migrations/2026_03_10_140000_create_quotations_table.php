<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quotations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();
            $table->date('date');
            $table->date('valid_until')->nullable();
            $table->text('notes')->nullable();
            $table->text('internal_notes')->nullable();
            $table->text('terms')->nullable();
            $table->string('status', 30)->default('معلق');
            $table->decimal('total', 15, 4)->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quotations');
    }
};
