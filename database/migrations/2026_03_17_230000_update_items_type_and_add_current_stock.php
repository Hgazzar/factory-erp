<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (! Schema::hasColumn('items', 'current_stock')) {
            Schema::table('items', function (Blueprint $table) {
                $table->decimal('current_stock', 15, 4)
                    ->default(0)
                    ->after('type')
                    ->comment('الرصيد الحالي للصنف');
            });
        }

        // توحيد القيم الحالية قبل فرض القيود الجديدة
        DB::table('items')
            ->whereIn('type', ['finished', 'semi_finished'])
            ->update(['type' => 'finished_good']);

        DB::table('items')
            ->whereNull('type')
            ->orWhereNotIn('type', ['raw_material', 'finished_good', 'service'])
            ->update(['type' => 'finished_good']);

        $driver = DB::getDriverName();

        if ($driver === 'pgsql') {
            DB::statement("
                DO $$
                BEGIN
                    IF NOT EXISTS (SELECT 1 FROM pg_type WHERE typname = 'item_type_enum') THEN
                        CREATE TYPE item_type_enum AS ENUM ('raw_material', 'finished_good', 'service');
                    END IF;
                END
                $$;
            ");

            DB::statement('ALTER TABLE items ALTER COLUMN type DROP DEFAULT');
            DB::statement('ALTER TABLE items ALTER COLUMN type TYPE item_type_enum USING (type::item_type_enum)');
            DB::statement("ALTER TABLE items ALTER COLUMN type SET DEFAULT 'finished_good'");
        } elseif ($driver === 'mysql') {
            DB::statement("
                ALTER TABLE items
                MODIFY COLUMN type ENUM('raw_material', 'finished_good', 'service')
                NOT NULL DEFAULT 'finished_good'
            ");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $driver = DB::getDriverName();

        if ($driver === 'pgsql') {
            DB::statement('ALTER TABLE items ALTER COLUMN type DROP DEFAULT');
            DB::statement('ALTER TABLE items ALTER COLUMN type TYPE VARCHAR(30)');
            DB::statement("ALTER TABLE items ALTER COLUMN type SET DEFAULT 'finished'");

            DB::statement("
                DO $$
                BEGIN
                    IF EXISTS (SELECT 1 FROM pg_type WHERE typname = 'item_type_enum') THEN
                        DROP TYPE item_type_enum;
                    END IF;
                END
                $$;
            ");
        } elseif ($driver === 'mysql') {
            DB::statement("
                ALTER TABLE items
                MODIFY COLUMN type VARCHAR(30)
                NOT NULL DEFAULT 'finished'
            ");
        }

        if (Schema::hasColumn('items', 'current_stock')) {
            Schema::table('items', function (Blueprint $table) {
                $table->dropColumn('current_stock');
            });
        }
    }
};
