<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('employees') && ! Schema::hasColumn('employees', 'leave_balance')) {
            Schema::table('employees', function (Blueprint $table): void {
                $table->decimal('leave_balance', 8, 2)->default(21)->after('attendance_policy');
            });
        }

        if (! Schema::hasTable('leaves')) {
            Schema::create('leaves', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
                $table->string('leave_type', 20);
                $table->date('start_date');
                $table->date('end_date');
                $table->unsignedSmallInteger('days_count');
                $table->text('reason')->nullable();
                $table->string('status', 20)->default('pending');
                $table->json('attachments')->nullable();
                $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('approved_at')->nullable();
                $table->timestamps();

                $table->index(['user_id', 'status']);
                $table->index(['user_id', 'start_date']);
                $table->index(['user_id', 'employee_id', 'start_date']);
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('leaves')) {
            Schema::dropIfExists('leaves');
        }

        if (Schema::hasTable('employees') && Schema::hasColumn('employees', 'leave_balance')) {
            Schema::table('employees', function (Blueprint $table): void {
                $table->dropColumn('leave_balance');
            });
        }
    }
};
