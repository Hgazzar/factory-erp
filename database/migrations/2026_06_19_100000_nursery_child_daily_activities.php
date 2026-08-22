<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('nursery_child_daily_activities')) {
            return;
        }

        Schema::create('nursery_child_daily_activities', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('child_id')->constrained('nursery_children')->cascadeOnDelete();
            $table->date('activity_date');
            $table->string('type', 32);
            $table->json('payload')->nullable();
            $table->string('note', 500)->nullable();
            $table->boolean('is_parent_visible')->default(true);
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('recorded_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'child_id', 'activity_date']);
            $table->index(['user_id', 'activity_date', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('nursery_child_daily_activities');
    }
};
