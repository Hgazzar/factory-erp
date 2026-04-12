<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('credit_notes', function (Blueprint $table) {
            if (! Schema::hasColumn('credit_notes', 'reference')) {
                $table->string('reference', 100)->nullable()->after('date');
            }
            if (! Schema::hasColumn('credit_notes', 'reason_type')) {
                $table->string('reason_type', 100)->nullable()->after('tax_amount');
            }
            if (! Schema::hasColumn('credit_notes', 'notes')) {
                $table->text('notes')->nullable()->after('reason');
            }
        });
    }

    public function down(): void
    {
        Schema::table('credit_notes', function (Blueprint $table) {
            if (Schema::hasColumn('credit_notes', 'notes')) {
                $table->dropColumn('notes');
            }
            if (Schema::hasColumn('credit_notes', 'reason_type')) {
                $table->dropColumn('reason_type');
            }
            if (Schema::hasColumn('credit_notes', 'reference')) {
                $table->dropColumn('reference');
            }
        });
    }
};

