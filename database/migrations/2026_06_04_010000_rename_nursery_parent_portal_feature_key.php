<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (! \Illuminate\Support\Facades\Schema::hasTable('tenant_features')) {
            return;
        }

        DB::table('tenant_features')
            ->where('feature_key', 'nursery_parent_portal')
            ->update(['feature_key' => 'nursery_portal']);
    }

    public function down(): void
    {
        if (! \Illuminate\Support\Facades\Schema::hasTable('tenant_features')) {
            return;
        }

        DB::table('tenant_features')
            ->where('feature_key', 'nursery_portal')
            ->update(['feature_key' => 'nursery_parent_portal']);
    }
};
