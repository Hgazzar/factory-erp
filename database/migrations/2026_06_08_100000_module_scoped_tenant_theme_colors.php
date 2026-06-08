<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('tenant_settings')) {
            return;
        }

        Schema::table('tenant_settings', function (Blueprint $table): void {
            foreach ([
                'nursery_theme_primary_color',
                'nursery_theme_secondary_color',
                'clinic_theme_primary_color',
                'clinic_theme_secondary_color',
                'store_theme_primary_color',
                'store_theme_secondary_color',
                'fleet_theme_primary_color',
                'fleet_theme_secondary_color',
            ] as $column) {
                if (! Schema::hasColumn('tenant_settings', $column)) {
                    $table->string($column, 7)->nullable();
                }
            }
        });

        $this->migrateLegacyThemeColors();
    }

    public function down(): void
    {
        if (! Schema::hasTable('tenant_settings')) {
            return;
        }

        Schema::table('tenant_settings', function (Blueprint $table): void {
            foreach ([
                'nursery_theme_primary_color',
                'nursery_theme_secondary_color',
                'clinic_theme_primary_color',
                'clinic_theme_secondary_color',
                'store_theme_primary_color',
                'store_theme_secondary_color',
                'fleet_theme_primary_color',
                'fleet_theme_secondary_color',
            ] as $column) {
                if (Schema::hasColumn('tenant_settings', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }

    private function migrateLegacyThemeColors(): void
    {
        if (! Schema::hasColumn('tenant_settings', 'theme_primary_color')) {
            return;
        }

        $profiles = Schema::hasTable('tenant_profiles')
            ? DB::table('tenant_profiles')->pluck('niche_key', 'tenant_user_id')
            : collect();

        $rows = DB::table('tenant_settings')
            ->whereNotNull('theme_primary_color')
            ->orWhereNotNull('theme_secondary_color')
            ->get(['tenant_user_id', 'theme_primary_color', 'theme_secondary_color']);

        foreach ($rows as $row) {
            $tenantUserId = (int) $row->tenant_user_id;
            $nicheKey = strtolower(trim((string) ($profiles[$tenantUserId] ?? '')));

            [$primaryCol, $secondaryCol] = match ($nicheKey) {
                'medical_clinics' => ['clinic_theme_primary_color', 'clinic_theme_secondary_color'],
                'fleet_agents' => ['fleet_theme_primary_color', 'fleet_theme_secondary_color'],
                'retail', 'manufacturing' => ['store_theme_primary_color', 'store_theme_secondary_color'],
                'nurseries' => ['nursery_theme_primary_color', 'nursery_theme_secondary_color'],
                default => ['nursery_theme_primary_color', 'nursery_theme_secondary_color'],
            };

            DB::table('tenant_settings')
                ->where('tenant_user_id', $tenantUserId)
                ->update([
                    $primaryCol => $row->theme_primary_color,
                    $secondaryCol => $row->theme_secondary_color,
                    'updated_at' => now(),
                ]);
        }
    }
};
