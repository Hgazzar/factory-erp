<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('fleet_routes')) {
            Schema::create('fleet_routes', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->foreignId('agent_id')->constrained('fleet_agents')->cascadeOnDelete();
                $table->date('route_date');
                $table->string('status', 24)->default('planned');
                $table->text('notes')->nullable();
                $table->timestamp('started_at')->nullable();
                $table->timestamp('completed_at')->nullable();
                $table->timestamps();
                $table->unique(['user_id', 'agent_id', 'route_date']);
            });
        }

        if (! Schema::hasTable('fleet_route_stops')) {
            Schema::create('fleet_route_stops', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->foreignId('route_id')->constrained('fleet_routes')->cascadeOnDelete();
                $table->foreignId('customer_id')->constrained('fleet_customers')->cascadeOnDelete();
                $table->unsignedSmallInteger('sort_order')->default(0);
                $table->string('status', 24)->default('pending');
                $table->timestamp('visited_at')->nullable();
                $table->text('notes')->nullable();
                $table->timestamps();
                $table->unique(['route_id', 'customer_id']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('fleet_route_stops');
        Schema::dropIfExists('fleet_routes');
    }
};
