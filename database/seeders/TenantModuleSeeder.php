<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\User;
use App\Services\Tenant\TenantModuleRegistry;
use Illuminate\Database\Seeder;

class TenantModuleSeeder extends Seeder
{
    public function run(): void
    {
        $registry = app(TenantModuleRegistry::class);

        User::query()
            ->where('role', 'admin')
            ->each(function (User $user) use ($registry): void {
                $registry->syncModulesForTenant((int) $user->id);
            });
    }
}
