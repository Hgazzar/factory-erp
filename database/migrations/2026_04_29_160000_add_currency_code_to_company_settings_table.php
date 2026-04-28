<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('company_settings')) {
            return;
        }

        Schema::table('company_settings', function (Blueprint $table): void {
            if (! Schema::hasColumn('company_settings', 'currency_code')) {
                $table->string('currency_code', 10)->default('SAR')->after('logo_url')->comment('رمز العملة للعرض والتقارير (ISO)');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('company_settings')) {
            return;
        }

        Schema::table('company_settings', function (Blueprint $table): void {
            if (Schema::hasColumn('company_settings', 'currency_code')) {
                $table->dropColumn('currency_code');
            }
        });
    }
};
