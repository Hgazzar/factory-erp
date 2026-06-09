<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fleet_custody_returns', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('agent_id');
            $table->string('return_number', 32);
            $table->date('returned_on');
            $table->string('status', 24)->default('draft');
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('recorded_by')->nullable();
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('agent_id')->references('id')->on('fleet_agents')->cascadeOnDelete();
            $table->foreign('recorded_by')->references('id')->on('users')->nullOnDelete();
            $table->unique(['user_id', 'return_number']);
            $table->index(['user_id', 'agent_id', 'status']);
            $table->index(['user_id', 'returned_on']);
        });

        Schema::create('fleet_custody_return_lines', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('return_id');
            $table->unsignedBigInteger('product_id');
            $table->decimal('quantity', 14, 4);
            $table->decimal('unit_price', 14, 4)->default(0);
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('return_id')->references('id')->on('fleet_custody_returns')->cascadeOnDelete();
            $table->foreign('product_id')->references('id')->on('fleet_products')->cascadeOnDelete();
            $table->index(['return_id', 'product_id']);
        });

        Schema::create('fleet_collections', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('agent_id');
            $table->unsignedBigInteger('customer_id')->nullable();
            $table->unsignedBigInteger('route_id')->nullable();
            $table->unsignedBigInteger('route_stop_id')->nullable();
            $table->string('collection_number', 32);
            $table->date('collected_on');
            $table->string('payment_method', 24)->default('cod');
            $table->decimal('subtotal', 14, 4)->default(0);
            $table->string('status', 24)->default('draft');
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('recorded_by')->nullable();
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('agent_id')->references('id')->on('fleet_agents')->cascadeOnDelete();
            $table->foreign('customer_id')->references('id')->on('fleet_customers')->nullOnDelete();
            $table->foreign('route_id')->references('id')->on('fleet_routes')->nullOnDelete();
            $table->foreign('route_stop_id')->references('id')->on('fleet_route_stops')->nullOnDelete();
            $table->foreign('recorded_by')->references('id')->on('users')->nullOnDelete();
            $table->unique(['user_id', 'collection_number']);
            $table->index(['user_id', 'agent_id', 'status']);
            $table->index(['user_id', 'collected_on']);
            $table->index(['user_id', 'route_id']);
        });

        Schema::create('fleet_collection_lines', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('collection_id');
            $table->unsignedBigInteger('product_id');
            $table->decimal('quantity', 14, 4);
            $table->decimal('unit_price', 14, 4)->default(0);
            $table->decimal('line_total', 14, 4)->default(0);
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('collection_id')->references('id')->on('fleet_collections')->cascadeOnDelete();
            $table->foreign('product_id')->references('id')->on('fleet_products')->cascadeOnDelete();
            $table->index(['collection_id', 'product_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fleet_collection_lines');
        Schema::dropIfExists('fleet_collections');
        Schema::dropIfExists('fleet_custody_return_lines');
        Schema::dropIfExists('fleet_custody_returns');
    }
};
