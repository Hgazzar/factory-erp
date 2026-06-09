<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('fleet_custody_returns')) {
            Schema::create('fleet_custody_returns', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->foreignId('agent_id')->constrained('fleet_agents')->cascadeOnDelete();
                $table->string('return_number', 32);
                $table->date('returned_on');
                $table->string('status', 24)->default('draft');
                $table->text('notes')->nullable();
                $table->unsignedBigInteger('recorded_by')->nullable();
                $table->timestamp('confirmed_at')->nullable();
                $table->timestamps();
                $table->unique(['user_id', 'return_number']);
            });
        }

        if (! Schema::hasTable('fleet_custody_return_lines')) {
            Schema::create('fleet_custody_return_lines', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->foreignId('return_id')->constrained('fleet_custody_returns')->cascadeOnDelete();
                $table->foreignId('product_id')->constrained('fleet_products')->cascadeOnDelete();
                $table->decimal('quantity', 14, 4);
                $table->decimal('unit_price', 14, 4)->default(0);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('fleet_collections')) {
            Schema::create('fleet_collections', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->foreignId('agent_id')->constrained('fleet_agents')->cascadeOnDelete();
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
                $table->unique(['user_id', 'collection_number']);
            });
        }

        if (! Schema::hasTable('fleet_collection_lines')) {
            Schema::create('fleet_collection_lines', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->foreignId('collection_id')->constrained('fleet_collections')->cascadeOnDelete();
                $table->foreignId('product_id')->constrained('fleet_products')->cascadeOnDelete();
                $table->decimal('quantity', 14, 4);
                $table->decimal('unit_price', 14, 4)->default(0);
                $table->decimal('line_total', 14, 4)->default(0);
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('fleet_collection_lines');
        Schema::dropIfExists('fleet_collections');
        Schema::dropIfExists('fleet_custody_return_lines');
        Schema::dropIfExists('fleet_custody_returns');
    }
};
