<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropIndex(['user_id', 'attendance_device_id']);
        });

        Schema::table('employees', function (Blueprint $table) {
            $table->unique(
                ['user_id', 'attendance_device_id'],
                'employees_user_attendance_device_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropUnique('employees_user_attendance_device_unique');
        });

        Schema::table('employees', function (Blueprint $table) {
            $table->index(['user_id', 'attendance_device_id']);
        });
    }
};
