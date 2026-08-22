<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('nursery_attendance_corrections')) {
            return;
        }

        Schema::create('nursery_attendance_corrections', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('attendance_log_id')->constrained('nursery_attendance_logs')->cascadeOnDelete();
            $table->foreignId('corrected_by')->nullable()->constrained('users')->nullOnDelete();
            $table->json('before_state');
            $table->json('after_state');
            $table->string('reason', 255)->nullable();
            $table->timestamps();

            $table->index(['user_id', 'attendance_log_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('nursery_attendance_corrections');
    }
};
