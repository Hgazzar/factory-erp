<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('credit_note_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('credit_note_id')->constrained('credit_notes')->cascadeOnDelete();
            $table->string('description', 255);
            $table->decimal('quantity', 15, 4)->default(1);
            $table->decimal('unit_price', 15, 4)->default(0);
            $table->decimal('tax_percent', 5, 2)->default(0);
            $table->decimal('line_total', 15, 4)->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('credit_note_items');
    }
};

