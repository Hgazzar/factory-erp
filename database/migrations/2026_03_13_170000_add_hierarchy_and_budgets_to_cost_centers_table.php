<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cost_centers', function (Blueprint $table) {
            $table->foreignId('parent_id')
                ->nullable()
                ->after('branch')
                ->constrained('cost_centers')
                ->nullOnDelete();
            $table->decimal('monthly_budget', 14, 2)->default(0)->after('annual_budget');
            $table->text('description')->nullable()->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('cost_centers', function (Blueprint $table) {
            $table->dropConstrainedForeignId('parent_id');
            $table->dropColumn(['monthly_budget', 'description']);
        });
    }
};
