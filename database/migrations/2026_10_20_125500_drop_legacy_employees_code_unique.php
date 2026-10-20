<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * اللايف أبقى قيد employees_code_unique لأن DROP INDEX لا يحذف CONSTRAINT في PostgreSQL.
 * الكود أصبح لكل مستأجر (user_id + code).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('employees')) {
            return;
        }

        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'pgsql') {
            DB::statement('ALTER TABLE employees DROP CONSTRAINT IF EXISTS employees_code_unique');
            DB::statement('DROP INDEX IF EXISTS employees_code_unique');
        } else {
            try {
                Schema::table('employees', function (Blueprint $table): void {
                    $table->dropUnique('employees_code_unique');
                });
            } catch (\Throwable) {
                try {
                    DB::statement('DROP INDEX IF EXISTS employees_code_unique');
                } catch (\Throwable) {
                    //
                }
            }
        }

        if (! $this->uniqueNamed('employees_user_code_unique')) {
            Schema::table('employees', function (Blueprint $table): void {
                $table->unique(['user_id', 'code'], 'employees_user_code_unique');
            });
        }
    }

    public function down(): void
    {
        // لا نعيد القيد العام — يكسر تعدد المستأجرين.
    }

    private function uniqueNamed(string $name): bool
    {
        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'pgsql') {
            $constraint = DB::select('SELECT 1 FROM pg_constraint WHERE conname = ? LIMIT 1', [$name]);
            if ($constraint !== []) {
                return true;
            }

            $index = DB::select(
                'SELECT 1 FROM pg_indexes WHERE indexname = ? AND tablename = ? LIMIT 1',
                [$name, 'employees']
            );

            return $index !== [];
        }

        $rows = DB::select(
            'SELECT 1 FROM sqlite_master WHERE type = ? AND name = ? LIMIT 1',
            ['index', $name]
        );

        return $rows !== [];
    }
};
