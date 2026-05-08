<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('customers')) {
            return;
        }

        Schema::table('customers', function (Blueprint $table) {
            if (! Schema::hasColumn('customers', 'lead_number')) {
                $table->string('lead_number', 40)->nullable()->after('code');
            }
            if (! Schema::hasColumn('customers', 'lead_priority')) {
                $table->string('lead_priority', 20)->nullable()->after('source');
            }
            if (! Schema::hasColumn('customers', 'lead_rating')) {
                $table->unsignedTinyInteger('lead_rating')->nullable()->after('lead_priority');
            }
        });

        if (Schema::hasColumn('customers', 'lead_number')) {
            Schema::table('customers', function (Blueprint $table) {
                $table->unique(['user_id', 'lead_number'], 'customers_user_id_lead_number_unique');
            });
        }

        $userIds = DB::table('customers')
            ->where('crm_status', 'potential')
            ->whereNull('lead_number')
            ->distinct()
            ->pluck('user_id');

        foreach ($userIds as $userId) {
            $rows = DB::table('customers')
                ->where('user_id', $userId)
                ->where('crm_status', 'potential')
                ->whereNull('lead_number')
                ->orderBy('id')
                ->pluck('id');

            $seq = 1;
            foreach ($rows as $customerId) {
                $candidate = 'LEAD-'.str_pad((string) $seq, 4, '0', STR_PAD_LEFT);
                $seq++;

                while (DB::table('customers')
                    ->where('user_id', $userId)
                    ->where('lead_number', $candidate)
                    ->exists()) {
                    $candidate = 'LEAD-'.str_pad((string) $seq, 4, '0', STR_PAD_LEFT);
                    $seq++;
                }

                DB::table('customers')->where('id', $customerId)->update(['lead_number' => $candidate]);
            }
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('customers')) {
            return;
        }

        Schema::table('customers', function (Blueprint $table) {
            if (Schema::hasColumn('customers', 'lead_number')) {
                $table->dropUnique('customers_user_id_lead_number_unique');
            }
        });

        Schema::table('customers', function (Blueprint $table) {
            foreach (['lead_rating', 'lead_priority', 'lead_number'] as $col) {
                if (Schema::hasColumn('customers', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
