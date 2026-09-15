<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * إصلاح ترتيب الهجرات: 2026_06_04_* كانت تُتخطى إن لم يكن جدول nursery_settings
 * موجوداً بعد، ثم 2026_06_06 أنشأ الجدول بدون display_name/logo/theme.
 */
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
        // لا نحذف أعمدة قد تكون مستخدمة — الإصلاح أحادي الاتجاه.
    }
};
