<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('company_settings', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->after('id')->constrained()->cascadeOnDelete();
        });

        $firstId = DB::table('company_settings')->orderBy('id')->value('id');
        if ($firstId !== null) {
            DB::table('company_settings')->where('id', $firstId)->update(['user_id' => 1]);
        }

        DB::table('company_settings')->whereNull('user_id')->delete();

        Schema::table('company_settings', function (Blueprint $table) {
            $table->unique('user_id');
        });
    }

    public function down(): void
    {
        Schema::table('company_settings', function (Blueprint $table) {
            $table->dropUnique(['user_id']);
        });

        Schema::table('company_settings', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropColumn('user_id');
        });
    }
};
