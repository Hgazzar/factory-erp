<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('budgets', function (Blueprint $table) {
            $table->id();
            $table->string('name', 150);
            $table->unsignedSmallInteger('fiscal_year');
            $table->date('start_date');
            $table->date('end_date');
            $table->enum('status', ['draft', 'active', 'closed'])->default('draft');
            $table->timestamps();

            $table->index(['fiscal_year', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('budgets');
    }
};

