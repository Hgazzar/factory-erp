<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->string('salary_type', 20)->default('monthly')->after('base_salary');
            $table->decimal('fixed_insurance_deduction', 15, 2)->nullable()->after('salary_type');
            $table->decimal('fixed_tax_deduction', 15, 2)->nullable()->after('fixed_insurance_deduction');
            $table->string('attendance_policy', 30)->default('none')->after('fixed_tax_deduction');
        });
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropColumn([
                'salary_type',
                'fixed_insurance_deduction',
                'fixed_tax_deduction',
                'attendance_policy',
            ]);
        });
    }
};
