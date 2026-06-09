<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('fleet_agents')) {
            Schema::table('fleet_agents', function (Blueprint $table): void {
                if (! Schema::hasColumn('fleet_agents', 'api_pin_hash')) {
                    $table->string('api_pin_hash')->nullable()->after('notes');
                }
                if (! Schema::hasColumn('fleet_agents', 'api_last_login_at')) {
                    $table->timestamp('api_last_login_at')->nullable()->after('api_pin_hash');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('fleet_agents')) {
            Schema::table('fleet_agents', function (Blueprint $table): void {
                foreach (['api_last_login_at', 'api_pin_hash'] as $col) {
                    if (Schema::hasColumn('fleet_agents', $col)) {
                        $table->dropColumn($col);
                    }
                }
            });
        }
    }
};
