<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * ربط نقاط البيع بالـ ERP: موظف، وردية إنتاج، قيد محاسبي، وتكلفة وحدة محفوظة للبند.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pos_sessions', function (Blueprint $table) {
            $table->foreignId('employee_id')->nullable()->after('pos_device_id')->constrained('employees')->nullOnDelete();
            $table->foreignId('production_shift_id')->nullable()->after('employee_id')->constrained('production_shifts')->nullOnDelete();
            $table->index(['employee_id']);
            $table->index(['production_shift_id']);
        });

        Schema::table('pos_sales', function (Blueprint $table) {
            $table->foreignId('journal_entry_id')->nullable()->after('status')->constrained('journal_entries')->nullOnDelete();
            $table->index(['journal_entry_id']);
        });

        Schema::table('pos_sale_lines', function (Blueprint $table) {
            $table->decimal('unit_cost', 15, 4)->default(0)->after('line_total')->comment('تكلفة الوحدة المأخوذة من متوسط الإنتاج (items.cost)');
        });
    }

    public function down(): void
    {
        Schema::table('pos_sale_lines', function (Blueprint $table) {
            $table->dropColumn('unit_cost');
        });

        Schema::table('pos_sales', function (Blueprint $table) {
            $table->dropForeign(['journal_entry_id']);
            $table->dropColumn('journal_entry_id');
        });

        Schema::table('pos_sessions', function (Blueprint $table) {
            $table->dropForeign(['employee_id']);
            $table->dropForeign(['production_shift_id']);
            $table->dropColumn(['employee_id', 'production_shift_id']);
        });
    }
};
