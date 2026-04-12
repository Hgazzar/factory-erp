<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // إضافة العمود لجدول الموردين
        if (Schema::hasTable('suppliers') && !Schema::hasColumn('suppliers', 'name_ar')) {
            Schema::table('suppliers', function (Blueprint $table) {
                $table->string('name_ar')->nullable()->after('name');
            });
        }

        // إضافة العمود لجدول المدفوعات (المصروفات)
        if (Schema::hasTable('payments') && !Schema::hasColumn('payments', 'name_ar')) {
            Schema::table('payments', function (Blueprint $table) {
                $table->string('name_ar')->nullable();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('suppliers', 'name_ar')) {
            Schema::table('suppliers', function (Blueprint $table) {
                $table->dropColumn('name_ar');
            });
        }

        if (Schema::hasColumn('payments', 'name_ar')) {
            Schema::table('payments', function (Blueprint $table) {
                $table->dropColumn('name_ar');
            });
        }
    }
};