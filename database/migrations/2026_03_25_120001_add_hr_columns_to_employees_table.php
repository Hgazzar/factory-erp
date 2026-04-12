<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->foreignId('department_id')->nullable()->after('user_id')->constrained('departments')->nullOnDelete();
            $table->string('email')->nullable()->after('name');
            $table->string('position')->nullable()->after('job_title');
            $table->string('gender', 20)->nullable()->after('position');
            $table->string('status', 30)->default('active')->after('gender');
            $table->date('hire_date')->nullable()->after('hired_at');
        });

        if (Schema::hasColumn('employees', 'job_title') && Schema::hasColumn('employees', 'position')) {
            DB::statement('UPDATE employees SET position = job_title WHERE position IS NULL AND job_title IS NOT NULL');
        }

        if (Schema::hasColumn('employees', 'hired_at') && Schema::hasColumn('employees', 'hire_date')) {
            DB::statement('UPDATE employees SET hire_date = hired_at WHERE hire_date IS NULL AND hired_at IS NOT NULL');
        }
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropConstrainedForeignId('department_id');
            $table->dropColumn(['email', 'position', 'gender', 'status', 'hire_date']);
        });
    }
};
