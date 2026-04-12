<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('production_records', function (Blueprint $table) {
            $table->string('scrap_reason', 100)->nullable()->after('scrap_quantity');
            $table->string('downtime_reason', 50)->nullable()->after('notes')->comment('electricity|machine_failure|maintenance|other');
            $table->decimal('downtime_lost_hours', 8, 2)->nullable()->after('downtime_reason');
        });

        Schema::table('production_logs', function (Blueprint $table) {
            $table->string('scrap_reason', 100)->nullable()->after('rejected_quantity');
            $table->string('downtime_reason', 50)->nullable()->after('notes');
            $table->decimal('downtime_lost_hours', 8, 2)->nullable()->after('downtime_reason');
        });
    }

    public function down(): void
    {
        Schema::table('production_records', function (Blueprint $table) {
            $table->dropColumn(['scrap_reason', 'downtime_reason', 'downtime_lost_hours']);
        });
        Schema::table('production_logs', function (Blueprint $table) {
            $table->dropColumn(['scrap_reason', 'downtime_reason', 'downtime_lost_hours']);
        });
    }
};
