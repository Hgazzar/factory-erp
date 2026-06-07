<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table): void {
            if (! Schema::hasColumn('employees', 'nursery_job_role')) {
                $table->string('nursery_job_role', 64)->nullable()->after('nursery_role');
            }
            if (! Schema::hasColumn('employees', 'nursery_permissions')) {
                $table->json('nursery_permissions')->nullable()->after('nursery_job_role');
            }
            if (! Schema::hasColumn('employees', 'nursery_education')) {
                $table->string('nursery_education', 120)->nullable()->after('nursery_permissions');
            }
            if (! Schema::hasColumn('employees', 'nursery_specialization')) {
                $table->string('nursery_specialization', 120)->nullable()->after('nursery_education');
            }
        });
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table): void {
            foreach (['nursery_specialization', 'nursery_education', 'nursery_permissions', 'nursery_job_role'] as $col) {
                if (Schema::hasColumn('employees', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
