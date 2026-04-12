<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * سجل مراقبة تغييرات الأدوار (Roles)
     */
    public function up(): void
    {
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('actor_id')->constrained('users')->cascadeOnDelete()->comment('المستخدم الذي قام بالتغيير');
            $table->foreignId('target_user_id')->constrained('users')->cascadeOnDelete()->comment('المستخدم الذي تغير دوره');
            $table->string('action', 50)->default('role_changed');
            $table->string('old_role', 50)->nullable();
            $table->string('new_role', 50)->nullable();
            $table->timestamp('logged_at');
            $table->timestamps();

            $table->index(['actor_id']);
            $table->index(['target_user_id']);
            $table->index(['logged_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};

