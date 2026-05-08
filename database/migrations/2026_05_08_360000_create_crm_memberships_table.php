<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('crm_memberships', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('user_id')->index();
            $table->string('code', 20)->index();
            $table->string('name', 180);
            $table->unsignedSmallInteger('level')->default(1);
            $table->enum('discount_type', ['percentage', 'fixed'])->default('percentage');
            $table->decimal('discount_value', 12, 2)->default(0);
            $table->decimal('min_spending', 14, 2)->default(0);
            $table->string('color', 20)->default('#3B82F6');
            $table->enum('status', ['active', 'paused'])->default('active');
            $table->timestamps();

            $table->unique(['user_id', 'code']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('crm_memberships');
    }
};

