<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('crm_segments')) {
            Schema::create('crm_segments', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->string('code', 40);
                $table->string('name', 180);
                $table->string('type', 40);
                $table->string('status', 20)->default('active');
                $table->json('criteria')->nullable();
                $table->timestamp('last_refreshed_at')->nullable();
                $table->timestamps();

                $table->unique(['user_id', 'code']);
                $table->index(['user_id', 'type', 'status']);
                $table->index(['user_id', 'name']);
            });
        }

        if (! Schema::hasTable('crm_segment_customer')) {
            Schema::create('crm_segment_customer', function (Blueprint $table) {
                $table->id();
                $table->foreignId('segment_id')->constrained('crm_segments')->cascadeOnDelete();
                $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();
                $table->timestamps();

                $table->unique(['segment_id', 'customer_id']);
                $table->index(['customer_id', 'segment_id']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('crm_segment_customer');
        Schema::dropIfExists('crm_segments');
    }
};
