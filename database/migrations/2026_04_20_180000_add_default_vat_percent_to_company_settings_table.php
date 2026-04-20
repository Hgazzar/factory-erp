<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('company_settings', function (Blueprint $table) {
            $table->decimal('default_vat_percent', 7, 4)
                ->nullable()
                ->after('tax_number')
                ->comment('نسبة ضريبة القيمة المضافة الافتراضية للمنشأة (%)؛ null = استخدام config/accounting');
        });
    }

    public function down(): void
    {
        Schema::table('company_settings', function (Blueprint $table) {
            $table->dropColumn('default_vat_percent');
        });
    }
};
