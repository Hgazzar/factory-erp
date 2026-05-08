<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('crm_opportunities', function (Blueprint $table) {
            $table->text('description')->nullable();
            $table->date('next_follow_up_date')->nullable();
            $table->text('competitor_notes')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('crm_opportunities', function (Blueprint $table) {
            $table->dropColumn(['description', 'next_follow_up_date', 'competitor_notes']);
        });
    }
};
