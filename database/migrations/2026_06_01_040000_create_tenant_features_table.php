<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('tenant_features')) {
            Schema::create('tenant_features', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->constrained('users')->cascadeOnDelete();
                $table->string('feature_key', 96);
                $table->timestamps();

                $table->unique(['tenant_id', 'feature_key']);
                $table->index(['feature_key']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('tenant_features');
    }
};
