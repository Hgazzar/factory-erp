<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Persist tenant logos in the database so Railway redeploys do not wipe branding
 * (local disk under storage/app/public is ephemeral without a Volume).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('tenant_settings')) {
            return;
        }

        Schema::table('tenant_settings', function (Blueprint $table): void {
            if (! Schema::hasColumn('tenant_settings', 'logo_mime')) {
                $table->string('logo_mime', 64)->nullable()->after('logo_path');
            }
            if (! Schema::hasColumn('tenant_settings', 'logo_data')) {
                $table->longText('logo_data')->nullable()->after('logo_mime');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('tenant_settings')) {
            return;
        }

        Schema::table('tenant_settings', function (Blueprint $table): void {
            if (Schema::hasColumn('tenant_settings', 'logo_data')) {
                $table->dropColumn('logo_data');
            }
            if (Schema::hasColumn('tenant_settings', 'logo_mime')) {
                $table->dropColumn('logo_mime');
            }
        });
    }
};
