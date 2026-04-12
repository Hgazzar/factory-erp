<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_audit_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inventory_audit_id')->constrained('inventory_audits')->cascadeOnDelete();
            $table->foreignId('item_id')->constrained('items')->cascadeOnDelete();
            $table->decimal('book_quantity', 15, 4)->default(0)->comment('الرصيد الدفتري');
            $table->decimal('actual_quantity', 15, 4)->nullable()->comment('الرصيد الفعلي');
            $table->decimal('unit_cost', 15, 4)->default(0)->comment('التكلفة عند الجرد');
            $table->decimal('difference', 15, 4)->default(0)->comment('الفرق = فعلي - دفتري');
            $table->decimal('difference_value', 15, 4)->default(0)->comment('قيمة الفرق المالي');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_audit_lines');
    }
};
