<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fleet_routes', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('agent_id');
            $table->date('route_date');
            $table->string('status', 24)->default('planned');
            $table->text('notes')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('agent_id')->references('id')->on('fleet_agents')->cascadeOnDelete();
            $table->unique(['user_id', 'agent_id', 'route_date']);
            $table->index(['user_id', 'route_date', 'status']);
        });

        Schema::create('fleet_route_stops', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('route_id');
            $table->unsignedBigInteger('customer_id');
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->string('status', 24)->default('pending');
            $table->timestamp('visited_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('route_id')->references('id')->on('fleet_routes')->cascadeOnDelete();
            $table->foreign('customer_id')->references('id')->on('fleet_customers')->cascadeOnDelete();
            $table->unique(['route_id', 'customer_id']);
            $table->index(['route_id', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fleet_route_stops');
        Schema::dropIfExists('fleet_routes');
    }
};
