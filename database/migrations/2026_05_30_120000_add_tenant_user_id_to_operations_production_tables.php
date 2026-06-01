<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $defaultTenantId = (int) (DB::table('users')->where('role', 'admin')->orderBy('id')->value('id') ?? 1);

        foreach (['production_lines', 'machines', 'production_shifts', 'production_logs', 'production_records'] as $table) {
            if (! Schema::hasTable($table) || Schema::hasColumn($table, 'user_id')) {
                continue;
            }

            Schema::table($table, function (Blueprint $blueprint): void {
                $blueprint->foreignId('user_id')
                    ->nullable()
                    ->after('id')
                    ->constrained('users')
                    ->cascadeOnDelete();
                $blueprint->index(['user_id']);
            });
        }

        if (Schema::hasTable('production_lines')) {
            DB::table('production_lines')->whereNull('user_id')->update(['user_id' => $defaultTenantId]);
        }

        if (Schema::hasTable('machines')) {
            DB::table('machines')->whereNull('user_id')->update(['user_id' => $defaultTenantId]);

            foreach (DB::table('machines')->whereNotNull('production_line_id')->get(['id', 'production_line_id']) as $machine) {
                $lineUserId = DB::table('production_lines')->where('id', $machine->production_line_id)->value('user_id');
                if ($lineUserId) {
                    DB::table('machines')->where('id', $machine->id)->update(['user_id' => $lineUserId]);
                }
            }
        }

        if (Schema::hasTable('production_shifts')) {
            DB::table('production_shifts')->whereNull('user_id')->update(['user_id' => $defaultTenantId]);

            foreach (DB::table('production_shifts')->get(['id', 'machine_id', 'production_line_id']) as $shift) {
                $tenantId = null;
                if ($shift->machine_id) {
                    $tenantId = DB::table('machines')->where('id', $shift->machine_id)->value('user_id');
                }
                if (! $tenantId && $shift->production_line_id) {
                    $tenantId = DB::table('production_lines')->where('id', $shift->production_line_id)->value('user_id');
                }
                if ($tenantId) {
                    DB::table('production_shifts')->where('id', $shift->id)->update(['user_id' => $tenantId]);
                }
            }
        }

        if (Schema::hasTable('production_logs')) {
            DB::table('production_logs')->whereNull('user_id')->update(['user_id' => $defaultTenantId]);

            foreach (DB::table('production_logs')->get(['id', 'production_shift_id', 'item_id']) as $log) {
                $tenantId = null;
                if ($log->production_shift_id) {
                    $tenantId = DB::table('production_shifts')->where('id', $log->production_shift_id)->value('user_id');
                }
                if (! $tenantId && $log->item_id) {
                    $tenantId = DB::table('items')->where('id', $log->item_id)->value('user_id');
                }
                if ($tenantId) {
                    DB::table('production_logs')->where('id', $log->id)->update(['user_id' => $tenantId]);
                }
            }
        }

        if (Schema::hasTable('production_records')) {
            DB::table('production_records')->whereNull('user_id')->update(['user_id' => $defaultTenantId]);

            foreach (DB::table('production_records')->get(['id', 'production_shift_id', 'item_id']) as $record) {
                $tenantId = null;
                if ($record->production_shift_id) {
                    $tenantId = DB::table('production_shifts')->where('id', $record->production_shift_id)->value('user_id');
                }
                if (! $tenantId && $record->item_id) {
                    $tenantId = DB::table('items')->where('id', $record->item_id)->value('user_id');
                }
                if ($tenantId) {
                    DB::table('production_records')->where('id', $record->id)->update(['user_id' => $tenantId]);
                }
            }
        }

        $this->replaceGlobalUniqueWithTenantUnique('production_lines', 'production_lines_user_code_unique');
        $this->replaceGlobalUniqueWithTenantUnique('machines', 'machines_user_code_unique');
    }

    public function down(): void
    {
        $this->restoreGlobalUnique('production_lines', 'production_lines_code_unique', 'production_lines_user_code_unique');
        $this->restoreGlobalUnique('machines', 'machines_code_unique', 'machines_user_code_unique');

        foreach (array_reverse(['production_records', 'production_logs', 'production_shifts', 'machines', 'production_lines']) as $table) {
            if (! Schema::hasTable($table) || ! Schema::hasColumn($table, 'user_id')) {
                continue;
            }

            Schema::table($table, function (Blueprint $blueprint): void {
                $blueprint->dropConstrainedForeignId('user_id');
            });
        }
    }

    private function replaceGlobalUniqueWithTenantUnique(string $table, string $compositeIndexName): void
    {
        if (! Schema::hasTable($table) || ! Schema::hasColumn($table, 'user_id')) {
            return;
        }

        try {
            Schema::table($table, function (Blueprint $blueprint): void {
                $blueprint->dropUnique(['code']);
            });
        } catch (\Throwable) {
        }

        Schema::table($table, function (Blueprint $blueprint) use ($compositeIndexName): void {
            $blueprint->unique(['user_id', 'code'], $compositeIndexName);
        });
    }

    private function restoreGlobalUnique(string $table, string $globalIndexName, string $compositeIndexName): void
    {
        if (! Schema::hasTable($table)) {
            return;
        }

        try {
            Schema::table($table, function (Blueprint $blueprint) use ($compositeIndexName): void {
                $blueprint->dropUnique($compositeIndexName);
            });
        } catch (\Throwable) {
        }

        Schema::table($table, function (Blueprint $blueprint) use ($globalIndexName): void {
            $blueprint->unique(['code'], $globalIndexName);
        });
    }
};
