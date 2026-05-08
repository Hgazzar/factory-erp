<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('crm_loyalty_programs')) {
            Schema::create('crm_loyalty_programs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->string('code', 40)->unique();
                $table->string('name', 180);
                $table->string('points_name', 120);
                $table->decimal('earning_rate', 12, 2)->default(0);
                $table->decimal('redemption_rate', 12, 4)->default(0);
                $table->unsignedInteger('tiers_count')->default(1);
                $table->enum('status', ['active', 'inactive'])->default('active');
                $table->timestamps();

                $table->index(['user_id', 'status']);
                $table->index(['user_id', 'name']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('crm_loyalty_programs');
    }
};
