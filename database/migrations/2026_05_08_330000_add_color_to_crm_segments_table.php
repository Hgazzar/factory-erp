<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('crm_segments') && ! Schema::hasColumn('crm_segments', 'color')) {
            Schema::table('crm_segments', function (Blueprint $table) {
                $table->string('color', 7)->default('#2563EB')->after('status');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('crm_segments') && Schema::hasColumn('crm_segments', 'color')) {
            Schema::table('crm_segments', function (Blueprint $table) {
                $table->dropColumn('color');
            });
        }
    }
};
