<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('fleet_agents')) {
            Schema::create('fleet_agents', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->string('name', 120);
                $table->string('phone', 32)->nullable();
                $table->string('email', 120)->nullable();
                $table->unsignedBigInteger('employee_id')->nullable();
                $table->unsignedBigInteger('pos_device_id')->nullable();
                $table->string('status', 24)->default('active');
                $table->text('notes')->nullable();
                $table->timestamps();
                $table->index(['user_id', 'status']);
            });
        }

        if (! Schema::hasTable('fleet_customers')) {
            Schema::create('fleet_customers', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->string('name', 120);
                $table->string('phone', 32)->nullable();
                $table->string('email', 120)->nullable();
                $table->string('address', 255)->nullable();
                $table->string('city', 120)->nullable();
                $table->string('region', 64)->nullable();
                $table->unsignedBigInteger('assigned_agent_id')->nullable();
                $table->unsignedBigInteger('crm_customer_id')->nullable();
                $table->string('status', 24)->default('active');
                $table->text('notes')->nullable();
                $table->timestamps();
                $table->foreign('assigned_agent_id')->references('id')->on('fleet_agents')->nullOnDelete();
                $table->index(['user_id', 'status']);
            });
        }

        if (! Schema::hasTable('fleet_products')) {
            Schema::create('fleet_products', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->string('name', 160);
                $table->string('sku', 64)->nullable();
                $table->decimal('sale_price', 14, 4)->default(0);
                $table->string('image_url', 500)->nullable();
                $table->boolean('is_active')->default(true);
                $table->text('description')->nullable();
                $table->timestamps();
                $table->index(['user_id', 'is_active']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('fleet_products');
        Schema::dropIfExists('fleet_customers');
        Schema::dropIfExists('fleet_agents');
    }
};
