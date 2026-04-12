<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('sales_targets', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('target_type', 50)->default('revenue'); // الإيرادات حالياً
            $table->string('assigned_to_type', 50)->default('company'); // company|warehouse|customer
            $table->unsignedBigInteger('assigned_to_id')->nullable();
            $table->string('period', 20)->default('custom'); // monthly|quarterly|yearly|custom
            $table->date('start_date');
            $table->date('end_date');
            $table->decimal('target_amount', 15, 4)->default(0);
            $table->decimal('threshold_amount', 15, 4)->nullable()->comment('الحد الأدنى (Threshold)');
            $table->decimal('stretch_amount', 15, 4)->nullable()->comment('الهدف الممتد (Stretch)');
            $table->decimal('achieved_amount', 15, 4)->default(0);
            $table->string('status', 20)->default('active'); // active|completed|expired
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['assigned_to_type', 'assigned_to_id']);
            $table->index(['start_date', 'end_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sales_targets');
    }
};
