<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('system_modules')) {
            Schema::create('system_modules', function (Blueprint $table) {
                $table->id();
                $table->string('key', 64)->unique();
                $table->string('name_ar');
                $table->string('name_en');
                $table->text('description_ar')->nullable();
                $table->boolean('is_core')->default(false);
                $table->json('niche_tags')->nullable();
                $table->unsignedSmallInteger('sort_order')->default(0);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('tenant_modules')) {
            Schema::create('tenant_modules', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_user_id')->constrained('users')->cascadeOnDelete();
                $table->foreignId('system_module_id')->constrained('system_modules')->cascadeOnDelete();
                $table->boolean('enabled')->default(true);
                $table->timestamp('enabled_at')->nullable();
                $table->timestamp('disabled_at')->nullable();
                $table->json('config')->nullable();
                $table->timestamps();

                $table->unique(['tenant_user_id', 'system_module_id']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('tenant_modules');
        Schema::dropIfExists('system_modules');
    }
};
