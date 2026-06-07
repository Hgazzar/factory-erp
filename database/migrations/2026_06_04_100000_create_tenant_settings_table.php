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
        if (Schema::hasTable('tenant_settings')) {
            return;
        }

        Schema::create('tenant_settings', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('tenant_user_id')->unique();
            $table->string('display_name', 120)->nullable();
            $table->string('logo_path', 500)->nullable();
            $table->string('theme_primary_color', 7)->nullable();
            $table->string('theme_secondary_color', 7)->nullable();
            $table->timestamps();

            $table->foreign('tenant_user_id')->references('id')->on('users')->cascadeOnDelete();
        });

        $this->migrateNurseryBrandingIntoTenantSettings();
    }

    public function down(): void
    {
        Schema::dropIfExists('tenant_settings');
    }

    private function migrateNurseryBrandingIntoTenantSettings(): void
    {
        if (! Schema::hasTable('nursery_settings')) {
            return;
        }

        $columns = ['display_name', 'logo_path', 'theme_primary_color', 'theme_secondary_color'];
        $hasAny = collect($columns)->contains(fn (string $col): bool => Schema::hasColumn('nursery_settings', $col));
        if (! $hasAny) {
            return;
        }

        $rows = DB::table('nursery_settings')->get(['user_id', ...$columns]);
        $now = now();

        foreach ($rows as $row) {
            $tenantUserId = (int) $row->user_id;
            if ($tenantUserId < 1) {
                continue;
            }

            $payload = [
                'display_name' => $row->display_name ?? null,
                'logo_path' => $row->logo_path ?? null,
                'theme_primary_color' => $row->theme_primary_color ?? null,
                'theme_secondary_color' => $row->theme_secondary_color ?? null,
            ];

            if (collect($payload)->filter(fn ($v) => $v !== null && trim((string) $v) !== '')->isEmpty()) {
                continue;
            }

            DB::table('tenant_settings')->updateOrInsert(
                ['tenant_user_id' => $tenantUserId],
                array_merge($payload, ['created_at' => $now, 'updated_at' => $now]),
            );
        }
    }
};
