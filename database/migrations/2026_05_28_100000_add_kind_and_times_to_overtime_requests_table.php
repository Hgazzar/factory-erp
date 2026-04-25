<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('overtime_requests', function (Blueprint $table) {
            $table->string('kind', 20)->default('regular')->after('work_date');
            $table->time('time_start')->nullable()->after('kind');
            $table->time('time_end')->nullable()->after('time_start');
        });
    }

    public function down(): void
    {
        Schema::table('overtime_requests', function (Blueprint $table) {
            $table->dropColumn(['kind', 'time_start', 'time_end']);
        });
    }
};
