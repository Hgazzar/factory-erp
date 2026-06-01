<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        $this->call([
            SystemModuleSeeder::class,
            TenantModuleSeeder::class,
            UnitSeeder::class,
            SupplierSeeder::class,
            ItemSeeder::class,
            AccountSeeder::class,
            FinanceModuleSeeder::class,
            JournalEntrySeeder::class,
            ExpenseCategorySeeder::class,
            FixedAssetSeeder::class,
            CostCenterSeeder::class,
            LoyaltyProgramsSeeder::class,
        ]);
    }
}
