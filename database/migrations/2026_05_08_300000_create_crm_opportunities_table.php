<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('crm_opportunities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('opportunity_number', 32);
            $table->string('title');
            $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();
            $table->string('stage', 32);
            $table->decimal('estimated_value', 15, 2)->default(0);
            $table->unsignedTinyInteger('probability')->default(0);
            $table->decimal('weighted_value', 15, 2)->default(0);
            $table->date('expected_closing_date')->nullable();
            $table->foreignId('assigned_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['user_id', 'opportunity_number']);
            $table->index(['user_id', 'stage']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('crm_opportunities');
    }
};
