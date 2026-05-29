<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Models\Account;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

abstract class AccountingTestCase extends TestCase
{
    use RefreshDatabase;

    protected User $tenant;

    protected function migrateFreshUsing(): array
    {
        return [
            '--drop-views' => false,
            '--drop-types' => false,
            '--path' => base_path('tests/database/migrations'),
            '--realpath' => true,
        ];
    }

    protected function migrateDatabases(): void
    {
        $this->artisan('migrate:fresh', $this->migrateFreshUsing());

        if (! Schema::hasTable('users')) {
            throw new \RuntimeException('Accounting test migrations did not create the users table.');
        }
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = User::factory()->create(['role' => 'admin']);
        $this->actingAs($this->tenant);
    }

    protected function makeAccount(string $type, float $openingBalance = 0.0, ?string $code = null): Account
    {
        return Account::factory()
            ->forTenant($this->tenant)
            ->type($type)
            ->withBalances($openingBalance)
            ->create([
                'code' => $code ?? fake()->unique()->numerify('ACC-####'),
            ]);
    }

    protected function currentBalance(Account $account): float
    {
        return (float) Account::withoutGlobalScopes()
            ->whereKey($account->id)
            ->value('current_balance');
    }

    protected function assertBalance(Account $account, float $expected): void
    {
        $this->assertEqualsWithDelta(
            $expected,
            $this->currentBalance($account),
            0.0001,
            sprintf('Expected account %s balance %.4f', $account->code, $expected)
        );
    }
}
