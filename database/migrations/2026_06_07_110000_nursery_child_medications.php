<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('nursery_child_medications', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('child_id')->constrained('nursery_children')->cascadeOnDelete();
            $table->string('name');
            $table->string('dosage')->nullable();
            $table->string('frequency', 32)->nullable();
            $table->string('schedule_notes')->nullable();
            $table->text('notes')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['user_id', 'child_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('nursery_child_medications');
    }
};
