<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('receive_notes', function (Blueprint $table) {
            $table->string('reference', 100)->nullable()->after('receive_date');
            $table->string('supplier_delivery_notice', 255)->nullable()->after('reference');
            $table->boolean('requires_inspection')->default(false)->after('status');
            $table->text('internal_notes')->nullable()->after('notes');
        });

        Schema::table('receive_note_items', function (Blueprint $table) {
            $table->decimal('quantity_required', 14, 4)->default(0)->after('unit')->comment('مطلوب');
            $table->decimal('quantity_accepted', 14, 4)->default(0)->after('quantity_required')->comment('مقبول');
            $table->decimal('quantity_rejected', 14, 4)->default(0)->after('quantity_accepted')->comment('مرفوض');
            $table->decimal('unit_cost', 14, 4)->default(0)->after('quantity_rejected');
            $table->decimal('line_cost', 14, 4)->default(0)->after('unit_cost');
        });
    }

    public function down(): void
    {
        Schema::table('receive_notes', function (Blueprint $table) {
            $table->dropColumn(['reference', 'supplier_delivery_notice', 'requires_inspection', 'internal_notes']);
        });
        Schema::table('receive_note_items', function (Blueprint $table) {
            $table->dropColumn(['quantity_required', 'quantity_accepted', 'quantity_rejected', 'unit_cost', 'line_cost']);
        });
    }
};
