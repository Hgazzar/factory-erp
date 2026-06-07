<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('nursery_classrooms', function (Blueprint $table): void {
            if (! Schema::hasColumn('nursery_classrooms', 'age_groups')) {
                $table->json('age_groups')->nullable()->after('capacity');
            }
        });
    }

    public function down(): void
    {
        Schema::table('nursery_classrooms', function (Blueprint $table): void {
            if (Schema::hasColumn('nursery_classrooms', 'age_groups')) {
                $table->dropColumn('age_groups');
            }
        });
    }
};
