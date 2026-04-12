<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * حقول كانت في واجهة القيد ولم تُخزَّن: ملاحظات الرأس، مركز التكلفة لكل بند.
     */
    public function up(): void
    {
        Schema::table('journal_entries', function (Blueprint $table) {
            $table->text('notes')->nullable()->after('description')->comment('ملاحظات داخلية على القيد');
        });

        Schema::table('journal_items', function (Blueprint $table) {
            $table->string('cost_center', 120)->nullable()->after('description')->comment('مركز التكلفة (نص حر مؤقتاً)');
        });
    }

    public function down(): void
    {
        Schema::table('journal_items', function (Blueprint $table) {
            $table->dropColumn('cost_center');
        });

        Schema::table('journal_entries', function (Blueprint $table) {
            $table->dropColumn('notes');
        });
    }
};
