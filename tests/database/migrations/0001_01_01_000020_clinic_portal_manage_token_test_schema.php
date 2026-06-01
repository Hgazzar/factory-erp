<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('clinic_appointments', 'portal_manage_token')) {
            Schema::table('clinic_appointments', function (Blueprint $table) {
                $table->string('portal_manage_token', 80)->nullable()->unique();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('clinic_appointments', 'portal_manage_token')) {
            Schema::table('clinic_appointments', function (Blueprint $table) {
                $table->dropColumn('portal_manage_token');
            });
        }
    }
};
