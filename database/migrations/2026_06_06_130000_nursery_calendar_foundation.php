<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('nursery_unit_lessons', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('unit_id')->constrained('nursery_units')->cascadeOnDelete();
            $table->string('name');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['unit_id', 'name']);
            $table->index(['user_id', 'unit_id']);
        });

        Schema::create('nursery_calendar_entries', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('entry_type', 24);
            $table->string('title');
            $table->foreignId('unit_id')->nullable()->constrained('nursery_units')->nullOnDelete();
            $table->foreignId('unit_lesson_id')->nullable()->constrained('nursery_unit_lessons')->nullOnDelete();
            $table->timestamp('starts_at');
            $table->timestamp('ends_at');
            $table->text('notes')->nullable();
            $table->json('classroom_ids')->nullable();
            $table->json('child_ids')->nullable();
            $table->json('media_links')->nullable();
            $table->boolean('is_recurring')->default(false);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['user_id', 'starts_at']);
            $table->index(['user_id', 'entry_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('nursery_calendar_entries');
        Schema::dropIfExists('nursery_unit_lessons');
    }
};
