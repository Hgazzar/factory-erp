<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('nursery_settings')) {
            return;
        }

        Schema::table('nursery_settings', function (Blueprint $table): void {
            if (! Schema::hasColumn('nursery_settings', 'display_name')) {
                $table->string('display_name', 120)->nullable()->after('nursery_name');
            }
            if (! Schema::hasColumn('nursery_settings', 'logo_path')) {
                $table->string('logo_path', 500)->nullable()->after('display_name');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('nursery_settings')) {
            return;
        }

        Schema::table('nursery_settings', function (Blueprint $table): void {
            if (Schema::hasColumn('nursery_settings', 'logo_path')) {
                $table->dropColumn('logo_path');
            }
            if (Schema::hasColumn('nursery_settings', 'display_name')) {
                $table->dropColumn('display_name');
            }
        });
    }
};
