<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('nursery_children')) {
            return;
        }

        Schema::table('nursery_children', function (Blueprint $table): void {
            if (! Schema::hasColumn('nursery_children', 'social_media_photo_consent')) {
                $table->boolean('social_media_photo_consent')->default(false)->after('health_notes');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('nursery_children')) {
            return;
        }

        Schema::table('nursery_children', function (Blueprint $table): void {
            if (Schema::hasColumn('nursery_children', 'social_media_photo_consent')) {
                $table->dropColumn('social_media_photo_consent');
            }
        });
    }
};
