<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tenant_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_user_id')->unique()->constrained('users')->cascadeOnDelete();
            $table->string('niche_key', 64)->index();
            $table->string('domain', 128)->unique();
            $table->string('status', 32)->default('active');
            $table->json('lexicon_overrides')->nullable();
            $table->timestamps();

            $table->index(['niche_key', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenant_profiles');
    }
};
