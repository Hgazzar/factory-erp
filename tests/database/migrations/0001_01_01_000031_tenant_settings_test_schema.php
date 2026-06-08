<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('tenant_settings')) {
            Schema::create('tenant_settings', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_user_id')->unique()->constrained('users')->cascadeOnDelete();
                $table->string('display_name', 120)->nullable();
                $table->string('logo_path', 500)->nullable();
                $table->string('theme_primary_color', 7)->nullable();
                $table->string('theme_secondary_color', 7)->nullable();
                $table->string('nursery_theme_primary_color', 7)->nullable();
                $table->string('nursery_theme_secondary_color', 7)->nullable();
                $table->string('clinic_theme_primary_color', 7)->nullable();
                $table->string('clinic_theme_secondary_color', 7)->nullable();
                $table->string('store_theme_primary_color', 7)->nullable();
                $table->string('store_theme_secondary_color', 7)->nullable();
                $table->string('fleet_theme_primary_color', 7)->nullable();
                $table->string('fleet_theme_secondary_color', 7)->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('tenant_settings');
    }
};
