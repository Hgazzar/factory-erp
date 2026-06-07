<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('nursery_guardians')) {
            return;
        }

        Schema::table('nursery_guardians', function (Blueprint $table): void {
            if (! Schema::hasColumn('nursery_guardians', 'portal_access_token')) {
                $table->string('portal_access_token', 64)->nullable()->after('notes');
            }
            if (! Schema::hasColumn('nursery_guardians', 'portal_invited_at')) {
                $table->timestamp('portal_invited_at')->nullable()->after('portal_access_token');
            }
            if (! Schema::hasColumn('nursery_guardians', 'portal_last_login_at')) {
                $table->timestamp('portal_last_login_at')->nullable()->after('portal_invited_at');
            }
        });

        Schema::table('nursery_guardians', function (Blueprint $table): void {
            if (Schema::hasColumn('nursery_guardians', 'portal_access_token')) {
                $table->unique(['user_id', 'portal_access_token'], 'nursery_guardians_user_portal_token_unique');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('nursery_guardians')) {
            return;
        }

        Schema::table('nursery_guardians', function (Blueprint $table): void {
            if (Schema::hasColumn('nursery_guardians', 'portal_access_token')) {
                $table->dropUnique('nursery_guardians_user_portal_token_unique');
            }
            foreach (['portal_last_login_at', 'portal_invited_at', 'portal_access_token'] as $column) {
                if (Schema::hasColumn('nursery_guardians', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
