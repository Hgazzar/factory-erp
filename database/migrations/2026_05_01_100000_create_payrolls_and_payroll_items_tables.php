<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payroll_cycles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->unsignedTinyInteger('month');
            $table->unsignedSmallInteger('year');
            $table->date('payment_date')->nullable();
            $table->unsignedInteger('employees_count')->default(0);
            $table->string('status', 20)->default('draft');
            $table->decimal('total_gross', 15, 2)->default(0);
            $table->decimal('total_deductions', 15, 2)->default(0);
            $table->decimal('total_amount', 15, 2)->default(0);
            $table->text('notes')->nullable();
            $table->foreignId('accrual_journal_entry_id')
                ->nullable()
                ->constrained('journal_entries')
                ->nullOnDelete();
            $table->foreignId('payment_journal_entry_id')
                ->nullable()
                ->constrained('journal_entries')
                ->nullOnDelete();
            $table->timestamps();

            $table->unique(['user_id', 'year', 'month']);
            $table->index(['user_id', 'status']);
        });

        Schema::create('pay_slips', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payroll_cycle_id')->constrained('payroll_cycles')->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->decimal('basic_salary', 15, 2)->default(0);
            $table->decimal('total_allowances', 15, 2)->default(0);
            $table->decimal('attendance_deductions', 15, 2)->default(0);
            $table->decimal('statutory_deductions', 15, 2)->default(0);
            $table->decimal('total_deductions', 15, 2)->default(0);
            $table->decimal('net_salary', 15, 2)->default(0);
            $table->decimal('overtime_hours', 10, 2)->default(0);
            $table->decimal('overtime_amount', 15, 2)->default(0);
            $table->decimal('absence_hours', 10, 2)->default(0);
            $table->decimal('late_hours', 10, 2)->default(0);
            $table->timestamps();

            $table->unique(['payroll_cycle_id', 'employee_id']);
        });

        Schema::create('payroll_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pay_slip_id')->constrained('pay_slips')->cascadeOnDelete();
            $table->string('item_code', 64);
            $table->string('item_kind', 16);
            $table->string('label', 255)->nullable();
            $table->decimal('amount', 15, 2)->default(0);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['pay_slip_id', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payroll_items');
        Schema::dropIfExists('pay_slips');
        Schema::dropIfExists('payroll_cycles');
    }
};
