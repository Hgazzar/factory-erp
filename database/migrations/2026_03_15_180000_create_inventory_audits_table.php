<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_audits', function (Blueprint $table) {
            $table->id();
            $table->string('audit_number', 50)->unique()->comment('رقم الجرد');
            $table->date('audit_date')->comment('تاريخ الجرد');
            $table->foreignId('warehouse_id')->constrained('warehouses')->cascadeOnDelete()->comment('المستودع');
            $table->string('type', 20)->comment('full = كلي، partial = جزئي');
            $table->string('category', 50)->nullable()->comment('للجرد الجزئي: raw_material, finished_good, service');
            $table->string('status', 20)->default('draft')->comment('draft, approved');
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_audits');
    }
};
