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
            if (! Schema::hasColumn('nursery_settings', 'theme_primary_color')) {
                $table->string('theme_primary_color', 7)->nullable()->after('logo_path');
            }
            if (! Schema::hasColumn('nursery_settings', 'theme_secondary_color')) {
                $table->string('theme_secondary_color', 7)->nullable()->after('theme_primary_color');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('nursery_settings')) {
            return;
        }

        Schema::table('nursery_settings', function (Blueprint $table): void {
            if (Schema::hasColumn('nursery_settings', 'theme_secondary_color')) {
                $table->dropColumn('theme_secondary_color');
            }
            if (Schema::hasColumn('nursery_settings', 'theme_primary_color')) {
                $table->dropColumn('theme_primary_color');
            }
        });
    }
};
