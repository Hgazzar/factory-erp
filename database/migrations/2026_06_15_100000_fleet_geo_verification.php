<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('fleet_customers', function (Blueprint $table): void {
            $table->decimal('latitude', 10, 7)->nullable()->after('region');
            $table->decimal('longitude', 10, 7)->nullable()->after('latitude');
            $table->string('location_status', 16)->default('none')->after('longitude');
            $table->string('location_source', 16)->nullable()->after('location_status');
            $table->timestamp('location_updated_at')->nullable()->after('location_source');
            $table->unsignedInteger('geofence_radius')->nullable()->after('location_updated_at');
            $table->index(['user_id', 'location_status']);
        });

        Schema::create('fleet_visits', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('agent_id')->constrained('fleet_agents')->cascadeOnDelete();
            $table->unsignedBigInteger('customer_id')->nullable();
            $table->unsignedBigInteger('route_stop_id')->nullable();
            $table->unsignedBigInteger('collection_id')->nullable();
            $table->decimal('captured_lat', 10, 7)->nullable();
            $table->decimal('captured_lng', 10, 7)->nullable();
            $table->unsignedInteger('accuracy_meters')->nullable();
            $table->boolean('is_mocked')->default(false);
            $table->string('geofence_status', 16)->default('unverified');
            $table->unsignedInteger('distance_meters')->nullable();
            $table->string('outcome', 16)->default('sale');
            $table->string('visit_reason', 191)->nullable();
            $table->timestamp('visited_at')->nullable();
            $table->timestamps();

            $table->foreign('customer_id')->references('id')->on('fleet_customers')->nullOnDelete();
            $table->index(['user_id', 'is_mocked']);
            $table->index(['user_id', 'geofence_status']);
            $table->index(['user_id', 'customer_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fleet_visits');

        Schema::table('fleet_customers', function (Blueprint $table): void {
            $table->dropIndex(['user_id', 'location_status']);
            $table->dropColumn([
                'latitude',
                'longitude',
                'location_status',
                'location_source',
                'location_updated_at',
                'geofence_radius',
            ]);
        });
    }
};
